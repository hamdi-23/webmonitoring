<?php

namespace Tests\Feature;

use App\Jobs\CheckMonitor;
use App\Models\Monitor;
use App\Models\MonitorCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CheckMonitorsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_due_active_monitors_are_dispatched(): void
    {
        Queue::fake();

        $neverChecked = Monitor::factory()->create();
        $due = Monitor::factory()->create(['interval_minutes' => 5, 'last_checked_at' => now()->subMinutes(6)]);
        $notDue = Monitor::factory()->create(['interval_minutes' => 5, 'last_checked_at' => now()->subMinutes(2)]);
        $paused = Monitor::factory()->create(['is_active' => false]);

        $this->artisan('monitors:check')->assertSuccessful();

        Queue::assertPushed(CheckMonitor::class, 2);
        Queue::assertPushed(CheckMonitor::class, fn ($job) => $job->monitor->is($neverChecked));
        Queue::assertPushed(CheckMonitor::class, fn ($job) => $job->monitor->is($due));
        Queue::assertNotPushed(CheckMonitor::class, fn ($job) => $job->monitor->is($notDue) || $job->monitor->is($paused));
    }

    public function test_sync_option_runs_checks_immediately(): void
    {
        Http::fake(['*' => Http::response('ok')]);
        Monitor::factory()->count(2)->create();

        $this->artisan('monitors:check --sync')->assertSuccessful();

        $this->assertSame(2, MonitorCheck::count());
    }

    public function test_old_checks_are_pruned(): void
    {
        $monitor = Monitor::factory()->create();
        $monitor->checks()->create(['is_up' => true, 'checked_at' => now()->subDays(31)]);
        $monitor->checks()->create(['is_up' => true, 'checked_at' => now()->subDays(1)]);

        $this->artisan('model:prune', ['--model' => MonitorCheck::class])->assertSuccessful();

        $this->assertSame(1, MonitorCheck::count());
    }
}
