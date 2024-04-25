<?php

namespace RZP\Models\Merchant\Acs\AsvRouter\AsvMaps;
final class AsvFlows
{

    public const ENABLED_QUERY = array();

    public const MAP = array();

    public static function isExclusionFLow(string $flow): bool
    {
        if (array_key_exists($flow, self::MAP)) {
            return true;
        }

        return false;
    }

    public static function isEnabledQueryLogs(string $flow): bool
    {
        if (array_key_exists($flow, self::ENABLED_QUERY)) {
            return true;
        }

        return false;
    }

}
