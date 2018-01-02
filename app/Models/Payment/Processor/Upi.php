<?php

namespace RZP\Models\Payment\Processor;

use Razorpay\IFSC\IFSC;
use RZP\Models\Payment;

class Upi
{
    const ICIC      = 'ICIC';
    const HDFC      = 'HDFC';   
    const SBIN      = 'SBIN';

    public static function exists($bank)
    {
        return defined(__CLASS__ . '::' . strtoupper($bank));
    }

    /**
     * Returns a key value map array,
     * where each key represents a bank in IFSC code format,
     * and value represents the full name of the bank
     *
     * @return array
     */
    public static function getFullBankNamesMap()
    {
        $upiBanks = Payment\Gateway::$upiToGatewayMap;

        $bankNameMap = [];

        foreach ($upiBanks as $bank => $gateway)
        {
            $bankNameMap[$bank] = IFSC::getBankName($bank);
        }

        return $bankNameMap;
    }
}
