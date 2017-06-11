<?php

namespace RZP\Models\VirtualAccount;

class Receiver
{
    const BANK_ACCOUNT      = 'bank_account';
    // const VPA               = 'vpa';

    public static function isTypeValid(string $type): bool
    {
        return (defined(__CLASS__ . '::' . strtoupper($type)));
    }
}
