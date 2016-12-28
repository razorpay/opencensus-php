<?php

namespace RZP\Models\Settlement\Details;

use RZP\Exception;

class Component
{
    const REFUND        = 'refund';
    const PAYMENT       = 'payment';
    const ADJUSTMENT    = 'adjustment';
    const FEE           = 'fee';
    const SERVICE_TAX   = 'service_tax';
    const FEE_CREDITS   = 'fee_credits';

    public static function validateComponent($component)
    {
        if (defined(__CLASS__.'::'.strtoupper($component)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid settlemnt component: ' . $component);
        }
    }
}
