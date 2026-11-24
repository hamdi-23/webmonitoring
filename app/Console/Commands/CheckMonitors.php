<?php

namespace App\Console\Commands;

use App\Jobs\CheckMonitor;
use App\Models\Monitor;
use App\Services\MonitorChecker;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('monitors:check {--all : Cek semua monitor aktif, abaikan interval} {--sync : Jalankan langsung tanpa queue}')]
#[Description('Cek website yang sudah waktunya dimonitor')]
class CheckMonitors extends Command
{
    public function handle(MonitorChecker $checker): int
    {
        $monitors = Monitor::active()->get()
            ->when(! $this->option('all'), fn ($c) => $c->filter->isDue());

        foreach ($monitors as $monitor) {
            if ($this->option('sync')) {
                $check = $checker->check($monitor);
                $this->line(sprintf('%-4s %s (%s)', $check->is_up ? 'UP' : 'DOWN', $monitor->url,
                    $check->error ?? $check->response_time_ms.' ms'));
            } else {
                CheckMonitor::dispatch($monitor);
            }
        }

        $this->info("{$monitors->count()} monitor diproses.");

        return self::SUCCESS;
    }
}
