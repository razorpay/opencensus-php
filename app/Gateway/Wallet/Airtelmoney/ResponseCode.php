<?php

namespace RZP\Gateway\Wallet\Airtelmoney;

use RZP\Gateway\Wallet\Base;

class ResponseCode
{
    public static $codes = [
        '901'   => 'Invalid MID or MID not existing in Airtel Money records',
        '902'   => 'Invalid MID',
        '905'   => 'Invalid input in Success / Failure URL',
        '909'   => 'Only INR currency supported',
        '910'   => 'No Transaction Found',
        '912'   => 'Invalid Amount',
        '913'   => 'Input Amount is negative',
        '920'   => 'Input Transaction Date not in specified format i.e. MMddyyyyHHmmss',
        '923'   => 'Reversal amount is greater than the amount that can be reversed',
        '930'   => 'Transaction Inquiry - Invalid Merchant ID',
        '931'   => 'Transaction Inquiry - Invalid Date Format',
        '999'   => 'Any other Airtel Money failure',
        '13365' => 'You have recently performed a similar transaction. Retry after 5 minutes.',
        '14236' => 'Rs.10 Minimum Balance LimitViolated.',
    ];

    const SUCCESS_CODE = '000';

    public static function getResponseMessage($code)
    {
        $codes = self::$codes;

        return $codes[$code];
    }
}
