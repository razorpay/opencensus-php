<?php

namespace RZP\Models\Payment\Processor;

use Razorpay\IFSC\BANK;
use Razorpay\IFSC\IFSC;
use RZP\Models\Payment;

/**
 * Use https://docs.google.com/spreadsheets/d/1ku_2PVJ2Jw72NxOGIVzVg5hKrYi22DErL3rDGtJ9T9U/edit#gid=0
 * to update the available IFSC
 * Class CardPayments
 * @package RZP\Models\Payment\Processor
 */

class CardPayments{

    const FDRL = 'FDRL';

    protected static $supportedTPVBanks = [
        self::FDRL
    ];

    public static function getCardTPVSupportedBanks()
    {
        return self::$supportedTPVBanks;
    }
}
