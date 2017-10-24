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

    const VALID_TYPES = [
        self::EMI,
        self::CLAIM,
        self::REFUND,
        self::COMBINED,
        self::EMANDATE_REGISTER,
        self::EMANDATE_DEBIT,
    ];

    public static function isValidType(string $type)
    {
        return (in_array($type, self::VALID_TYPES, true) === true);
    }
}
