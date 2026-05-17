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
        $pegawai = User::factory()->pegawai()->create();//membuat user dengan role pegawai untuk keperluan testing.

        $leave = LeaveRequest::factory()->create(['user_id' => $pegawai->id]);//membuat data pengajuan cuti untuk pegawai yang sedang login.

        $response = $this->actingAs($pegawai, 'sanctum')//mengautentikasi sebagai pegawai yang sudah dibuat sebelumnya.
            ->getJson("/api/leave-requests/{$leave->id}");//mengirim request GET dalam format JSON untuk mendapatkan detail pengajuan cuti berdasarkan ID pengajuan.

        $response->assertStatus(200)//memastikan response HTTP 200 OK.
            ->assertJsonPath('id', $leave->id)//memastikan response JSON memiliki field id yang sesuai dengan ID pengajuan yang diminta.
            ->assertJsonPath('user_id', $pegawai->id);//memastikan response JSON memiliki field user_id yang sesuai dengan ID pegawai yang sedang login.
    }

    /**
     * INCREMENTAL STEP 4: Atasan melihat pending request dan approve
     */
    public function test_incremental_4_atasan_dapat_melihat_dan_menyetujui_pengajuan()
    {
        $pegawai = User::factory()->pegawai()->create();//membuat user dengan role pegawai untuk keperluan testing.
        $atasan = User::factory()->atasan()->create();//membuat user dengan role atasan untuk keperluan testing.

        $leave = LeaveRequest::factory()->create([//membuat data pengajuan cuti untuk pegawai yang sedang login dengan status pending.
            'user_id' => $pegawai->id,//menentukan user_id sesuai dengan ID pegawai yang sedang login.
            'status' => 'pending'//menentukan status pengajuan sebagai pending untuk memastikan atasan dapat melihatnya di daftar pending request.
        ]);

        $viewResponse = $this->actingAs($atasan, 'sanctum')//mengautentikasi sebagai atasan yang sudah dibuat sebelumnya.
            ->getJson('/api/leave-requests');//mengirim request GET dalam format JSON untuk mendapatkan daftar pengajuan cuti yang pending.

        $viewResponse->assertStatus(200)//memastikan response HTTP 200 OK.
            ->assertJsonCount(1)//memastikan response JSON berupa array dengan jumlah elemen 1 (hanya pengajuan yang pending).
            ->assertJsonPath('0.status', 'pending')//memastikan elemen pertama dalam array memiliki field status dengan nilai pending.
            ->assertJsonPath('0.user_id', $pegawai->id);//memastikan elemen pertama dalam array memiliki field user_id yang sesuai dengan ID pegawai yang sedang login.

        $approveResponse = $this->actingAs($atasan, 'sanctum')//mengautentikasi sebagai atasan yang sudah dibuat sebelumnya.
            ->postJson("/api/leave-requests/{$leave->id}/approve");//mengirim request POST dalam format JSON untuk menyetujui pengajuan cuti berdasarkan ID pengajuan.

        $approveResponse->assertStatus(200)//memastikan response HTTP 200 OK.
            ->assertJsonPath('status', 'approved')//memastikan response JSON memiliki field status dengan nilai approved setelah disetujui.
            ->assertJsonPath('approved_by', $atasan->id);//memastikan response JSON memiliki field approved_by yang sesuai dengan ID atasan yang menyetujui.

        $this->assertDatabaseHas('leave_requests', [//memastikan database memiliki record di tabel leave_requests dengan data yang sesuai setelah disetujui.
            'id' => $leave->id,//menentukan ID pengajuan yang sama untuk memastikan record yang diperiksa adalah pengajuan yang disetujui.
            'status' => 'approved',//menentukan status pengajuan sebagai approved untuk memastikan perubahan status sudah tercatat di database.
            'approved_by' => $atasan->id//menentukan approved_by sesuai dengan ID atasan yang menyetujui untuk memastikan informasi persetujuan sudah tercatat di database.
        ]);
    }
    /**
     * INCREMENTAL STEP 5: Atasan reject request
     */
    public function test_incremental_5_atasan_dapat_menolak_pengajuan()
    {
        $pegawai = User::factory()->pegawai()->create();//membuat user dengan role pegawai untuk keperluan testing.
        $atasan = User::factory()->atasan()->create();//membuat user dengan role atasan untuk keperluan testing.

        $leave = LeaveRequest::factory()->create([//membuat data pengajuan cuti untuk pegawai yang sedang login dengan status pending.
            'user_id' => $pegawai->id,//menentukan user_id sesuai dengan ID pegawai yang sedang login.
            'status' => 'pending'//menentukan status pengajuan sebagai pending untuk memastikan atasan dapat melihatnya di daftar pending request.
        ]);

        $response = $this->actingAs($atasan, 'sanctum')//mengautentikasi sebagai atasan yang sudah dibuat sebelumnya.
            ->postJson("/api/leave-requests/{$leave->id}/reject");//mengirim request POST dalam format JSON untuk menolak pengajuan cuti berdasarkan ID pengajuan.

        $response->assertStatus(200)//memastikan response HTTP 200 OK.
            ->assertJsonPath('status', 'rejected')//memastikan response JSON memiliki field status dengan nilai rejected setelah ditolak.
            ->assertJsonPath('approved_by', $atasan->id);//memastikan response JSON memiliki field approved_by yang sesuai dengan ID atasan yang menolak.

        $this->assertDatabaseHas('leave_requests', [//memastikan database memiliki record di tabel leave_requests dengan data yang sesuai setelah ditolak.
            'id' => $leave->id,//menentukan ID pengajuan yang sama untuk memastikan record yang diperiksa adalah pengajuan yang ditolak.
            'status' => 'rejected',//menentukan status pengajuan sebagai rejected untuk memastikan perubahan status sudah tercatat di database.
            'approved_by' => $atasan->id//menentukan approved_by sesuai dengan ID atasan yang menolak untuk memastikan informasi penolakan sudah tercatat di database.
        ]);
    }


    //test incremental pegawai tidak bisa approve dan reject pengajuan cuti
    public function test_incremental_4_5_pegawai_tidak_dapat_menyetujui_atau_menolak_pengajuan()
    {
        $pegawai = User::factory()->pegawai()->create();//membuat user dengan role pegawai untuk keperluan testing.

        $leave = LeaveRequest::factory()->create([//membuat data pengajuan cuti untuk pegawai yang sedang login dengan status pending.
            'user_id' => $pegawai->id,//menentukan user_id sesuai dengan ID pegawai yang sedang login.
            'status' => 'pending'//menentukan status pengajuan sebagai pending untuk memastikan pegawai dapat melihatnya di daftar pending request.
        ]);

        $approveResponse = $this->actingAs($pegawai, 'sanctum')//mengautentikasi sebagai pegawai yang sudah dibuat sebelumnya.
            ->postJson("/api/leave-requests/{$leave->id}/approve");//mengirim request POST dalam format JSON untuk mencoba menyetujui pengajuan cuti berdasarkan ID pengajuan.

        $approveResponse->assertStatus(403);//memastikan response HTTP 403 Forbidden yang menandakan pegawai tidak memiliki izin untuk menyetujui pengajuan.

        $rejectResponse = $this->actingAs($pegawai, 'sanctum')//mengautentikasi sebagai pegawai yang sudah dibuat sebelumnya.
            ->postJson("/api/leave-requests/{$leave->id}/reject");//mengirim request POST dalam format JSON untuk mencoba menolak pengajuan cuti berdasarkan ID pengajuan.

        $rejectResponse->assertStatus(403);//memastikan response HTTP 403 Forbidden yang menandakan pegawai tidak memiliki izin untuk menolak pengajuan.
        $this->assertDatabaseHas('leave_requests', [//memastikan database memiliki record di tabel leave_requests dengan data yang sesuai untuk memastikan status pengajuan tidak berubah.
            'id' => $leave->id,//menentukan ID pengajuan yang sama untuk memastikan record yang diperiksa adalah pengajuan yang tidak boleh diubah.
            'status' => 'pending'//menentukan status pengajuan sebagai pending untuk memastikan record yang diperiksa adalah pengajuan yang tidak boleh diubah.
        ]);
    }

    /**
     * INCREMENTAL STEP 6: Validasi error lalu retry
     */
    public function test_incremental_6_validasi_gagal_dan_mengulang()
    {
        $pegawai = User::factory()->pegawai()->create();//membuat user dengan role pegawai untuk keperluan testing.

        $invalidResponse = $this->actingAs($pegawai, 'sanctum')//mengautentikasi sebagai pegawai yang sudah dibuat sebelumnya.
            ->postJson('/api/leave-requests', [//mengirim request POST dalam format JSON dengan data yang tidak valid (end_date sebelum start_date).
                'start_date' => '2026-06-15',//menentukan start_date dengan tanggal yang lebih akhir.
                'end_date' => '2026-06-10',//menentukan end_date dengan tanggal yang lebih awal untuk memicu error validasi.
                'type' => 'tahunan',//menentukan type dengan nilai yang valid untuk memastikan error yang terjadi hanya karena end_date yang tidak valid.
                'reason' => 'Liburan'//menentukan reason dengan nilai yang valid untuk memastikan error yang terjadi hanya karena end_date yang tidak valid.
            ]);

        $invalidResponse->assertStatus(422)//memastikan response HTTP 422 Unprocessable Entity yang menandakan error validasi.
            ->assertJsonValidationErrors(['end_date']);//memastikan response JSON memiliki error validasi untuk field end_date.

        $this->assertDatabaseMissing('leave_requests', [//memastikan database tidak memiliki record di tabel leave_requests dengan data yang tidak valid.
            'user_id' => $pegawai->id,//menentukan user_id sesuai dengan ID pegawai yang sedang login untuk memastikan record yang diperiksa adalah record yang tidak valid.
            'type' => 'tahunan',//menentukan type dengan nilai yang valid untuk memastikan record yang diperiksa adalah record yang tidak valid.
            'reason' => 'Liburan'//menentukan reason dengan nilai yang valid untuk memastikan record yang diperiksa adalah record yang tidak valid.
        ]);

        $validResponse = $this->actingAs($pegawai, 'sanctum')//mengautentikasi sebagai pegawai yang sudah dibuat sebelumnya.
            ->postJson('/api/leave-requests', [//mengirim request POST dalam format JSON dengan data yang sudah diperbaiki dan valid.
                'start_date' => '2026-06-15',//menentukan start_date dengan tanggal yang valid.
                'end_date' => '2026-06-20',//menentukan end_date dengan tanggal yang valid dan setelah start_date untuk memastikan data sudah valid.
                'type' => 'tahunan',//menentukan type dengan nilai yang valid untuk memastikan data sudah valid.
                'reason' => 'Liburan'//menentukan reason dengan nilai yang valid untuk memastikan data sudah valid.
            ]);

        $validResponse->assertStatus(201)//memastikan response HTTP 201 Created yang menandakan pengajuan berhasil dibuat setelah data diperbaiki.
            ->assertJsonPath('status', 'pending')//memastikan response JSON memiliki field status dengan nilai pending setelah pengajuan berhasil dibuat.
            ->assertJsonPath('days', 6);//memastikan response JSON memiliki field days dengan nilai 6 (selisih antara end_date dan start_date) setelah pengajuan berhasil dibuat.

        $this->assertDatabaseHas('leave_requests', [//memastikan database memiliki record di tabel leave_requests dengan data yang sudah diperbaiki dan valid.
            'user_id' => $pegawai->id,//menentukan user_id sesuai dengan ID pegawai yang sedang login untuk memastikan record yang diperiksa adalah record yang sudah valid.
            'type' => 'tahunan',//menentukan type dengan nilai yang valid untuk memastikan record yang diperiksa adalah record yang sudah valid.
            'status' => 'pending',//menentukan status pengajuan sebagai pending untuk memastikan record yang diperiksa adalah record yang sudah valid.
            'days' => 6,//menentukan days dengan nilai yang sesuai untuk memastikan record yang diperiksa adalah record yang sudah valid.
            'reason' => 'Liburan'//menentukan reason dengan nilai yang valid untuk memastikan record yang diperiksa adalah record yang sudah valid.
        ]);
    }

    /**
     * INCREMENTAL STEP 7: Pegawai update request miliknya sendiri
     */
    public function test_incremental_7_pegawai_dapat_merubah_pengajuan_miliknya_sendiri()
    {
        $pegawai = User::factory()->pegawai()->create();//membuat user dengan role pegawai untuk keperluan testing.
        $leave = LeaveRequest::factory()->create([//membuat data pengajuan cuti untuk pegawai yang sedang login dengan status pending melalui factory.
            'user_id' => $pegawai->id,//menentukan user_id sesuai dengan ID pegawai yang sedang login.
            'status' => 'pending'//menentukan status pengajuan sebagai pending untuk memastikan pegawai dapat mengubahnya.
        ]);

        $response = $this->actingAs($pegawai, 'sanctum')//mengautentikasi sebagai pegawai yang sudah dibuat sebelumnya.
            ->putJson("/api/leave-requests/{$leave->id}", [//mengirim request PUT dalam format JSON untuk mengubah pengajuan cuti berdasarkan ID pengajuan dengan data yang sudah diperbarui.
                'start_date' => '2026-06-10',
                'end_date' => '2026-06-12',
                'type' => 'sakit',
                'reason' => 'Update alasan'
            ]);

        $response->assertStatus(200)//memastikan response HTTP 200 OK yang menandakan pengajuan berhasil diubah.
            ->assertJsonPath('type', 'sakit')//memastikan response JSON memiliki field type dengan nilai sakit setelah diubah.
            ->assertJsonPath('days', 3);//memastikan response JSON memiliki field days dengan nilai 3 (selisih antara end_date dan start_date) setelah diubah.

        $this->assertDatabaseHas('leave_requests', [//memastikan database memiliki record di tabel leave_requests dengan data yang sudah diperbarui.
            'id' => $leave->id,//menentukan ID pengajuan yang sama untuk memastikan record yang diperiksa adalah pengajuan yang sudah diubah.
            'type' => 'sakit',//menentukan type dengan nilai sakit untuk memastikan record yang diperiksa adalah pengajuan yang sudah diubah.
            'days' => 3//menentukan days dengan nilai 3 untuk memastikan record yang diperiksa adalah pengajuan yang sudah diubah.
        ]);
    }

    //incremental pegawai tidak bisa merubah/update pengajuan pegawai lain
     public function test_incremental_7_pegawai_tidak_dapat_merubah_pengajuan_pegawai_lain()
    {
        $pegawai1 = User::factory()->pegawai()->create();//membuat user dengan role pegawai untuk keperluan testing.
        $pegawai2 = User::factory()->pegawai()->create();//membuat user lain dengan role pegawai untuk memastikan pegawai pertama tidak bisa mengubah pengajuan milik pegawai kedua.

        $leave = LeaveRequest::factory()->create([//membuat data pengajuan cuti untuk pegawai kedua dengan status pending melalui factory.
            'user_id' => $pegawai2->id,//menentukan user_id sesuai dengan ID pegawai kedua untuk memastikan data yang dibuat adalah pengajuan milik pegawai kedua.
            'status' => 'pending'
        ]);

        $response = $this->actingAs($pegawai1, 'sanctum')//mengautentikasi sebagai pegawai pertama yang sudah dibuat sebelumnya.
            ->putJson("/api/leave-requests/{$leave->id}", [//mengirim request PUT dalam format JSON untuk mencoba mengubah pengajuan cuti milik pegawai kedua berdasarkan ID pengajuan dengan data yang sudah diperbarui.
                'start_date' => '2026-06-10',
                'end_date' => '2026-06-12',
                'type' => 'sakit',
                'reason' => 'Update alasan'
            ]);

        $response->assertStatus(403);//memastikan response HTTP 403 Forbidden yang menandakan pegawai pertama tidak memiliki izin untuk mengubah pengajuan milik pegawai kedua.

        $this->assertDatabaseHas('leave_requests', [//memastikan database memiliki record di tabel leave_requests dengan data yang sesuai untuk memastikan pengajuan milik pegawai kedua tidak berubah.
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
        $pegawai = User::factory()->pegawai()->create();//membuat user dengan role pegawai untuk keperluan testing.
        $leave = LeaveRequest::factory()->create([//membuat data pengajuan cuti untuk pegawai yang sedang login dengan status pending melalui factory.
            'user_id' => $pegawai->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($pegawai, 'sanctum')//mengautentikasi sebagai pegawai yang sudah dibuat sebelumnya.
            ->deleteJson("/api/leave-requests/{$leave->id}");//mengirim request DELETE dalam format JSON untuk menghapus pengajuan cuti berdasarkan ID pengajuan.

        $response->assertStatus(200);//memastikan response HTTP 200 OK yang menandakan pengajuan berhasil dihapus.

        $this->assertDatabaseMissing('leave_requests', ['id' => $leave->id]);//memastikan database tidak memiliki record di tabel leave_requests dengan ID pengajuan yang sudah dihapus untuk memastikan pengajuan benar-benar dihapus dari database.
    }

    //pegawaitidak bisa menghapus pengajuan pegawai lain
    public function test_incremental_10_pegawai_tidak_dapat_menghapus_pengajuan_pegawai_lain()
    {
        $pegawai1 = User::factory()->pegawai()->create();//membuat user dengan role pegawai untuk keperluan testing.
        $pegawai2 = User::factory()->pegawai()->create();//membuat user lain dengan role pegawai untuk memastikan pegawai pertama tidak bisa menghapus pengajuan milik pegawai kedua.

        $leave = LeaveRequest::factory()->create([//membuat data pengajuan cuti untuk pegawai kedua dengan status pending melalui factory.
            'user_id' => $pegawai2->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($pegawai1, 'sanctum')//mengautentikasi sebagai pegawai pertama yang sudah dibuat sebelumnya.
            ->deleteJson("/api/leave-requests/{$leave->id}");//mengirim request DELETE dalam format JSON untuk mencoba menghapus pengajuan cuti milik pegawai kedua berdasarkan ID pengajuan.

        $response->assertStatus(403);//memastikan response HTTP 403 Forbidden yang menandakan pegawai pertama tidak memiliki izin untuk menghapus pengajuan milik pegawai kedua.

        $this->assertDatabaseHas('leave_requests', [//memastikan database memiliki record di tabel leave_requests dengan data yang sesuai untuk memastikan pengajuan milik pegawai kedua tidak terhapus.
            'id' => $leave->id,//menentukan ID pengajuan yang sama untuk memastikan record yang diperiksa adalah pengajuan yang tidak boleh dihapus.
            'user_id' => $pegawai2->id//menentukan user_id sesuai dengan ID pegawai kedua untuk memastikan record yang diperiksa adalah pengajuan milik pegawai kedua.
        ]);
    }
}
