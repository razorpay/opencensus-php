<?php

namespace RZP\Models\CreditNote;

use RZP\Exception\BadRequestValidationFailureException;

class Status
{
    const CREATED             = 'created';
    const PARTIALLY_PROCESSED = 'partially_processed';
    const PRCOESSED           = 'processed';

    /**
     * @param string $status
     *
     * @return bool
     */
    public static function isStatusValid(string $status): bool
    {
        $key = __CLASS__ . '::' . strtoupper($status);

        return ((defined($key) === true) and (constant($key) === $status));
    }

    /**
     * @param string $status
     *
     * @throws BadRequestValidationFailureException
     */
    public static function checkStatus(string $status)
    {
        if (self::isStatusValid($status) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid status: ' . $status);
        }
    }
}
