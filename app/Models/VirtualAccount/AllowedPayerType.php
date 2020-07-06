<?php


namespace RZP\Models\VirtualAccount;


class AllowedPayerType
{
    const BANK_ACCOUNT      = 'bank_account';

    public static function isValid(string $payerType) : bool
    {
        return (defined(__CLASS__.'::'.strtoupper($payerType)) === true);
    }
}
