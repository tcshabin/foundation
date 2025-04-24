<?php

namespace App\Helpers;

class Status
{
    const INACTIVE = 0;
    const ACTIVE   = 1;
    const DELETED  = 2;
    const BLOCKED  = 3;

    public static function getLabel(int $status): string
    {
        return match ($status) {
            self::ACTIVE   => 'Active',
            self::INACTIVE => 'Inactive',
            self::DELETED  => 'Deleted',
            self::BLOCKED  => 'Blocked',
            default        => 'Unknown',
        };
    }

    public static function getAll(): array
    {
        return [
            self::ACTIVE   => 'Active',
            self::INACTIVE => 'Inactive',
            self::DELETED  => 'Deleted',
            self::BLOCKED  => 'Blocked',
        ];
    }
}
