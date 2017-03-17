<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Exception;

class Version
{
    const V1    = 'V1'; // Reconciliation without fund_transfer_attempt
    const V2    = 'V2'; // Reconciliation using fund_transfer_attempt

    public static function validateVersion(string $version)
    {
        if (defined(__CLASS__.'::'. $version) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid FundTransferAttempt version: ' . $version);
        }
    }
}