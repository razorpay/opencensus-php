<?php

namespace RZP\Models\PaperMandate;

class DebitType
{
    const FIXED_AMOUNT   = 'fixed_amount';
    const MAXIMUM_AMOUNT = 'maximum_amount';

    public static function isValidType($type)
    {
        return (defined(__CLASS__.'::'.strtoupper($type)));
    }
}
