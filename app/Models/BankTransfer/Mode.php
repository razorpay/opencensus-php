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
    const FUND_TRANS   = 'fund trans';
    const TRANSFER   = 'transfer';

    public static function isValid($mode)
    {
        return defined(__CLASS__ . '::' . strtoupper($mode));
    }
}
