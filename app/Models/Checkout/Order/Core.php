<?php

namespace RZP\Models\Checkout\Order;

use RZP\Models\Base\Core as BaseCore;

class Core extends BaseCore
{
    public function getPaymentArrayFromCheckoutOrder(Entity $checkoutOrder): array
    {
        $paymentArray = [];

        $checkoutOrderArray = array_merge(
            $checkoutOrder->toArrayPrivate(),
            $checkoutOrder->meta_data
        );

        foreach (Entity::CREATE_PAYMENT_ATTRIBUTES as $attributeKey)
        {
            if (isset($checkoutOrderArray[$attributeKey]))
            {
                $paymentArray[$attributeKey] = $checkoutOrderArray[$attributeKey];
            }
        }

        return $paymentArray;
    }
}
