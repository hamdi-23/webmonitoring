<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\MonitorStatusChanged;
use Illuminate\Support\Facades\Notification;

class MonitorAlerter
{
    public function send(MonitorStatusChanged $notification): void
    {
        if (config('monitoring.notify.mail')) {
            Notification::send(User::all(), $notification);
        }

        $chatId = config('services.telegram.chat_id');

        if (filled($chatId) && filled(config('services.telegram.bot_token'))) {
            Notification::route('telegram', $chatId)->notify($notification);
        }
    }
}
