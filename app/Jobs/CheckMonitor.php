<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Services\MonitorChecker;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckMonitor implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public Monitor $monitor) {}

    public function uniqueId(): string
    {
        return (string) $this->monitor->id;
    }

    public function uniqueFor(): int
    {
        return 120;
    }

    public function handle(MonitorChecker $checker): void
    {
        $checker->check($this->monitor);
    }
}
