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

    /* map to store types and type specific requirements */
    protected $typeMap = [
        self::TAX_INVOICE => true,
    ];

    /**
     * @param $type
     * @return bool
     */
    public function isValidType($type): bool
    {
        return in_array(strtolower($type), array_keys($this->typeMap));
    }
}

