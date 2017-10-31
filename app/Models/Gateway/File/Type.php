<?php

namespace RZP\Models\Gateway\File;

class Type
{
    const EMI               = 'emi';
    const CLAIM             = 'claim';
    const REFUND            = 'refund';
    const COMBINED          = 'combined';
    const EMANDATE_REGISTER = 'emandate_register';
    const EMANDATE_DEBIT    = 'emandate_debit';

    // Sub types for gateway_file entity
    const TPV     = 'tpv';
    const NON_TPV = 'non_tpv';

    const VALID_TYPES = [
        self::EMI,
        self::CLAIM,
        self::REFUND,
        self::COMBINED,
        self::EMANDATE_REGISTER,
        self::EMANDATE_DEBIT,
    ];

    const VALID_SUB_TYPES = [
        self::TPV,
        self::NON_TPV,
    ];

    public static function isValidType(string $type)
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }

    public static function isValidSubType(string $subType)
    {
        return (in_array($subType, self::VALID_SUB_TYPES, true) === true);
    }
}
