<?php

namespace RZP\Models\Gateway\File;

class Type
{
    const CLAIM    = 'claim';
    const REFUND   = 'refund';
    const EMI      = 'emi';
    const COMBINED = 'combined';

    public static function isValidType(string $type)
    {
        return (defined(__CLASS__ . '::' . strtoupper($type)));
    }
}
