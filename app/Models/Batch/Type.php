<?php

namespace RZP\Models\Batch;

class Type
{
    // Batch Types
    const REFUND = 'refund';

    public static function exists($type)
    {
        return defined(get_class() . '::' . strtoupper($type));
    }
}
