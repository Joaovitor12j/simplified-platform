<?php

declare(strict_types=1);

namespace App\Enums;

enum UserType: string
{
    case COMMON = 'common';
    case SHOPKEEPER = 'shopkeeper';

    public static function fromValue(string $value): self
    {
        return match (strtoupper($value)) {
            'COMMON' => self::COMMON,
            'SHOPKEEPER' => self::SHOPKEEPER,
            default => self::from($value),
        };
    }

    public function isCommon(): bool
    {
        return $this === self::COMMON;
    }

    public function isShopkeeper(): bool
    {
        return $this === self::SHOPKEEPER;
    }
}
