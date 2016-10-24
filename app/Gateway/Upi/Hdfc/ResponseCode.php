<?php

namespace RZP\Gateway\Upi\Hdfc;

use RZP\Error;
use RZP\Error\ErrorCode;

class ResponseCode
{
    /**
     * We list these as per the docs given to us
     * However, these are never returned in reality
     * @var array
     */
    public static $codes = array(
        'PT01'  =>  'Debit Transaction Failed',
        'PT02'  =>  'Credit Transaction Failed',
        'PT03'  =>  'Insufficient Fund',
        'PT04'  =>  'Transaction Limit Exceeded',
        'PT05'  =>  'Transaction Amount Exceeded',
        'PT06'  =>  'Invalid amount',
        'PT07'  =>  'Incorrect/Invalid Payer Virtual Address',
        'PT08'  =>  'Incorrect/Invalid Payee Virtual Address',

        'PC01'  =>  'Invalid Request',
        'PC02'  =>  'Validation Error',
        'PC03'  =>  'Technical Error',
        'PC04'  =>  'Time Out',
        'PC05'  =>  'Security answer not matched',

        'PQ01'  =>  'Transaction details not found',

        'MC01'  =>  'Invalid Request',
        'MC02'  =>  'Merchant not found',
        'MC03'  =>  'Inactive Merchant',
        'MC04'  =>  'Validation Error',
        'MC05'  =>  'Technical Error',
        'MC06'  =>  'Time Out',
        'MC07'  =>  'Cancel by User',
        'MC08'  =>  'Customer Already Registered',

        'MT01'  =>  'Debit Transaction Failed',
        'MT02'  =>  'Credit Transaction Failed',
        'MT03'  =>  'Insufficient Fund',
        'MT04'  =>  'Transaction Limit Exceeded',
        'MT05'  =>  'Transaction Amount Exceeded',
        'MT06'  =>  'Closed Account',
        'MT07'  =>  'Inactive Account',
        'MT08'  =>  'Invalid M-PIN entered',
        'MT09'  =>  'Dormant Account/Decline',
        'MT10'  =>  'No Credit Account',
        'MT11'  =>  'Credit decline reversal',
        'MT12'  =>  'Partial Decline',

        'MQ01'  =>  'Transaction details not found',

        'FAILED'    =>  'Payment Failed because of Gateway Error',
    );

    public static function getResponseMessage($code)
    {
        $codes = self::$codes;

        if (array_key_exists($code, $codes))
        {
            return $codes[$code];
        }
        else
        {
            return 'Unknown Gateway Response Code';
        }
    }
}
