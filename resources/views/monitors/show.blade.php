@extends('layouts.app')

@section('title', $monitor->name)

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('monitors.index') }}" class="text-sm text-zinc-500 hover:underline dark:text-zinc-400">&larr; Semua monitor</a>
            <div class="mt-2 flex items-center gap-3">
                <h1 class="text-xl font-semibold">{{ $monitor->name }}</h1>
                <x-status-badge :status="$monitor->status" :active="$monitor->is_active" />
            </div>
            <a href="{{ $monitor->url }}" target="_blank" rel="noopener noreferrer" class="mt-1 block truncate text-sm text-zinc-500 hover:underline dark:text-zinc-400">{{ $monitor->url }}</a>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                {{ $monitor->method }} · setiap {{ $monitor->interval_minutes }} menit · timeout {{ $monitor->timeout_seconds }} dtk · down setelah {{ $monitor->failure_threshold }}x gagal
                @if ($monitor->expected_status) · harus status {{ $monitor->expected_status }} @endif
                @if ($monitor->keyword) · kata kunci “{{ $monitor->keyword }}” @endif
                @if ($monitor->status_changed_at) · status ini sejak {{ $monitor->status_changed_at->diffForHumans() }} @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @php $btn = 'rounded-md border border-zinc-300 px-3 py-1.5 text-sm font-medium hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800'; @endphp
            <form method="POST" action="{{ route('monitors.check', $monitor) }}">@csrf<button class="{{ $btn }}">Cek sekarang</button></form>
            <form method="POST" action="{{ route('monitors.toggle', $monitor) }}">@csrf<button class="{{ $btn }}">{{ $monitor->is_active ? 'Jeda' : 'Aktifkan' }}</button></form>
            <a href="{{ route('monitors.edit', $monitor) }}" class="{{ $btn }}">Edit</a>
            <form method="POST" action="{{ route('monitors.destroy', $monitor) }}" onsubmit="return confirm('Hapus monitor ini beserta riwayatnya?')">
                @csrf @method('DELETE')
                <button class="rounded-md border border-red-300 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-400 dark:hover:bg-red-950">Hapus</button>
            </form>
        </div>
    </div>

    @if ($monitor->status === \App\Enums\MonitorStatus::Down && $monitor->last_error)
        <div class="mt-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
            <span class="font-medium">Error terakhir:</span> {{ $monitor->last_error }}
        </div>
    @elseif ($monitor->is_active && $monitor->isAwaitingConfirmation())
        <div class="mt-6 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">
            <span class="font-medium">Gagal {{ $monitor->consecutive_failures }} dari {{ $monitor->failure_threshold }} kali, menunggu konfirmasi:</span> {{ $monitor->last_error }}
            <span class="block text-xs opacity-80">Akan dicek ulang dalam ±1 menit. Notifikasi dikirim jika tetap gagal.</span>
        </div>
    @endif

    <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-4">
        @foreach ([
            ['Uptime 24 jam', $uptime24h],
            ['Uptime 7 hari', $uptime7d],
            ['Uptime 30 hari', $uptime30d],
        ] as [$label, $value])
            <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $label }}</div>
                <div @class([
                    'mt-1 text-2xl font-semibold tabular-nums',
                    'text-emerald-600 dark:text-emerald-400' => $value !== null && $value >= 99,
                    'text-amber-600 dark:text-amber-400' => $value !== null && $value < 99 && $value >= 95,
                    'text-red-600 dark:text-red-400' => $value !== null && $value < 95,
                ])>{{ $value !== null ? number_format($value, 2).'%' : '–' }}</div>
            </div>
        @endforeach
        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">Rata-rata respons 24j</div>
            <div class="mt-1 text-2xl font-semibold tabular-nums">{{ $avgResponse !== null ? $avgResponse.' ms' : '–' }}</div>
        </div>
    </div>

    <div class="mt-6 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-baseline justify-between">
            <h2 class="text-sm font-medium">Waktu respons</h2>
            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $chartChecks->count() }} pengecekan terakhir</span>
        </div>
        @if ($chartChecks->isEmpty())
            <p class="py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">Belum ada data pengecekan.</p>
        @else
            @php
                $maxMs = max(1, $chartChecks->max('response_time_ms') ?? 1);
                $slot = 100 / 60;
            @endphp
            <div class="mt-3 flex gap-3">
                <div class="flex h-32 flex-col justify-between text-right text-[10px] tabular-nums text-zinc-400">
                    <span>{{ $maxMs }} ms</span><span>0</span>
                </div>
                <svg viewBox="0 0 100 40" preserveAspectRatio="none" class="h-32 w-full" role="img" aria-label="Grafik waktu respons">
                    @foreach ($chartChecks as $i => $check)
                        @php
                            $h = $check->is_up && $check->response_time_ms !== null ? max(1, $check->response_time_ms / $maxMs * 40) : 40;
                        @endphp
                        <rect x="{{ $i * $slot + $slot * 0.15 }}" y="{{ 40 - $h }}" width="{{ $slot * 0.7 }}" height="{{ $h }}" rx="0.3"
                              @class(['fill-emerald-500/80' => $check->is_up, 'fill-red-500/80' => ! $check->is_up])>
                            <title>{{ $check->checked_at->format('d M H:i:s') }} — {{ $check->is_up ? $check->response_time_ms.' ms' : 'DOWN: '.$check->error }}</title>
                        </rect>
                    @endforeach
                </svg>
            </div>
            <div class="mt-2 flex gap-4 text-xs text-zinc-500 dark:text-zinc-400">
                <span class="flex items-center gap-1.5"><span class="size-2 rounded-sm bg-emerald-500"></span>Up</span>
                <span class="flex items-center gap-1.5"><span class="size-2 rounded-sm bg-red-500"></span>Down</span>
            </div>
        @endif
    </div>

    <div class="mt-6 overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <h2 class="border-b border-zinc-200 px-4 py-3 text-sm font-medium dark:border-zinc-800">Riwayat pengecekan</h2>
        @if ($recentChecks->isEmpty())
            <p class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">Belum ada data.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-2 font-medium">Waktu</th>
                            <th class="px-4 py-2 font-medium">Hasil</th>
                            <th class="px-4 py-2 text-right font-medium">HTTP</th>
                            <th class="px-4 py-2 text-right font-medium">Respons</th>
                            <th class="px-4 py-2 font-medium">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($recentChecks as $check)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-2 text-zinc-500 dark:text-zinc-400">{{ $check->checked_at->format('d M Y H:i:s') }}</td>
                                <td class="px-4 py-2">
                                    <span @class(['font-medium', 'text-emerald-600 dark:text-emerald-400' => $check->is_up, 'text-red-600 dark:text-red-400' => ! $check->is_up])>{{ $check->is_up ? 'Up' : 'Down' }}</span>
                                </td>
                                <td class="px-4 py-2 text-right tabular-nums">{{ $check->status_code ?? '–' }}</td>
                                <td class="px-4 py-2 text-right tabular-nums">{{ $check->response_time_ms !== null ? $check->response_time_ms.' ms' : '–' }}</td>
                                <td class="max-w-md truncate px-4 py-2 text-zinc-500 dark:text-zinc-400" title="{{ $check->error }}">{{ $check->error ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
