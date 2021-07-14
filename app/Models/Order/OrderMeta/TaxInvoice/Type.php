<?php

namespace RZP\Models\Order\OrderMeta\TaxInvoice;

/**
 * Class Types
 *
 * @package RZP\Models\Order\OrderMeta\TaxInvoice
 */
class Type
{
    const INTERSTATE    = 'interstate';
    const INTRASTATE    = 'intrastate';

    /**
     * @param string $type
     * @return bool
     */
    public static function isValidType(string $type)
    {
        $key = __CLASS__. '::' . strtoupper($type);

        return ((defined($key) === true) and
                (constant($key) === $type));
    }
}

