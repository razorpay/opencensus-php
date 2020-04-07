<?php

namespace RZP\Models\Merchant;

class Product
{
    // RazorpayX
    const BANKING = 'banking';

    // PG
    const PRIMARY = 'primary';

    public static function isProductBanking(string $product): bool
    {
        return self::BANKING === $product;
    }
}
