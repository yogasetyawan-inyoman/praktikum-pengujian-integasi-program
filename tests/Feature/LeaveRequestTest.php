<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use App\Models\LeaveRequest;
use Carbon\Carbon;

class LeaveRequestTest extends TestCase
{
    /**
     * Skenario 1: Pegawai submit pengajuan cuti
     */
    public function test_pegawai_can_submit_leave_request()
    {
        $pegawai = User::factory()->pegawai()->create();

        $response = $this->actingAs($pegawai, 'sanctum')
            ->postJson('/api/leave-requests', [
                'start_date' => '2026-06-01',
                'end_date' => '2026-05-03',
                'type' => 'tahunan',
                'reason' => 'Liburan keluarga'
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('user_id', $pegawai->id);

        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $pegawai->id,
            'type' => 'tahunan',
            'status' => 'pending',
            'days' => 3
        ]);
    }

    /**
     * Skenario 2: Validasi error - tanggal invalid
     */
    public function test_submit_leave_fails_with_invalid_dates()
    {
        $pegawai = User::factory()->pegawai()->create();

        $response = $this->actingAs($pegawai, 'sanctum')
            ->postJson('/api/leave-requests', [
                'start_date' => '2026-06-03',
                'end_date' => '2026-06-01', // end date < start date
                'type' => 'tahunan',
                'reason' => 'Test'
            ]);
        //$response->dump();
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    /**
     * Skenario 3: Hanya pegawai & admin yang bisa submit
     */
    public function test_atasan_cannot_submit_leave_request()
    {
        $atasan = User::factory()->atasan()->create();

        $response = $this->actingAs($atasan, 'sanctum')
            ->postJson('/api/leave-requests', [
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-03',
                'type' => 'tahunan',
                'reason' => 'Test'
            ]);

        $response->assertStatus(403);
    }

    /**
     * Skenario 4: Pegawai lihat daftar pengajuannya sendiri
     */
    public function test_pegawai_can_view_own_leave_requests()
    {
        $pegawai = User::factory()->pegawai()->create();
        $other_pegawai = User::factory()->pegawai()->create();

        LeaveRequest::factory()->create(['user_id' => $pegawai->id]);
        LeaveRequest::factory()->create(['user_id' => $pegawai->id]);
        LeaveRequest::factory()->create(['user_id' => $other_pegawai->id]);

        $response = $this->actingAs($pegawai, 'sanctum')
            ->getJson('/api/leave-requests');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    /**
     * Skenario 5: Atasan lihat hanya pengajuan pending
     */
    public function test_atasan_sees_only_pending_requests()
    {
        $atasan = User::factory()->atasan()->create();
        $pegawai = User::factory()->pegawai()->create();

        LeaveRequest::factory()->create(['user_id' => $pegawai->id, 'status' => 'pending']);
        LeaveRequest::factory()->create(['user_id' => $pegawai->id, 'status' => 'approved']);
        LeaveRequest::factory()->create(['user_id' => $pegawai->id, 'status' => 'rejected']);

        $response = $this->actingAs($atasan, 'sanctum')
            ->getJson('/api/leave-requests');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.status', 'pending');
    }

    /**
     * Skenario 6: Admin lihat semua pengajuan
     */
    public function test_admin_sees_all_leave_requests()
    {
        $admin = User::factory()->admin()->create();
        $pegawai1 = User::factory()->pegawai()->create();
        $pegawai2 = User::factory()->pegawai()->create();

        LeaveRequest::factory()->create(['user_id' => $pegawai1->id, 'status' => 'pending']);
        LeaveRequest::factory()->create(['user_id' => $pegawai2->id, 'status' => 'approved']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/leave-requests');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    /**
     * Skenario 7: Atasan approve pengajuan
     */
    public function test_atasan_can_approve_leave_request()
    {
        $atasan = User::factory()->atasan()->create();
        $pegawai = User::factory()->pegawai()->create();
        $leave = LeaveRequest::factory()->create([
            'user_id' => $pegawai->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($atasan, 'sanctum')
            ->postJson("/api/leave-requests/{$leave->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'approved')
            ->assertJsonPath('approved_by', $atasan->id);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'status' => 'approved',
            'approved_by' => $atasan->id
        ]);
    }

    /**
     * Skenario 8: Atasan reject pengajuan
     */
    public function test_atasan_can_reject_leave_request()
    {
        $atasan = User::factory()->atasan()->create();
        $leave = LeaveRequest::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($atasan, 'sanctum')
            ->postJson("/api/leave-requests/{$leave->id}/reject");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'rejected');
    }

    /**
     * Skenario 9: Pegawai tidak bisa approve/reject
     */
    public function test_pegawai_cannot_approve_leave_request()
    {
        $pegawai = User::factory()->pegawai()->create();
        $leave = LeaveRequest::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($pegawai, 'sanctum')
            ->postJson("/api/leave-requests/{$leave->id}/approve");

        $response->assertStatus(403);
    }

    /**
     * Skenario 10b: Pegawai lihat detail pengajuan miliknya sendiri
     */
    public function test_pegawai_can_view_single_own_leave_request()
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
     * Skenario 10c: Detail pengajuan yang tidak ada mengembalikan 404
     */
    public function test_show_returns_404_if_leave_request_not_found()
    {
        $pegawai = User::factory()->pegawai()->create();

        $response = $this->actingAs($pegawai, 'sanctum')
            ->getJson('/api/leave-requests/999999');

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Pengajuan cuti tidak ditemukan');
    }

    /**
     * Skenario 10d: Pegawai update pengajuan miliknya sendiri
     */
    public function test_pegawai_can_update_own_request()
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

    /**
     * Skenario 10e: Pegawai tidak bisa update pengajuan milik orang lain
     */
    public function test_pegawai_cannot_update_others_request()
    {
        $pegawai1 = User::factory()->pegawai()->create();
        $pegawai2 = User::factory()->pegawai()->create();
        $leave = LeaveRequest::factory()->create(['user_id' => $pegawai1->id]);

        $response = $this->actingAs($pegawai2, 'sanctum')
            ->putJson("/api/leave-requests/{$leave->id}", [
                'start_date' => '2026-06-10',
                'end_date' => '2026-06-12',
                'type' => 'sakit',
                'reason' => 'Update alasan'
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Forbidden');
    }

    /**
     * Skenario 10: Pegawai hapus pengajuan miliknya (sebelum approved)
     */
    public function test_pegawai_can_delete_own_pending_request()
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

    /**
     * Skenario 11: Pegawai tidak bisa hapus pengajuan orang lain
     */
    public function test_pegawai_cannot_delete_others_request()
    {
        $pegawai1 = User::factory()->pegawai()->create();
        $pegawai2 = User::factory()->pegawai()->create();
        $leave = LeaveRequest::factory()->create(['user_id' => $pegawai1->id]);

        $response = $this->actingAs($pegawai2, 'sanctum')
            ->deleteJson("/api/leave-requests/{$leave->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('leave_requests', ['id' => $leave->id]);
    }

    /**
     * Skenario 12: Perhitungan hari cuti otomatis
     */
    public function test_days_calculated_correctly()
    {
        $pegawai = User::factory()->pegawai()->create();

        $response = $this->actingAs($pegawai, 'sanctum')
            ->postJson('/api/leave-requests', [
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-05', // 5 hari
                'type' => 'tahunan',
                'reason' => 'Test'
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('days', 5);
    }
}