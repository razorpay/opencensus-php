<?php

namespace RZP\Gateway\Card\Fss;

use RZP\Exception;
use Razorpay\IFSC\Bank;

class BankCodes
{
    /**
     * IFSC to Fss Bank code mapping.
     *
     * @var array
     */
    public static $bankCodes = [
        Bank::UTBI => 1113,
        Bank::IOBA => 1114,
        Bank::ANDB => 1115,
        Bank::SYNB => 1116,
        Bank::SURY => 1117,
        Bank::UCBA => 1118,
        Bank::ICIC => 1119,
    ];

    /**
     * Get Card types and it's values by acquirer
     * @param $ifsc
     *
     * @return mixed
     * @throws \RZP\Exception\LogicException
     */
    public static function getBankCodeByIfsc($ifsc)
    {
        if (empty(self::$bankCodes[$ifsc]) === true)
        {
            throw new Exception\LogicException(
                'Unsupported ifsc for the gateway',
                null,
                [
                    'ifsc' => $ifsc,
                ]);
        }

        return self::$bankCodes[$ifsc];
    }
}

