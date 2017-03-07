<?php

namespace RZP\Models\BankTransferAttempt;

class Version
{
    const V1    = 'V1'; // Reconciliation without bank_transfer_attempt
    const V2    = 'V2'; // Reconciliaton using bank_transder_attempt

    public static function validateVersion(string $type)
    {
        if (defined(__CLASS__.'::'.strtoupper($type)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid BankTransferAttempt version: ' . $version);
        }
    }
}