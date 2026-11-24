@php
    $input = 'mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 dark:focus:ring-zinc-700';
    $label = 'block text-sm font-medium';
    $hint = 'mt-1 text-xs text-zinc-500 dark:text-zinc-400';
@endphp

<div class="space-y-5">
    <div>
        <label for="name" class="{{ $label }}">Nama</label>
        <input id="name" name="name" type="text" required value="{{ old('name', $monitor->name) }}" placeholder="Website Perusahaan" class="{{ $input }}">
        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="url" class="{{ $label }}">URL</label>
        <input id="url" name="url" type="url" required value="{{ old('url', $monitor->url) }}" placeholder="https://example.com" class="{{ $input }}">
        @error('url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-5 sm:grid-cols-3">
        <div>
            <label for="method" class="{{ $label }}">Metode</label>
            <select id="method" name="method" class="{{ $input }}">
                @foreach (['GET', 'HEAD'] as $method)
                    <option value="{{ $method }}" @selected(old('method', $monitor->method) === $method)>{{ $method }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="interval_minutes" class="{{ $label }}">Interval (menit)</label>
            <input id="interval_minutes" name="interval_minutes" type="number" min="1" max="1440" required value="{{ old('interval_minutes', $monitor->interval_minutes) }}" class="{{ $input }}">
            @error('interval_minutes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="timeout_seconds" class="{{ $label }}">Timeout (detik)</label>
            <input id="timeout_seconds" name="timeout_seconds" type="number" min="1" max="60" required value="{{ old('timeout_seconds', $monitor->timeout_seconds) }}" class="{{ $input }}">
            @error('timeout_seconds') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="expected_status" class="{{ $label }}">Status HTTP yang diharapkan <span class="font-normal text-zinc-500">(opsional)</span></label>
            <input id="expected_status" name="expected_status" type="number" min="100" max="599" value="{{ old('expected_status', $monitor->expected_status) }}" placeholder="mis. 200" class="{{ $input }}">
            <p class="{{ $hint }}">Kosongkan agar semua status di bawah 400 dianggap up.</p>
            @error('expected_status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="keyword" class="{{ $label }}">Kata kunci di halaman <span class="font-normal text-zinc-500">(opsional)</span></label>
            <input id="keyword" name="keyword" type="text" value="{{ old('keyword', $monitor->keyword) }}" placeholder="mis. Selamat datang" class="{{ $input }}">
            <p class="{{ $hint }}">Website dianggap down jika teks ini tidak ada di respons (hanya untuk GET).</p>
            @error('keyword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <label class="flex items-center gap-2 text-sm">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $monitor->is_active ?? true)) class="size-4 rounded border-zinc-300">
        Aktif (dicek otomatis sesuai interval)
    </label>
</div>
