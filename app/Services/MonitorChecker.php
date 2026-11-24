<?php

namespace App\Services;

use App\Enums\MonitorStatus;
use App\Models\Monitor;
use App\Models\MonitorCheck;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MonitorChecker
{
    public function check(Monitor $monitor): MonitorCheck
    {
        $statusCode = null;
        $responseTime = null;
        $error = null;

        $startedAt = hrtime(true);

        try {
            $response = Http::withUserAgent(config('monitoring.user_agent'))
                ->timeout($monitor->timeout_seconds)
                ->connectTimeout($monitor->timeout_seconds)
                ->withOptions(['allow_redirects' => ['max' => 5]])
                ->send($monitor->method, $monitor->url);

            $responseTime = (int) round((hrtime(true) - $startedAt) / 1_000_000);
            $statusCode = $response->status();

            if ($monitor->expected_status !== null) {
                if ($statusCode !== $monitor->expected_status) {
                    $error = "Status {$statusCode}, diharapkan {$monitor->expected_status}";
                }
            } elseif ($statusCode >= 400) {
                $error = "Status {$statusCode}";
            }

            if ($error === null && filled($monitor->keyword) && $monitor->method !== 'HEAD'
                && ! Str::contains($response->body(), $monitor->keyword)) {
                $error = "Kata kunci \"{$monitor->keyword}\" tidak ditemukan";
            }
        } catch (ConnectionException $e) {
            $error = Str::limit($e->getMessage(), 500);
        } catch (Throwable $e) {
            $error = Str::limit(class_basename($e).': '.$e->getMessage(), 500);
        }

        $isUp = $error === null;
        $now = now();

        $check = $monitor->checks()->create([
            'is_up' => $isUp,
            'status_code' => $statusCode,
            'response_time_ms' => $responseTime,
            'error' => $error,
            'checked_at' => $now,
        ]);

        $previous = $monitor->status;
        $current = $isUp ? MonitorStatus::Up : MonitorStatus::Down;

        $monitor->forceFill([
            'status' => $current,
            'status_changed_at' => $previous === $current ? $monitor->status_changed_at : $now,
            'last_checked_at' => $now,
            'last_status_code' => $statusCode,
            'last_response_time_ms' => $responseTime,
            'last_error' => $error,
        ])->save();

        if ($previous !== $current && $previous !== MonitorStatus::Pending) {
            Log::warning("Monitor \"{$monitor->name}\" berubah status: {$previous->value} -> {$current->value}", [
                'monitor_id' => $monitor->id,
                'url' => $monitor->url,
                'error' => $error,
            ]);
        }

        return $check;
    }
}
