<?php

namespace RZP\Models\BankTransfer;

class Mode
{
    const RTGS = 'rtgs';
    const NEFT = 'neft';
    const IMPS = 'imps';
    const IFT  = 'ift';
    const UPI  = 'upi';
    const FT   = 'ft';

    public static function isValid($mode)
    {
        return defined(__CLASS__ . '::' . strtoupper($mode));
    }
}
