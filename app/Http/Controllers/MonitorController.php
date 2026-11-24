<?php

namespace App\Http\Controllers;

use App\Enums\MonitorStatus;
use App\Http\Requests\MonitorRequest;
use App\Models\Monitor;
use App\Services\MonitorChecker;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MonitorController extends Controller
{
    public function index(): View
    {
        $monitors = Monitor::query()
            ->withCount([
                'checks as checks_24h' => fn ($q) => $q->where('checked_at', '>=', now()->subDay()),
                'checks as up_checks_24h' => fn ($q) => $q->where('checked_at', '>=', now()->subDay())->where('is_up', true),
            ])
            ->orderByRaw("CASE status WHEN 'down' THEN 0 WHEN 'pending' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => $monitors->count(),
            'up' => $monitors->where('status', MonitorStatus::Up)->count(),
            'down' => $monitors->where('status', MonitorStatus::Down)->count(),
            'avg_response' => $monitors->where('status', MonitorStatus::Up)->avg('last_response_time_ms'),
        ];

        return view('monitors.index', compact('monitors', 'stats'));
    }

    public function create(): View
    {
        return view('monitors.create', ['monitor' => new Monitor]);
    }

    public function store(MonitorRequest $request, MonitorChecker $checker): RedirectResponse
    {
        $monitor = Monitor::create($request->validated());

        if ($monitor->is_active) {
            $checker->check($monitor);
        }

        return to_route('monitors.show', $monitor)->with('success', 'Monitor berhasil ditambahkan.');
    }

    public function show(Monitor $monitor): View
    {
        $recentChecks = $monitor->checks()->latest('checked_at')->limit(60)->get();

        return view('monitors.show', [
            'monitor' => $monitor,
            'recentChecks' => $recentChecks,
            'chartChecks' => $recentChecks->reverse()->values(),
            'uptime24h' => $monitor->uptimePercentage(24),
            'uptime7d' => $monitor->uptimePercentage(24 * 7),
            'uptime30d' => $monitor->uptimePercentage(24 * 30),
            'avgResponse' => $monitor->averageResponseTime(24),
        ]);
    }

    public function edit(Monitor $monitor): View
    {
        return view('monitors.edit', compact('monitor'));
    }

    public function update(MonitorRequest $request, Monitor $monitor): RedirectResponse
    {
        $monitor->update($request->validated());

        return to_route('monitors.show', $monitor)->with('success', 'Monitor berhasil diperbarui.');
    }

    public function destroy(Monitor $monitor): RedirectResponse
    {
        $monitor->delete();

        return to_route('monitors.index')->with('success', "Monitor \"{$monitor->name}\" dihapus.");
    }

    public function check(Monitor $monitor, MonitorChecker $checker): RedirectResponse
    {
        $check = $checker->check($monitor);

        return back()->with(
            $check->is_up ? 'success' : 'error',
            match (true) {
                $check->is_up => "{$monitor->name} UP ({$check->response_time_ms} ms).",
                $monitor->isAwaitingConfirmation() => "{$monitor->name} gagal ({$monitor->consecutive_failures}/{$monitor->failure_threshold}): {$check->error}",
                default => "{$monitor->name} DOWN: {$check->error}",
            }
        );
    }

    public function toggle(Monitor $monitor): RedirectResponse
    {
        $monitor->update(['is_active' => ! $monitor->is_active]);

        return back()->with('success', $monitor->is_active ? 'Monitor diaktifkan.' : 'Monitor dijeda.');
    }
}
