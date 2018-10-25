<?php

namespace RZP\Gateway\Hdfc;

class AuthRespCode
{
    const D = 'D';
    const E = 'E';
    const F = 'F';
    const G = 'G';
    const H = 'H';
    const I = 'I';
    const J = 'J';
    const K = 'K';
    const X = 'X';
    const Y = 'Y';
    const Z = 'Z';
    const P = 'P';

    public static $authRespCodeDescription = [
        self::D => 'Total Amount limit set for the terminal for transactions has been crossed',
        self::E => 'Total transaction limit set for the terminal has been crossed ',
        self::F => 'Maximum debit amount limit set for the terminal for a day has been crossed',
        self::G => 'Maximum credit amount limit set for the terminal for a day has been crossed',
        self::H => 'Maximum debit amount set for per card for rolling 24 hrs has been crossed',
        self::I => 'Maximum credit amount set for per card for rolling 24 hrs has been crossed',
        self::J => 'Maximum transaction set for per card for rolling 24 hrs has been crossed',
        self::K => 'Amount Less than Minimum Amount configured',
        self::P => '(HDFC internal DB issue)',
        self::X => 'BIN is added as negative BIN in PG',
        self::Y => 'Card is present in negative card list and will be decline in future also unless it is not removed manually.',
        self::Z => 'Card is present in decline card database, will be declined for short time',
    ];

    public static function shouldRetryRefund($authRespCode)
    {
        $retryAuthRespCodes = [self::D, self::E, self::F, self::G, self::H, self::I, self::J, self::P];

        return (in_array($authRespCode, $retryAuthRespCodes, true) === true);
    }
}
