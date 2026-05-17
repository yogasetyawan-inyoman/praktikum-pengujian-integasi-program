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
 * File ini disusun mengikuti skenario incremental untuk modul praktik:
 * 1. Pegawai submit cuti
 * 2. Pegawai melihat daftar pengajuan milik sendiri
 * 3. Pegawai melihat detail pengajuan miliknya sendiri
 * 4. Atasan melihat pending request dan approve
 * 5. Atasan reject request
 * 6. Validasi error lalu retry
 * 7. Pegawai update request miliknya sendiri
 * 8. Pegawai delete request miliknya sendiri
 *
 * Fokus pengujian:
 * - Authentication Layer (auth:sanctum)
 * - Authorization Layer (role middleware)
 * - Controller Layer (business logic)
 * - Database Layer (persistence)
 * ============================================================================
 */
class LeaveRequestWorkflowTest extends TestCase
{
    /**
     * INCREMENTAL STEP 1: Pegawai submit cuti
     */
    public function test_incremental_1_pegawai_submit_pengajuan_cuti()
    {
        $pegawai = User::factory()->pegawai()->create([ //membuat user dengan role pegawai untuk keperluan testing.
            'name' => 'Budi',
            'email' => 'budi@company.com'
        ]);

        $response = $this->actingAs($pegawai, 'sanctum')
            ->postJson('/api/leave-requests', [ //mengirim request POST dalam format JSON.
                'start_date' => '2026-06-15',
                'end_date' => '2026-06-17',
                'type' => 'tahunan',
                'reason' => 'Liburan keluarga'
            ]);

        $response->assertStatus(201) //memastikan response HTTP 201 Created.
            ->assertJsonPath('status', 'pending') //memastikan response JSON memiliki field status dengan nilai pending.
            ->assertJsonPath('user_id', $pegawai->id) //memastikan response JSON memiliki field user_id yang sesuai dengan ID pegawai yang membuat request.
            ->assertJsonPath('days', 3)//memastikan response JSON memiliki field days dengan nilai 3 (selisih antara end_date dan start_date).
            ->assertJsonPath('type', 'tahunan');//memastikan response JSON memiliki field type dengan nilai tahunan.

        $this->assertDatabaseHas('leave_requests', [//memastikan database memiliki record di tabel leave_requests dengan data yang sesuai.
            'user_id' => $pegawai->id,
            'type' => 'tahunan',
            'status' => 'pending',
            'days' => 3,
            'reason' => 'Liburan keluarga',
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-17'
        ]);
    }

    /**
     * INCREMENTAL STEP 2: Pegawai melihat daftar pengajuannya sendiri
     */
    public function test_incremental_2_pegawai_melihat_daftar_pengajuannya_sendiri()
    {
        $pegawai = User::factory()->pegawai()->create();//membuat user dengan role pegawai untuk keperluan testing.
        $pegawaiLain = User::factory()->pegawai()->create();//membuat user lain dengan role pegawai untuk memastikan data yang ditampilkan hanya milik pegawai yang sedang login.

        LeaveRequest::factory()->create(['user_id' => $pegawai->id]);//membuat data pengajuan cuti untuk pegawai yang sedang login.
        LeaveRequest::factory()->create(['user_id' => $pegawai->id]);//membuat data pengajuan cuti kedua untuk pegawai yang sedang login.
        LeaveRequest::factory()->create(['user_id' => $pegawaiLain->id]);//membuat data pengajuan cuti untuk pegawai lain yang tidak boleh ditampilkan.

        $response = $this->actingAs($pegawai, 'sanctum')//mengautentikasi sebagai pegawai yang sudah dibuat sebelumnya.
            ->getJson('/api/leave-requests');//mengirim request GET dalam format JSON untuk mendapatkan daftar pengajuan cuti.

        $response->assertStatus(200)//memastikan response HTTP 200 OK.
            ->assertJsonCount(2)//memastikan response JSON berupa array dengan jumlah elemen 2 (hanya pengajuan milik pegawai yang sedang login).
            ->assertJsonPath('0.user_id', $pegawai->id)//memastikan elemen pertama dalam array memiliki field user_id yang sesuai dengan ID pegawai yang sedang login.
            ->assertJsonPath('1.user_id', $pegawai->id);//memastikan elemen kedua dalam array memiliki field user_id yang sesuai dengan ID pegawai yang sedang login.
    }

