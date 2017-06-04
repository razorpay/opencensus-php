<?php

namespace RZP\Models\BankTransfer;

use RZP\Exception;

class Mode
{
    const RTGS = 'rtgs';
    const NEFT = 'neft';
    const IMPS = 'imps';
    const IFT  = 'ift';

    public static function isValid($mode)
    {
        return defined(__CLASS__ . '::' . strtoupper($mode));
    }
}
