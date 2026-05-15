<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\LeaveRequest;

/**
 * ============================================================================
 * INTEGRATION TESTING - PENGUJIAN INTEGRASI PROGRAM
 * ============================================================================
 * 
 * File ini mendemonstrasikan Integration Testing dengan 3 skenario workflow
 * yang menguji multiple components bekerja bersama:
 * - Authentication Layer (Login)
 * - Controller Layer (Business Logic)
 * - Database Layer (Data Persistence)
 * 
 * Perbedaan dengan Unit Test:
 * - Unit Test: Menguji 1 function/method saja
 * - Integration Test: Menguji alur lengkap (Auth → Controller → DB)
 * ============================================================================
 */
class LeaveRequestWorkflowTest extends TestCase
{
    /**
     * ========================================================================
     * WORKFLOW #1: End-to-End - Pegawai Submit Cuti → Verifikasi Database
     * ========================================================================
     * 
     * Skenario Bisnis:
     * Pegawai bernama Budi ingin mengajukan cuti tahunan selama 3 hari.
     * 
     * Alur:
     * 1. Budi (sebagai pegawai) submit form pengajuan cuti
     * 2. Controller validasi data
     * 3. Data disimpan di database dengan status 'pending'
     * 4. Budi dapat melihat pengajuannya dalam daftar cuti
     * 
     * Testing Layers:
     * ✓ Authentication: actingAs() memastikan user terautentikasi
     * ✓ Validation: postJson() mengirim data
     * ✓ Business Logic: Controller calculate days, set status
     * ✓ Database: Data tersimpan di leave_requests table
     * ✓ Query: Pegawai bisa retrieve pengajuannya
     * 
     * ========================================================================
     */
    public function test_workflow_1_pegawai_submit_leave_request()
    {
        // ────────────────────────────────────────────────────────────────
        // GIVEN: Setup awal - buat data pegawai di database
        // ────────────────────────────────────────────────────────────────
        $pegawai = User::factory()->pegawai()->create([
            'name' => 'Budi',
            'email' => 'budi@company.com'
        ]);

        // ────────────────────────────────────────────────────────────────
        // WHEN: Pegawai submit pengajuan cuti
        // Menggunakan actingAs() untuk simulate authenticated request
        // ────────────────────────────────────────────────────────────────
        $submitResponse = $this->actingAs($pegawai, 'sanctum')
            ->postJson('/api/leave-requests', [
                'start_date' => '2026-06-15',
                'end_date' => '2026-06-17',
                'type' => 'tahunan',
                'reason' => 'Liburan keluarga'
            ]);

        // ────────────────────────────────────────────────────────────────
        // THEN: Verifikasi response dari controller
        // - Status code 201 (resource created)
        // - Data berisikan field yang expected
        // ────────────────────────────────────────────────────────────────
        $submitResponse->assertStatus(201)
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('user_id', $pegawai->id)
            ->assertJsonPath('days', 3)
            ->assertJsonPath('type', 'tahunan');

        // ────────────────────────────────────────────────────────────────
        // THEN: Verifikasi data tersimpan di database
        // Database harus memiliki record baru dengan nilai yang correct
        // ────────────────────────────────────────────────────────────────
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $pegawai->id,
            'type' => 'tahunan',
            'status' => 'pending',
            'days' => 3,
            'reason' => 'Liburan keluarga',
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-17'
        ]);

        // ────────────────────────────────────────────────────────────────
        // WHEN: Pegawai lihat daftar pengajuannya
        // ────────────────────────────────────────────────────────────────
        $listResponse = $this->actingAs($pegawai, 'sanctum')
            ->getJson('/api/leave-requests');

        // ────────────────────────────────────────────────────────────────
        // THEN: Verifikasi pengajuan ada dalam daftar
        // ────────────────────────────────────────────────────────────────
        $listResponse->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.type', 'tahunan')
            ->assertJsonPath('0.status', 'pending');
    }

    /**
     * ========================================================================
     * WORKFLOW #2: Role-Based Authorization - Atasan Approve Pengajuan
     * ========================================================================
     * 
     * Skenario Bisnis:
     * Pegawai submit cuti → Atasan review → Atasan approve
     * Mendemonstrasikan: Role-based filtering, Authorization, Audit trail
     * 
     * Alur:
     * 1. Pegawai submit cuti (status: pending)
     * 2. Atasan lihat pengajuan
     * 3. Atasan hanya melihat yang status 'pending'
     * 4. Atasan approve pengajuan
     * 5. Database terupdate: status=approved, approved_by=atasan_id
     * 
     * Testing Layers:
     * ✓ Role-Based Query: Atasan hanya lihat pending (middleware filter)
     * ✓ Authorization: Hanya atasan/admin bisa approve (middleware check)
     * ✓ Business Logic: Status berubah, approved_by terisi
     * ✓ Audit Trail: approved_by mencatat siapa yang approve
     * 
     * ========================================================================
     */
    public function test_workflow_2_atasan_approve_leave_request()
    {
        // ────────────────────────────────────────────────────────────────
        // GIVEN: Pegawai sudah submit cuti dengan status pending
        // ────────────────────────────────────────────────────────────────
        $pegawai = User::factory()->pegawai()->create();
        $leave = LeaveRequest::factory()->create([
            'user_id' => $pegawai->id,
            'status' => 'pending'
        ]);

        // ────────────────────────────────────────────────────────────────
        // GIVEN: Atasan sudah ada di database
        // ────────────────────────────────────────────────────────────────
        $atasan = User::factory()->atasan()->create();

        // ────────────────────────────────────────────────────────────────
        // WHEN: Atasan lihat daftar pengajuan cuti
        // Controller filter: hanya tampilkan status 'pending' untuk atasan
        // ────────────────────────────────────────────────────────────────
        $atasanViewResponse = $this->actingAs($atasan, 'sanctum')
            ->getJson('/api/leave-requests');

        // ────────────────────────────────────────────────────────────────
        // THEN: Atasan hanya lihat pengajuan dengan status pending
        // Ini menunjukkan role-based filtering bekerja dengan benar
        // ────────────────────────────────────────────────────────────────
        $atasanViewResponse->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.status', 'pending')
            ->assertJsonPath('0.user_id', $pegawai->id);

        // ────────────────────────────────────────────────────────────────
        // WHEN: Atasan approve pengajuan cuti
        // Endpoint hanya accessible untuk role atasan/admin (middleware check)
        // ────────────────────────────────────────────────────────────────
        $approveResponse = $this->actingAs($atasan, 'sanctum')
            ->postJson("/api/leave-requests/{$leave->id}/approve");

        // ────────────────────────────────────────────────────────────────
        // THEN: Response menunjukkan status berubah ke approved
        // approved_by field terupdate dengan atasan ID (audit trail)
        // ────────────────────────────────────────────────────────────────
        $approveResponse->assertStatus(200)
            ->assertJsonPath('status', 'approved')
            ->assertJsonPath('approved_by', $atasan->id);

        // ────────────────────────────────────────────────────────────────
        // THEN: Database terupdate dengan status baru
        // Data integrity: response harus sesuai dengan database
        // ────────────────────────────────────────────────────────────────
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'status' => 'approved',
            'approved_by' => $atasan->id
        ]);

        // ────────────────────────────────────────────────────────────────
        // WHEN: Pegawai lihat pengajuannya setelah diapprove
        // ────────────────────────────────────────────────────────────────
        $pegawaiViewResponse = $this->actingAs($pegawai, 'sanctum')
            ->getJson("/api/leave-requests/{$leave->id}");

        // ────────────────────────────────────────────────────────────────
        // THEN: Pegawai bisa melihat pengajuannya sudah approved
        // Menunjukkan perubahan data terlihat dari semua user
        // ────────────────────────────────────────────────────────────────
        $pegawaiViewResponse->assertStatus(200)
            ->assertJsonPath('status', 'approved')
            ->assertJsonPath('approved_by', $atasan->id);
    }

    /**
     * ========================================================================
     * WORKFLOW #3: Validation & Error Handling - Invalid Data → Error → Retry
     * ========================================================================
     * 
     * Skenario Bisnis:
     * Pegawai submit dengan data invalid → dapat error → fix data → resubmit
     * Mendemonstrasikan: Validation layer, error messages, data integrity
     * 
     * Alur:
     * 1. Pegawai submit dengan tanggal invalid (end_date < start_date)
     * 2. Server return HTTP 422 + validation error message
     * 3. Verifikasi data TIDAK tersimpan (data integrity)
     * 4. Pegawai submit lagi dengan data valid
     * 5. Kali kedua berhasil tersimpan
     * 
     * Testing Layers:
     * ✓ Validation: Input validation di controller
     * ✓ Error Response: HTTP 422 + error message details
     * ✓ Data Integrity: Invalid data tidak masuk database
     * ✓ Retry Logic: User bisa resubmit setelah fix data
     * 
     * ========================================================================
     */
    public function test_workflow_3_validation_error_and_retry()
    {
        // ────────────────────────────────────────────────────────────────
        // GIVEN: Pegawai terautentikasi
        // ────────────────────────────────────────────────────────────────
        $pegawai = User::factory()->pegawai()->create();

        // ────────────────────────────────────────────────────────────────
        // WHEN: Pegawai submit dengan data INVALID
        // end_date (2026-06-10) < start_date (2026-06-15) = SALAH!
        // Ini melanggar business rule: end_date >= start_date
        // ────────────────────────────────────────────────────────────────
        $invalidResponse = $this->actingAs($pegawai, 'sanctum')
            ->postJson('/api/leave-requests', [
                'start_date' => '2026-06-15',
                'end_date' => '2026-06-10',  // ❌ INVALID: lebih kecil dari start_date
                'type' => 'tahunan',
                'reason' => 'Liburan'
            ]);

        // ────────────────────────────────────────────────────────────────
        // THEN: Server return error response
        // - Status code 422 (Unprocessable Entity)
        // - Errors array berisi detail masalah di field mana
        // ────────────────────────────────────────────────────────────────
        $invalidResponse->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);

        // ────────────────────────────────────────────────────────────────
        // THEN: Data TIDAK tersimpan di database (data integrity)
        // Invalid data tidak boleh masuk database
        // ────────────────────────────────────────────────────────────────
        $this->assertDatabaseMissing('leave_requests', [
            'user_id' => $pegawai->id,
            'reason' => 'Liburan'
        ]);

        // ────────────────────────────────────────────────────────────────
        // WHEN: Pegawai submit LAGI dengan data VALID
        // Kali ini: end_date (2026-06-20) > start_date (2026-06-15) = BENAR
        // ────────────────────────────────────────────────────────────────
        $validResponse = $this->actingAs($pegawai, 'sanctum')
            ->postJson('/api/leave-requests', [
                'start_date' => '2026-06-15',
                'end_date' => '2026-06-20',  // ✓ VALID: lebih besar dari start_date
                'type' => 'tahunan',
                'reason' => 'Liburan'
            ]);

        // ────────────────────────────────────────────────────────────────
        // THEN: Submit berhasil kali kedua
        // - Status code 201 (resource created)
        // - Perhitungan days: 15-20 Juni = 6 hari
        // ────────────────────────────────────────────────────────────────
        $validResponse->assertStatus(201)
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('days', 6);

        // ────────────────────────────────────────────────────────────────
        // THEN: Data tersimpan di database (hanya data valid)
        // ────────────────────────────────────────────────────────────────
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $pegawai->id,
            'type' => 'tahunan',
            'status' => 'pending',
            'days' => 6,
            'reason' => 'Liburan'
        ]);

        // ────────────────────────────────────────────────────────────────
        // WHEN: Pegawai lihat pengajuannya
        // ────────────────────────────────────────────────────────────────
        $listResponse = $this->actingAs($pegawai, 'sanctum')
            ->getJson('/api/leave-requests');

        // ────────────────────────────────────────────────────────────────
        // THEN: Pengajuan terlihat di daftar
        // Menunjukkan data valid berhasil tersimpan dan queryable
        // ────────────────────────────────────────────────────────────────
        $listResponse->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.days', 6);
    }
}
