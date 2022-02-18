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
    const TAX_INVOICE                 = 'tax_invoice';
    const ONE_CLICK_CHECKOUT          = 'one_click_checkout';
    const CUSTOMER_ADDITIONAL_INFO     = 'customer_additional_info';

    /* map to store types and type specific requirements */
    protected $typeMap = [
        self::TAX_INVOICE                 => true,
        self::ONE_CLICK_CHECKOUT          => true,
        self::CUSTOMER_ADDITIONAL_INFO     => true,
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
