<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MonitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'method' => strtoupper((string) $this->input('method', 'GET')),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url:http,https', 'max:2048'],
            'method' => ['required', Rule::in(['GET', 'HEAD'])],
            'expected_status' => ['nullable', 'integer', 'between:100,599'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'interval_minutes' => ['required', 'integer', 'between:1,1440'],
            'timeout_seconds' => ['required', 'integer', 'between:1,60'],
            'is_active' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'url' => 'URL',
            'expected_status' => 'status yang diharapkan',
            'keyword' => 'kata kunci',
            'interval_minutes' => 'interval',
            'timeout_seconds' => 'timeout',
        ];
    }
}
