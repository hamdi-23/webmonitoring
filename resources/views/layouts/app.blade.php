<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <header class="border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
            <a href="{{ route('monitors.index') }}" class="flex items-center gap-2 font-semibold">
                <span class="relative flex size-2.5">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                    <span class="relative inline-flex size-2.5 rounded-full bg-emerald-500"></span>
                </span>
                {{ config('app.name') }}
            </a>
            <a href="{{ route('monitors.create') }}"
               class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                + Tambah monitor
            </a>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
        @foreach (['success' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
                   'error' => 'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200'] as $key => $classes)
            @if (session($key))
                <div class="mb-6 rounded-md border px-4 py-3 text-sm {{ $classes }}" role="status">{{ session($key) }}</div>
            @endif
        @endforeach

        @yield('content')
    </main>
</body>
</html>
