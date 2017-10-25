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

    public static function isValidType(string $type)
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }
}
