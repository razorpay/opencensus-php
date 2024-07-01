<?php

namespace RZP\Models\Merchant\Acs\AsvRouter\AsvMaps;
final class AsvFlows
{

    public const MAP = array(
        'worker:update_merchant_context' => true,
        'merchant_activation_status' => true,
        'merchant_activation_save' => true,
        'merchant_fetch_internal_users' => true,
    );

    public static function isExclusionFLow(string $flow): bool
    {
        if (array_key_exists($flow, self::MAP)) {
            return true;
        }

        return false;
    }

}
