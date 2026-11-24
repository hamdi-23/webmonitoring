<?php

namespace Tests\Feature;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MonitorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create());
    }

    public function test_home_redirects_to_dashboard(): void
    {
        $this->get('/')->assertRedirect('/monitors');
    }

    public function test_dashboard_shows_empty_state(): void
    {
        $this->get('/monitors')->assertOk()->assertSee('Belum ada website yang dimonitor');
    }

    public function test_dashboard_lists_monitors(): void
    {
        Monitor::factory()->up()->create(['name' => 'Situs A']);
        Monitor::factory()->down()->create(['name' => 'Situs B']);

        $this->get('/monitors')->assertOk()->assertSeeInOrder(['Situs B', 'Situs A']);
    }

    public function test_monitor_can_be_created_and_is_checked_immediately(): void
    {
        Http::fake(['*' => Http::response('ok')]);

        $response = $this->post('/monitors', [
            'name' => 'Contoh',
            'url' => 'https://example.com',
            'method' => 'get',
            'interval_minutes' => 5,
            'timeout_seconds' => 10,
            'failure_threshold' => 2,
            'is_active' => '1',
        ]);

        $monitor = Monitor::firstOrFail();
        $response->assertRedirect(route('monitors.show', $monitor));
        $this->assertSame('GET', $monitor->method);
        $this->assertSame(1, $monitor->checks()->count());

        $this->get(route('monitors.show', $monitor))->assertOk()->assertSee('Contoh')->assertSee('Riwayat pengecekan');
    }

    public function test_validation_rejects_invalid_url(): void
    {
        $this->post('/monitors', [
            'name' => 'X', 'url' => 'ftp://example.com', 'method' => 'GET',
            'interval_minutes' => 5, 'timeout_seconds' => 10,
        ])->assertSessionHasErrors('url');

        $this->assertSame(0, Monitor::count());
    }

    public function test_monitor_can_be_updated_toggled_checked_and_deleted(): void
    {
        Http::fake(['*' => Http::response('ok')]);
        $monitor = Monitor::factory()->create();

        $this->get(route('monitors.edit', $monitor))->assertOk();

        $this->put(route('monitors.update', $monitor), [
            'name' => 'Baru', 'url' => 'https://example.org', 'method' => 'HEAD',
            'interval_minutes' => 10, 'timeout_seconds' => 5, 'failure_threshold' => 3, 'is_active' => '1',
        ])->assertRedirect(route('monitors.show', $monitor));
        $this->assertSame('Baru', $monitor->fresh()->name);
        $this->assertSame(3, $monitor->fresh()->failure_threshold);

        $this->post(route('monitors.toggle', $monitor));
        $this->assertFalse($monitor->fresh()->is_active);

        $this->post(route('monitors.check', $monitor))->assertSessionHas('success');
        $this->assertSame(1, $monitor->checks()->count());

        $this->delete(route('monitors.destroy', $monitor))->assertRedirect(route('monitors.index'));
        $this->assertModelMissing($monitor);
    }
}
