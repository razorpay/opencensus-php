<?php

namespace RZP\Gateway\Netbanking\Hdfc;

use RZP\Exception\ReconciliationException;

class Status
{
    const DEBIT_SUCCESS = 'processed';
    const DEBIT_REJECT  = 'rejected';

    const REGISTRATION_SUCCESS = 'success';
    const REGISTRATION_FAILURE = 'reject';

    const REGISTRATION_FILE_STATUSES = [
        self::REGISTRATION_SUCCESS,
        self::REGISTRATION_FAILURE,
    ];

    const DEBIT_FILE_STATUSES = [
        self::DEBIT_SUCCESS,
        self::DEBIT_REJECT,
    ];

    /**
     * @param $status
     * @return bool
     * @throws ReconciliationException
     */
    public static function isRegistrationSuccess($status)
    {
        $status = strtolower($status);

        if (in_array($status, self::REGISTRATION_FILE_STATUSES) === false)
        {
            throw new ReconciliationException('Unexpected status passed', ['status' => $status]);
        }

        return ($status === self::REGISTRATION_SUCCESS);
    }

    /**
     * @param $status
     * @return bool
     * @throws ReconciliationException
     */
    public static function isDebitSuccess($status)
    {
        $status = strtolower($status);

        if (in_array($status, self::DEBIT_FILE_STATUSES) === false)
        {
            throw new ReconciliationException('Unexpected status passed', ['status' => $status]);
        }

        return ($status === self::DEBIT_SUCCESS);
    }
}
