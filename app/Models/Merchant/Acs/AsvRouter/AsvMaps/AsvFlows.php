<?php

namespace RZP\Models\Merchant\Acs\AsvRouter\AsvMaps;
final class AsvFlows
{

    public const MAP = array(
        'worker:update_merchant_context' => true,
        'merchant_activation_status' => true,
        'merchant_activation_save' => true,
        'merchant_fetch_internal_users' => true,
        'merchant_submit_internal' => true,
        'merchant_activation_update' => true
    );

    public const CacheDisabledFlows = array(
        'worker:update_merchant_context' => true,
        'merchant_activation_status' => true,
        'merchant_activation_save' => true,
        'merchant_submit_internal' => true,
        'merchant_activation_update' => true
    );

    public static function isExclusionFLow(string $flow): bool
    {
        if (array_key_exists($flow, self::MAP)) {
            return true;
        }

        return false;
    }

    public static function isCacheDisabledFlow(string $flow): bool
    {
        if (array_key_exists($flow, self::CacheDisabledFlows)) {
            return true;
        }

        return false;
    }

}
