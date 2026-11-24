<?php

namespace Tests\Feature;

use App\Enums\MonitorStatus;
use App\Models\Monitor;
use App\Services\MonitorChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MonitorCheckerTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_response_marks_monitor_up(): void
    {
        Http::fake(['example.com/*' => Http::response('ok', 200)]);
        $monitor = Monitor::factory()->create(['url' => 'https://example.com/health']);

        $check = app(MonitorChecker::class)->check($monitor);

        $this->assertTrue($check->is_up);
        $this->assertSame(200, $check->status_code);
        $this->assertNotNull($check->response_time_ms);
        $monitor->refresh();
        $this->assertSame(MonitorStatus::Up, $monitor->status);
        $this->assertNotNull($monitor->last_checked_at);
        $this->assertNotNull($monitor->status_changed_at);
    }

    public function test_server_error_marks_monitor_down(): void
    {
        Http::fake(['*' => Http::response('boom', 503)]);
        $monitor = Monitor::factory()->create();

        $check = app(MonitorChecker::class)->check($monitor);

        $this->assertFalse($check->is_up);
        $this->assertSame('Status 503', $check->error);
        $this->assertSame(MonitorStatus::Down, $monitor->fresh()->status);
    }

    public function test_expected_status_must_match(): void
    {
        Http::fake(['*' => Http::response('', 301)]);
        $monitor = Monitor::factory()->create(['expected_status' => 200]);

        $check = app(MonitorChecker::class)->check($monitor);

        $this->assertFalse($check->is_up);
        $this->assertStringContainsString('diharapkan 200', $check->error);
    }

    public function test_missing_keyword_marks_monitor_down(): void
    {
        Http::fake(['*' => Http::response('<h1>Maintenance</h1>', 200)]);
        $monitor = Monitor::factory()->create(['keyword' => 'Selamat datang']);

        $check = app(MonitorChecker::class)->check($monitor);

        $this->assertFalse($check->is_up);
        $this->assertStringContainsString('Selamat datang', $check->error);
    }

    public function test_connection_failure_marks_monitor_down(): void
    {
        Http::fake(fn () => throw new ConnectionException('Could not resolve host'));
        $monitor = Monitor::factory()->create();

        $check = app(MonitorChecker::class)->check($monitor);

        $this->assertFalse($check->is_up);
        $this->assertNull($check->status_code);
        $this->assertStringContainsString('Could not resolve host', $check->error);
    }

    public function test_status_changed_at_only_updates_on_transition(): void
    {
        Http::fake(['*' => Http::response('ok')]);
        $monitor = Monitor::factory()->create();
        $checker = app(MonitorChecker::class);

        $checker->check($monitor);
        $firstChange = $monitor->fresh()->status_changed_at;

        $this->travel(5)->minutes();
        $checker->check($monitor);

        $this->assertTrue($firstChange->equalTo($monitor->fresh()->status_changed_at));
    }

    public function test_uptime_percentage(): void
    {
        $monitor = Monitor::factory()->create();
        $monitor->checks()->createMany([
            ['is_up' => true, 'checked_at' => now()->subHour()],
            ['is_up' => true, 'checked_at' => now()->subHours(2)],
            ['is_up' => true, 'checked_at' => now()->subHours(3)],
            ['is_up' => false, 'checked_at' => now()->subHours(4)],
            ['is_up' => false, 'checked_at' => now()->subDays(3)],
        ]);

        $this->assertSame(75.0, $monitor->uptimePercentage(24));
        $this->assertSame(60.0, $monitor->uptimePercentage(24 * 7));
        $this->assertNull(Monitor::factory()->create()->uptimePercentage());
    }
}
