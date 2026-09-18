<?php

namespace App\Enums;

enum Role: string
{
    case USER = 'USER';
    case ADMIN = 'ADMIN';
    case AUTHOR = 'AUTHOR';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