    /**
     * INCREMENTAL STEP 3: Pegawai melihat detail pengajuan miliknya sendiri
     */
    public function test_incremental_3_pegawai_melihat_detail_pengajuan_miliknya_sendiri()
    {
        $pegawai = User::factory()->pegawai()->create();

        $leave = LeaveRequest::factory()->create(['user_id' => $pegawai->id]);

        $response = $this->actingAs($pegawai, 'sanctum')
            ->getJson("/api/leave-requests/{$leave->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $leave->id)
            ->assertJsonPath('user_id', $pegawai->id);
    }

    /**
     * INCREMENTAL STEP 4: Atasan melihat pending request dan approve
     */
    public function test_incremental_4_atasan_dapat_melihat_dan_menyetujui_pengajuan()
    {
        $pegawai = User::factory()->pegawai()->create();
        $atasan = User::factory()->atasan()->create();

        $leave = LeaveRequest::factory()->create([
            'user_id' => $pegawai->id,
            'status' => 'pending'
        ]);

        $viewResponse = $this->actingAs($atasan, 'sanctum')
            ->getJson('/api/leave-requests');

        $viewResponse->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.status', 'pending')
            ->assertJsonPath('0.user_id', $pegawai->id);

        $approveResponse = $this->actingAs($atasan, 'sanctum')
            ->postJson("/api/leave-requests/{$leave->id}/approve");

        $approveResponse->assertStatus(200)
            ->assertJsonPath('status', 'approved')
            ->assertJsonPath('approved_by', $atasan->id);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'status' => 'approved',
            'approved_by' => $atasan->id
        ]);
    }

    /**
     * INCREMENTAL STEP 5: Atasan reject request
     */
    public function test_incremental_5_atasan_dapat_menolak_pengajuan()
    {
        $pegawai = User::factory()->pegawai()->create();
        $atasan = User::factory()->atasan()->create();

        $leave = LeaveRequest::factory()->create([
            'user_id' => $pegawai->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($atasan, 'sanctum')
            ->postJson("/api/leave-requests/{$leave->id}/reject");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'rejected')
            ->assertJsonPath('approved_by', $atasan->id);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'status' => 'rejected',
            'approved_by' => $atasan->id
        ]);
    }

    /**
     * INCREMENTAL STEP 6: Validasi error lalu retry
     */
    public function test_incremental_6_validasi_gagal_dan_mengulang()
    {
        $pegawai = User::factory()->pegawai()->create();

        $invalidResponse = $this->actingAs($pegawai, 'sanctum')
            ->postJson('/api/leave-requests', [
                'start_date' => '2026-06-15',
                'end_date' => '2026-06-10',// end_date sebelum start_date
                'type' => 'tahunan',
                'reason' => 'Liburan'
            ]);

        $invalidResponse->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);

        $this->assertDatabaseMissing('leave_requests', [
            'user_id' => $pegawai->id,
            'reason' => 'Liburan'
        ]);

        $validResponse = $this->actingAs($pegawai, 'sanctum')
            ->postJson('/api/leave-requests', [
                'start_date' => '2026-06-15',
                'end_date' => '2026-06-20',
                'type' => 'tahunan',
                'reason' => 'Liburan'
            ]);

        $validResponse->assertStatus(201)
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('days', 6);

        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $pegawai->id,
            'type' => 'tahunan',
            'status' => 'pending',
            'days' => 6,
            'reason' => 'Liburan'
        ]);
    }

    /**
     * INCREMENTAL STEP 7: Pegawai update request miliknya sendiri
     */
    public function test_incremental_7_pegawai_dapat_merubah_pengajuan_miliknya_sendiri()
    {
        $pegawai = User::factory()->pegawai()->create();
        $leave = LeaveRequest::factory()->create([
            'user_id' => $pegawai->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($pegawai, 'sanctum')
            ->putJson("/api/leave-requests/{$leave->id}", [
                'start_date' => '2026-06-10',
                'end_date' => '2026-06-12',
                'type' => 'sakit',
                'reason' => 'Update alasan'
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('type', 'sakit')
            ->assertJsonPath('days', 3);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'type' => 'sakit',
            'days' => 3
        ]);
    }

    //incremental pegawai tidak bisa merubah/update pengajuan pegawai lain
     public function test_incremental_7_pegawai_tidak_dapat_merubah_pengajuan_pegawai_lain()
    {
        $pegawai1 = User::factory()->pegawai()->create();
        $pegawai2 = User::factory()->pegawai()->create();

        $leave = LeaveRequest::factory()->create([
            'user_id' => $pegawai2->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($pegawai1, 'sanctum')
            ->putJson("/api/leave-requests/{$leave->id}", [
                'start_date' => '2026-06-10',
                'end_date' => '2026-06-12',
                'type' => 'sakit',
                'reason' => 'Update alasan'
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'user_id' => $pegawai2->id,
            'type' => $leave->type,
            'reason' => $leave->reason
        ]);
    }

    /**
     * INCREMENTAL STEP 8: Pegawai delete request miliknya sendiri
     */
    public function test_incremental_9_pegawai_dapat_menghapus_pengajuan_miliknya_sendiri()
    {
        $pegawai = User::factory()->pegawai()->create();
        $leave = LeaveRequest::factory()->create([
            'user_id' => $pegawai->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($pegawai, 'sanctum')
            ->deleteJson("/api/leave-requests/{$leave->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('leave_requests', ['id' => $leave->id]);
    }

    //pegawaitidak bisa menghapus pengajuan pegawai lain
    public function test_incremental_10_pegawai_tidak_dapat_menghapus_pengajuan_pegawai_lain()
    {
        $pegawai1 = User::factory()->pegawai()->create();
        $pegawai2 = User::factory()->pegawai()->create();

        $leave = LeaveRequest::factory()->create([
            'user_id' => $pegawai2->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($pegawai1, 'sanctum')
            ->deleteJson("/api/leave-requests/{$leave->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'user_id' => $pegawai2->id
        ]);
    }
}
