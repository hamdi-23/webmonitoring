<?php

namespace Tests\Feature;

use App\Enums\MonitorStatus;
use App\Models\Monitor;
use App\Models\User;
use App\Notifications\MonitorStatusChanged;
use App\Services\MonitorChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FailureThresholdTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->user = User::factory()->create();
    }

    public function test_monitor_is_marked_down_only_after_threshold_is_reached(): void
    {
        Http::fake(['*' => Http::response('', 500)]);
        $monitor = Monitor::factory()->up()->create(['failure_threshold' => 3]);
        $checker = app(MonitorChecker::class);

        $checker->check($monitor);
        $this->travel(1)->minutes();
        $checker->check($monitor);

        $this->assertSame(MonitorStatus::Up, $monitor->fresh()->status);
        $this->assertSame(2, $monitor->fresh()->consecutive_failures);
        $this->assertTrue($monitor->fresh()->isAwaitingConfirmation());
        Notification::assertNothingSent();

        $this->travel(1)->minutes();
        $checker->check($monitor);

        $monitor->refresh();
        $this->assertSame(MonitorStatus::Down, $monitor->status);
        $this->assertFalse($monitor->isAwaitingConfirmation());
        // Downtime is counted from the first failure, not from confirmation.
        $this->assertTrue($monitor->status_changed_at->equalTo($monitor->first_failure_at));
        $this->assertTrue($monitor->status_changed_at->lt(now()->subMinute()));
        Notification::assertSentToTimes($this->user, MonitorStatusChanged::class, 1);

        // Further failures don't send more notifications.
        $this->travel(5)->minutes();
        $checker->check($monitor);
        Notification::assertSentToTimes($this->user, MonitorStatusChanged::class, 1);
    }

    public function test_a_single_blip_resets_without_any_notification(): void
    {
        $monitor = Monitor::factory()->up()->create(['failure_threshold' => 2]);
        $checker = app(MonitorChecker::class);

        Http::fake(['*' => Http::sequence()->push('', 500)->push('ok', 200)]);

        $checker->check($monitor);
        $this->assertSame(1, $monitor->fresh()->consecutive_failures);

        $checker->check($monitor);
        $monitor->refresh();

        $this->assertSame(MonitorStatus::Up, $monitor->status);
        $this->assertSame(0, $monitor->consecutive_failures);
        $this->assertNull($monitor->first_failure_at);
        $this->assertSame(1, $monitor->checks()->where('is_up', false)->count());
        Notification::assertNothingSent();
    }

    public function test_recovery_is_immediate_and_notifies(): void
    {
        Http::fake(['*' => Http::response('ok')]);
        $monitor = Monitor::factory()->down()->create(['failure_threshold' => 3, 'consecutive_failures' => 5]);

        app(MonitorChecker::class)->check($monitor);

        $this->assertSame(MonitorStatus::Up, $monitor->fresh()->status);
        Notification::assertSentTo($this->user, MonitorStatusChanged::class, fn ($n) => $n->status === MonitorStatus::Up);
    }

    public function test_new_monitor_stays_pending_until_failure_is_confirmed(): void
    {
        Http::fake(['*' => Http::response('', 503)]);
        $monitor = Monitor::factory()->create(['failure_threshold' => 2]);

        app(MonitorChecker::class)->check($monitor);

        $this->assertSame(MonitorStatus::Pending, $monitor->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_failing_monitor_is_rechecked_after_one_minute(): void
    {
        $failing = Monitor::factory()->up()->create([
            'interval_minutes' => 30, 'failure_threshold' => 2,
            'consecutive_failures' => 1, 'last_checked_at' => now()->subMinute(),
        ]);
        $healthy = Monitor::factory()->up()->create(['interval_minutes' => 30, 'last_checked_at' => now()->subMinute()]);
        $confirmedDown = Monitor::factory()->down()->create(['interval_minutes' => 30, 'last_checked_at' => now()->subMinute()]);

        $this->assertTrue($failing->isDue());
        $this->assertFalse($healthy->isDue());
        $this->assertFalse($confirmedDown->isDue());
    }
}
