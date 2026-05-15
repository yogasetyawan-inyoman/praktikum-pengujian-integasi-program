<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\LeaveRequest;

class LeaveRequestUnauthorizedTest extends TestCase
{
    public function test_guest_cannot_access_index()
    {
        $this->getJson('/api/leave-requests')->assertStatus(401);
    }

    public function test_guest_cannot_submit_leave_request()
    {
        $payload = [
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-03',
            'type' => 'tahunan',
            'reason' => 'Liburan'
        ];
        $this->postJson('/api/leave-requests', $payload)->assertStatus(401);
    }

    public function test_guest_cannot_view_single_request()
    {
        $leave = LeaveRequest::factory()->create();
        $this->getJson("/api/leave-requests/{$leave->id}")->assertStatus(401);
    }

    public function test_guest_cannot_update_request()
    {
        $leave = LeaveRequest::factory()->create();
        $this->putJson("/api/leave-requests/{$leave->id}", [
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-12',
            'type' => 'sakit',
            'reason' => 'Update'
        ])->assertStatus(401);
    }

    public function test_guest_cannot_delete_request()
    {
        $leave = LeaveRequest::factory()->create();
        $this->deleteJson("/api/leave-requests/{$leave->id}")->assertStatus(401);
    }

    public function test_guest_cannot_approve_or_reject()
    {
        $leave = LeaveRequest::factory()->create();
        $this->postJson("/api/leave-requests/{$leave->id}/approve")->assertStatus(401);
        $this->postJson("/api/leave-requests/{$leave->id}/reject")->assertStatus(401);
    }
}