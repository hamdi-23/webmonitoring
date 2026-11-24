<?php

namespace App\Enums;

enum MonitorStatus: string
{
    case Pending = 'pending';
    case Up = 'up';
    case Down = 'down';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Belum dicek',
            self::Up => 'Up',
            self::Down => 'Down',
        };
    }
}
