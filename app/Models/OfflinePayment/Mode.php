<?php

namespace RZP\Models\OfflinePayment;

class Mode
{
    const CASH = 'cash';
    const CHEQUE = 'cheque';
    const OTHCHEQUE = 'othcheque';
    const DD = 'dd';
    const OTHDD = 'othdd';
    const BC = 'bc';
    const OTHBC = 'othbc';
    const HFT = 'hft';
    const OTHER = 'other';

    public static function isValid($mode)
    {
        return defined(__CLASS__ . '::' . strtoupper($mode));
    }
}
