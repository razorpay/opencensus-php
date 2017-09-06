<?php

namespace RZP\Models\Gateway\File;

class Type
{
    const EMI      = 'emi';
    const CLAIM    = 'claim';
    const REFUND   = 'refund';
    const COMBINED = 'combined';

    const VALID_TYPES = [
        self::EMI,
        self::CLAIM,
        self::REFUND,
        self::COMBINED,
    ];

    public static function isValidType(string $type)
    {
        return (in_array($type, self::VALID_TYPES, true) === true);
    }
}
