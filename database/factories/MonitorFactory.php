<?php

namespace Database\Factories;

use App\Enums\MonitorStatus;
use App\Models\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Monitor>
 */
class MonitorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'url' => 'https://'.fake()->unique()->domainName(),
            'method' => 'GET',
            'interval_minutes' => 5,
            'timeout_seconds' => 10,
            'is_active' => true,
        ];
    }

    public function up(): static
    {
        return $this->state(fn () => [
            'status' => MonitorStatus::Up,
            'last_checked_at' => now(),
            'last_status_code' => 200,
            'last_response_time_ms' => fake()->numberBetween(80, 900),
        ]);
    }

    public function down(): static
    {
        return $this->state(fn () => [
            'status' => MonitorStatus::Down,
            'last_checked_at' => now(),
            'last_status_code' => 500,
            'last_error' => 'Status 500',
        ]);
    }
}
