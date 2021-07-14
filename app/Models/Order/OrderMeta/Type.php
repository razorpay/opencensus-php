<?php

namespace RZP\Models\Order\OrderMeta;

/**
 * Class Type
 *
 * @package RZP\Models\Order\OrderMeta
 *
 * Order_Meta class can store different type of key-value pair.
 * This class is used to define the allowed types in order_meta.
 */
class Type
{
    const TAX_INVOICE = 'tax_invoice';

    /**
     * @param $type
     * @return bool
     */
    public static function isValidType($type)
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and
                (constant($key) === $type));
    }
}

