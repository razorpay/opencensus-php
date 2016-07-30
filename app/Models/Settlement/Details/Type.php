<?php

namespace RZP\Models\Settlement\Details;

class Type
{
    const REFUND        = 'refund';
    const PAYMENT       = 'payment';
    const ADJUSTMENT    = 'adjustment';
    const FEE           = 'fee';
    const SERVICE_TAX   = 'service_tax';

    public static function validateType($type)
    {
        if (defined(__CLASS__.'::'.strtoupper($type)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid settlemnt component type: ' . $type);
        }
    }
}
