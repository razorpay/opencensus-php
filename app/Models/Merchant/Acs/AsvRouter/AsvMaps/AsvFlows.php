<?php

namespace RZP\Models\Merchant\Acs\AsvRouter\AsvMaps;
final class AsvFlows
{

    public const MAP = array(
        // TOP Read Routes To be Excluded
        'payment_notify' => true,
        'order_payments' => true,
        'payment_fetch_multiple' => true,
        'payment_fetch_by_id' => true,
        'order_fetch_by_id' => true,
        'merchant_activation_details' => true,
        'payment_verify_new' => true,
        'internal_transactions' => true,
        'internal_payment_pricing' => true,
    );

    public static function isExclusionFLow(string $flow): bool
    {
        if (array_key_exists($flow, self::MAP)) {
            return true;
        }

        return false;
    }

}
