<?php

namespace RZP\Models\Merchant\Acs\AsvRouter\AsvMaps;


final class WriteEnabledOnAsv {

    // this is a map that, maps repo class to SDK wrapper class and returns the wrapper class instance
    public const MAP = array(
        FunctionConstant::SAVE_OR_FAIL => self::SAVE_OR_FAIL,
        FunctionConstant::DELETE_OR_FAIL => self::DELETE_OR_FAIL,
    );

    public const SAVE_OR_FAIL = array(
    );

    public const DELETE_OR_FAIL = array(
    );

    /**
     * @throws \Exception
     */
    public static function checkIfWriteEnabled($repoClass, $functionName): bool {
        if (array_key_exists($functionName, self::MAP)) {
            $map = self::MAP[$functionName];
            if (array_key_exists($repoClass, $map)) {
                return $map[$repoClass];
            }
        }

        return false;
    }
}
