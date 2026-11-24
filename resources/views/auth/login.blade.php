@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    @php
        $input = 'mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 dark:focus:ring-zinc-700';
    @endphp
    <div class="mx-auto mt-12 max-w-sm">
        <h1 class="text-center text-xl font-semibold">Masuk ke {{ config('app.name') }}</h1>
        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium">Email</label>
                <input id="email" name="email" type="email" required autofocus autocomplete="username" value="{{ old('email') }}" class="{{ $input }}">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-medium">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password" class="{{ $input }}">
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember" value="1" class="size-4 rounded border-zinc-300"> Ingat saya
            </label>
            <button class="w-full rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">Masuk</button>
        </form>
        <p class="mt-4 text-center text-xs text-zinc-500 dark:text-zinc-400">Akun dibuat oleh admin lewat <code>php artisan user:create</code>.</p>
    </div>
@endsection
