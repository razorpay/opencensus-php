<?php

namespace RZP\Models\Merchant\OneClickCheckout\ShippingMethodProvider;

class Type
{
    const SHIPROCKET = 'shiprocket';
    const RAZORPAY   = 'razorpay';
    const MERCHANT   = 'merchant';

    public function isVaidType(string $type)
    {
        return in_array($type, [
            self::SHIPROCKET,
            self::RAZORPAY,
            self::MERCHANT,
        ]);
    }
}
