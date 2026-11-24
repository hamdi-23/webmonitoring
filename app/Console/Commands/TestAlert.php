<?php

namespace App\Console\Commands;

use App\Enums\MonitorStatus;
use App\Models\Monitor;
use App\Notifications\MonitorStatusChanged;
use App\Services\MonitorAlerter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('monitors:test-alert')]
#[Description('Kirim notifikasi contoh ke email & Telegram untuk mengetes konfigurasi')]
class TestAlert extends Command
{
    public function handle(MonitorAlerter $alerter): int
    {
        $monitor = Monitor::first();

        if (! $monitor) {
            $this->error('Tambahkan minimal satu monitor terlebih dahulu.');

            return self::FAILURE;
        }

        $alerter->send(new MonitorStatusChanged($monitor, MonitorStatus::Down, '[TES] Ini notifikasi percobaan'));

        $this->info('Notifikasi tes dikirim (atau dimasukkan ke queue).');
        $this->line('Email: '.(config('monitoring.notify.mail') ? 'aktif' : 'nonaktif'));
        $this->line('Telegram: '.(filled(config('services.telegram.chat_id')) && filled(config('services.telegram.bot_token')) ? 'aktif' : 'belum dikonfigurasi'));

        return self::SUCCESS;
    }
}
