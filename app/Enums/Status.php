<?php

namespace App\Enums;

enum Status: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case FREEZE = 'FREEZE';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
