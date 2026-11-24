<?php

namespace App\Notifications;

use App\Enums\MonitorStatus;
use App\Models\Monitor;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class MonitorStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Monitor $monitor,
        public MonitorStatus $status,
        public ?string $error = null,
        public ?int $responseTimeMs = null,
        public ?Carbon $downSince = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable ? [TelegramChannel::class] : ['mail'];
    }

    public function isDown(): bool
    {
        return $this->status === MonitorStatus::Down;
    }

    public function downtime(): ?string
    {
        return $this->downSince?->diffForHumans(now(), syntax: Carbon::DIFF_ABSOLUTE, parts: 2);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(($this->isDown() ? '🔴 DOWN: ' : '🟢 PULIH: ').$this->monitor->name)
            ->greeting($this->isDown() ? 'Website tidak bisa diakses' : 'Website sudah normal kembali')
            ->line("**{$this->monitor->name}** — {$this->monitor->url}");

        if ($this->isDown()) {
            $mail->error()->line('Error: '.($this->error ?? 'tidak diketahui'));
        } else {
            $mail->success();
            if ($this->downtime()) {
                $mail->line("Durasi down: {$this->downtime()}");
            }
            if ($this->responseTimeMs !== null) {
                $mail->line("Waktu respons: {$this->responseTimeMs} ms");
            }
        }

        return $mail->action('Lihat detail', route('monitors.show', $this->monitor));
    }

    public function toTelegram(object $notifiable): string
    {
        $e = fn (?string $v) => htmlspecialchars((string) $v, ENT_QUOTES);

        $lines = [
            ($this->isDown() ? '🔴 <b>DOWN</b>' : '🟢 <b>PULIH</b>').' — '.$e($this->monitor->name),
            $e($this->monitor->url),
        ];

        if ($this->isDown()) {
            $lines[] = 'Error: '.$e($this->error ?? 'tidak diketahui');
        } else {
            if ($this->downtime()) {
                $lines[] = 'Durasi down: '.$e($this->downtime());
            }
            if ($this->responseTimeMs !== null) {
                $lines[] = "Respons: {$this->responseTimeMs} ms";
            }
        }

        $lines[] = '<a href="'.$e(route('monitors.show', $this->monitor)).'">Lihat detail</a>';

        return implode("\n", $lines);
    }
}
