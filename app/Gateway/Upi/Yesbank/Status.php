<?php

namespace RZP\Gateway\Upi\Yesbank;

class Status
{
    const SUCCESS                    = 'S';
    const FAILURE                    = 'F';
    const TIMEOUT                    = 'T';
    const PENDING                    = 'P';
    const TXN_CREDIT_CONFIRM         = 'TCC';
    const REMITTER_RETURN_INITIATED  = 'RET';
    const REMITTER_RETURN_POSTED     =' RRC';
    const VERIFY_SUCCESS             = 'SUCCESS';
    const VERIFY_FAILED              = 'FAILED';
    const VERIFY_FAILURE             = 'FAILURE';
    const VERIFY_PENDING             = 'PENDING';
    const VERIFY_TIMEDOUT            = 'TIMED-OUT';
    const VERIFY_TIMEOUT             = 'TIMEOUT';
    const SUCCESS_STATUS             = 'payment_successful';
    const FAILURE_STATUS             = 'payment_failed';

    const STATUS_CODES = [
        self::VERIFY_SUCCESS    => self::SUCCESS,
        self::VERIFY_FAILURE    => self::FAILURE,
        self::VERIFY_FAILED     => self::FAILURE,
        self::VERIFY_TIMEOUT    => self::TIMEOUT,
        self::VERIFY_TIMEDOUT   => self::TIMEOUT,
        self::VERIFY_PENDING    => self::PENDING,

        self::SUCCESS           => self::SUCCESS,
        self::FAILURE           => self::FAILURE,
        self::TIMEOUT           => self::TIMEOUT,
        self::PENDING           => self::PENDING,
    ];

    public static function getStatusCodeFromMap($code)
    {
        return self::STATUS_CODES[$code];
    }
}
