<?php

namespace RZP\Models\Merchant\Credits\Balance;

class Product
{
    const BANKING = 'banking';

    public static function exists(string $product): bool
    {
        $key = __CLASS__ . '::' . strtoupper($product);

        return (defined($key) === true);
    }
}
