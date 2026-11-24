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
    'interval_minutes', 'timeout_seconds', 'is_active',
])]
class Monitor extends Model
{
    /** @use HasFactory<MonitorFactory> */
    use HasFactory;

    protected $attributes = [
        'method' => 'GET',
        'interval_minutes' => 5,
        'timeout_seconds' => 10,
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

        // Allow a small margin so a scheduler tick a few seconds early still counts.
        return $this->last_checked_at->copy()->addMinutes($this->interval_minutes)->subSeconds(5)->lte($now);
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
