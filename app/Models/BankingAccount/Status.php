<?php

namespace RZP\Models\BankingAccount;

class Status
{
    const CREATED           = 'created';
    const INITIATED         = 'initiated';
    const PROCESSING        = 'processing';
    const PROCESSED         = 'processed';
    const CANCELLED         = 'cancelled';
    const UNSERVICEABLE     = 'unserviceable';

    // Statuses to communicate with RBL
    const SUCCESS           = 'Success';
    const FAILURE           = 'Failure';

    public static function isValidStatus(string $status)
    {
        $key = __CLASS__ . '::' . strtoupper($status);

        return ((defined($key) === true) and (constant($key) === $status));
    }
}
