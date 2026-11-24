<?php

namespace Database\Factories;

use App\Models\Monitor;
use App\Models\MonitorCheck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonitorCheck>
 */
class MonitorCheckFactory extends Factory
{
    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::factory(),
            'is_up' => true,
            'status_code' => 200,
            'response_time_ms' => fake()->numberBetween(80, 900),
            'checked_at' => now(),
        ];
    }
}
