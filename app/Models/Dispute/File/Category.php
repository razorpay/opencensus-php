<?php

namespace RZP\Models\Dispute\File;

class Category
{
    const EXPLANATION_LETTER                  = 'explanation_letter';
    const POLICIES                            = 'policies';
    const CUSTOMER_COMMUNICATION              = 'customer_communication';
    const DELIVERY_PROOF                      = 'delivery_proof';
    const SHIPPING_PROOF                      = 'shipping_proof';
    const PROOF_OF_SERVICES_PRODUCTS          = 'proof_of_services_products';
    const INSTANT_SERVICES                    = 'instant_services';
    const OTHERS                              = 'others';

    public static function exists(string $category): bool
    {
        $key = __CLASS__ . '::' . strtoupper($category);

        return ((defined($key) === true) and (constant($key) === $category));
    }
}
