<?php

namespace RZP\Models\Batch;

class Type
{
    const REFUND       = 'refund';
    const PAYMENT_LINK = 'payment_link';

    public static function exists(string $type)
    {
        return defined(get_class() . '::' . strtoupper($type));
    }
}
