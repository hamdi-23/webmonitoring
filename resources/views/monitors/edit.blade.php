@extends('layouts.app')

@section('title', 'Edit monitor')

@section('content')
    <div class="mx-auto max-w-2xl">
        <h1 class="text-xl font-semibold">Edit monitor</h1>
        <form method="POST" action="{{ route('monitors.update', $monitor) }}" class="mt-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
            @csrf
            @method('PUT')
            @include('monitors._form')
            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('monitors.show', $monitor) }}" class="rounded-md px-4 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">Batal</a>
                <button class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">Simpan perubahan</button>
            </div>
        </form>
    </div>
@endsection
