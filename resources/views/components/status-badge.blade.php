@props(['status', 'active' => true])

@php
    $classes = match (true) {
        ! $active => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
        $status === \App\Enums\MonitorStatus::Up => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
        $status === \App\Enums\MonitorStatus::Down => 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300',
        default => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    };
    $dot = match (true) {
        ! $active => 'bg-zinc-400',
        $status === \App\Enums\MonitorStatus::Up => 'bg-emerald-500',
        $status === \App\Enums\MonitorStatus::Down => 'bg-red-500',
        default => 'bg-amber-500',
    };
@endphp

<span {{ $attributes->class("inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium $classes") }}>
    <span class="size-1.5 rounded-full {{ $dot }}"></span>
    {{ $active ? $status->label() : 'Dijeda' }}
</span>
