<?php

namespace App\Models;

use Database\Factories\MonitorCheckFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['is_up', 'status_code', 'response_time_ms', 'error', 'checked_at'])]
#[WithoutTimestamps]
class MonitorCheck extends Model
{
    /** @use HasFactory<MonitorCheckFactory> */
    use HasFactory, Prunable;

    protected function casts(): array
    {
        return [
            'is_up' => 'boolean',
            'checked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Monitor, $this> */
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    /**
     * Check history older than the retention window is removed by `model:prune`.
     */
    public function prunable(): Builder
    {
        return static::where('checked_at', '<', now()->subDays((int) config('monitoring.retention_days')));
    }
}
