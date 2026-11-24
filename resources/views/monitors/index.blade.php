@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        @foreach ([
            ['Total monitor', $stats['total'], 'text-zinc-900 dark:text-zinc-100'],
            ['Up', $stats['up'], 'text-emerald-600 dark:text-emerald-400'],
            ['Down', $stats['down'], 'text-red-600 dark:text-red-400'],
            ['Rata-rata respons', $stats['avg_response'] !== null ? round($stats['avg_response']).' ms' : '–', 'text-zinc-900 dark:text-zinc-100'],
        ] as [$label, $value, $color])
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $label }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums {{ $color }}">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="mt-8 overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        @if ($monitors->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="font-medium">Belum ada website yang dimonitor.</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Tambahkan URL pertama untuk mulai memantau uptime dan waktu respons.</p>
                <a href="{{ route('monitors.create') }}" class="mt-4 inline-block rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">+ Tambah monitor</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3 font-medium">Website</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Respons</th>
                            <th class="px-4 py-3 text-right font-medium">Uptime 24j</th>
                            <th class="px-4 py-3 font-medium">Terakhir dicek</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($monitors as $monitor)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                                <td class="px-4 py-3">
                                    <a href="{{ route('monitors.show', $monitor) }}" class="font-medium hover:underline">{{ $monitor->name }}</a>
                                    <div class="max-w-xs truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $monitor->url }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <x-status-badge :status="$monitor->status" :active="$monitor->is_active" />
                                    @if ($monitor->status === \App\Enums\MonitorStatus::Down && $monitor->last_error)
                                        <div class="mt-1 max-w-xs truncate text-xs text-red-600 dark:text-red-400" title="{{ $monitor->last_error }}">{{ $monitor->last_error }}</div>
                                    @elseif ($monitor->is_active && $monitor->isAwaitingConfirmation())
                                        <div class="mt-1 max-w-xs truncate text-xs text-amber-600 dark:text-amber-400" title="{{ $monitor->last_error }}">Gagal {{ $monitor->consecutive_failures }}/{{ $monitor->failure_threshold }} · menunggu konfirmasi</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ $monitor->last_response_time_ms !== null ? $monitor->last_response_time_ms.' ms' : '–' }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{ $monitor->checks_24h ? number_format($monitor->up_checks_24h / $monitor->checks_24h * 100, 2).'%' : '–' }}
                                </td>
                                <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">{{ $monitor->last_checked_at?->diffForHumans() ?? 'Belum pernah' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <form method="POST" action="{{ route('monitors.check', $monitor) }}">
                                        @csrf
                                        <button class="rounded-md border border-zinc-300 px-2.5 py-1 text-xs font-medium hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">Cek sekarang</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
