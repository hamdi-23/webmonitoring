<?php

namespace App\Models;

use App\Enums\MonitorStatus;
use Database\Factories\MonitorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'name', 'url', 'method', 'expected_status', 'keyword',
    'interval_minutes', 'timeout_seconds', 'failure_threshold', 'is_active',
])]
class Monitor extends Model
{
    /** @use HasFactory<MonitorFactory> */
    use HasFactory;

    protected $attributes = [
        'method' => 'GET',
        'interval_minutes' => 5,
        'timeout_seconds' => 10,
        'failure_threshold' => 2,
        'consecutive_failures' => 0,
        'is_active' => true,
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'status' => MonitorStatus::class,
            'status_changed_at' => 'datetime',
            'last_checked_at' => 'datetime',
            'expected_status' => 'integer',
            'interval_minutes' => 'integer',
            'timeout_seconds' => 'integer',
            'failure_threshold' => 'integer',
            'consecutive_failures' => 'integer',
            'first_failure_at' => 'datetime',
        ];
    }

    /** @return HasMany<MonitorCheck, $this> */
    public function checks(): HasMany
    {
        return $this->hasMany(MonitorCheck::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function isDue(?Carbon $now = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->last_checked_at === null) {
            return true;
        }

        $now ??= now();

        // While a failure is waiting for confirmation, re-check after a minute
        // instead of the full interval so real outages are confirmed quickly.
        $minutes = $this->isAwaitingConfirmation() ? 1 : $this->interval_minutes;

        // Allow a small margin so a scheduler tick a few seconds early still counts.
        return $this->last_checked_at->copy()->addMinutes($minutes)->subSeconds(5)->lte($now);
    }

    /**
     * The last check(s) failed but not enough times in a row to mark it down.
     */
    public function isAwaitingConfirmation(): bool
    {
        return $this->consecutive_failures > 0 && $this->status !== MonitorStatus::Down;
    }

    /**
     * Uptime percentage over the given window, or null when there is no data.
     */
    public function uptimePercentage(int $hours = 24): ?float
    {
        $stats = $this->checks()
            ->where('checked_at', '>=', now()->subHours($hours))
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_up THEN 1 ELSE 0 END) as up')
            ->first();

        if (! $stats || (int) $stats->total === 0) {
            return null;
        }

        return round(((int) $stats->up / (int) $stats->total) * 100, 2);
    }

    public function averageResponseTime(int $hours = 24): ?int
    {
        $avg = $this->checks()
            ->where('checked_at', '>=', now()->subHours($hours))
            ->where('is_up', true)
            ->avg('response_time_ms');

        return $avg === null ? null : (int) round($avg);
    }
}
