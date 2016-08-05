<?php

namespace RZP\Gateway\Billdesk;

use RZP\Models\Payment\TwoFaStatus;

class AuthStatus
{
    const SUCCESS       = '0300';
    const INVALID_AUTH  = '0399';
    const PENDING       = '0002';
    const NA            = 'NA';
    const ERROR         = '0001';

    public static $statusMap = array(
        self::SUCCESS       => 'authorized',
        self::PENDING       => 'pending',
        self::INVALID_AUTH  => 'failed',
        self::ERROR         => 'failed',
        self::NA            => 'failed');

    public static $messages = array(
        '0300'  => 'Success',
        '0399'  => 'Invalid Authentication at Bank Cancel Transaction',
        'NA'    => 'Invalid Input in the Request Message Cancel Transaction, or transaction not found on billdesk end',
        '0002'  => 'BillDesk is waiting for Response from Bank Pending Transaction',
        '0001'  => 'Error at BillDesk Cancel Transaction',
    );

    public static function isTwoFaFailed($code)
    {
        $twoFaStatus = self::getTwoFaStatus($code);

        return ($twoFaStatus === TwoFaStatus::FAILED);
    }

    public static function getTwoFaStatus($code)
    {
        switch ($code) {
            case '0300':
                return TwoFaStatus::PASSED;
            default:
                return TwoFaStatus::UNKNOWN;
            }
    }
}
