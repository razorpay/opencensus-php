<?php

namespace RZP\Models\Settlement\Details;

class Component
{
    const REFUND        = 'refund';
    const PAYMENT       = 'payment';
    const ADJUSTMENT    = 'adjustment';
    const FEE           = 'fee';
    const SERVICE_TAX   = 'service_tax';

    public static function validateComponent($component)
    {
        if (defined(__CLASS__.'::'.strtoupper($component)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid settlemnt component: ' . $component);
        }
    }
}
