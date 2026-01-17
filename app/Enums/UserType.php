<?php

declare(strict_types=1);

namespace App\Enums;

enum UserType: string
{
    case COMMON = 'common';
    case SHOPKEEPER = 'shopkeeper';

    public function isCommon(): bool
    {
        return $this === self::COMMON;
    }

    public function isShopkeeper(): bool
    {
        return $this === self::SHOPKEEPER;
    }
}
