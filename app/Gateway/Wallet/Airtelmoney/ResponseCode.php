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
        '999'   => [
             'Any other Airtel Money failure',
             'Transaction could not be processed as airtel money account has been blocked. Please reset mPIN or call 400 for assistance',
             'Transaction cancelled by user.',
             'You have not changed your mPIN. Please call 121 to reset mPIN.',
             'Internal configuration error. Please contact our call center for support.',
             'Sorry! The actor details you entered are not valid. Kindly try again with correct details.',
             'Your request could not be processed as the actor status in not active',
             'Request timeout. Please try again later',
        ],
        '13365' => 'You have recently performed a similar transaction. Retry after 5 minutes.',
        '14236' => 'Rs.10 Minimum Balance LimitViolated.',
        '35007' => 'Transaction could not be processed as credit is not permitted on customer\'s airtel money a/c. Please contact customer care for assistance.',
        '54074' => 'Transaction reversal not allowed as the wallet has been upgraded to another wallet.',
        '55550' => 'Transaction not present between date range.',
        '999999' => 'Unable to process your request. Please try again later.'
    ];

    const SUCCESS_CODE = '000';

    public static function getResponseMessage($code)
    {
        $codes = self::$codes;

        return $codes[$code];
    }
}
