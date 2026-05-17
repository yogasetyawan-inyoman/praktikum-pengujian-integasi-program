<?php

namespace Database\Factories;

use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $start = Carbon::now()->addDays(5);
        $end = $start->copy()->addDays(3);

        return [
            'user_id' => User::factory()->pegawai(),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'days' => 4,
            'type' => $this->faker->randomElement(['tahunan', 'sakit', 'bersalin']),
            'reason' => $this->faker->sentence(),
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
        ];
    }

    public function approved()
    {
        return $this->state([
            'status' => 'approved',
            'approved_by' => User::factory()->atasan(),
            'approved_at' => now(),
        ]);
    }

    public function rejected()
    {
        return $this->state([
            'status' => 'rejected',
            'approved_by' => User::factory()->atasan(),
            'approved_at' => now(),
        ]);
    }
}
