<?php

namespace RZP\Models\Gateway\File;

class Type
{
    const EMI      = 'emi';
    const CLAIM    = 'claim';
    const REFUND   = 'refund';
    const COMBINED = 'combined';

    // Sub types for gateway_file entity
    const TPV     = 'tpv';
    const NON_TPV = 'non_tpv';

    const VALID_TYPES = [
        self::EMI,
        self::CLAIM,
        self::REFUND,
        self::COMBINED,
    ];

    const VALID_SUB_TYPES = [
        self::TPV,
        self::NON_TPV,
    ];

    public static function isValidType(string $type)
    {
        return (in_array($type, self::VALID_TYPES, true) === true);
    }

    public static function isValidSubType(string $subType)
    {
        return (in_array($subType, self::VALID_SUB_TYPES, true) === true);
    }
}
