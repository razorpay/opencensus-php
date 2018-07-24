<?php

namespace RZP\Gateway\Enach\Base;

class CategoryCode
{
    const A001 = 'A001'; //'API mandate';
    const C001 = 'C001'; //'B2B Corporate';
    const B001 = 'B001'; //'Bill Payment Credit card';
    const D001 = 'D001'; //'Destination Bank Mandate';
    const E001 = 'E001'; //'Education fees';
    const I001 = 'I001'; //'Insurance Premium';
    const I002 = 'I002'; //'Insurance other payment';
    const L099 = 'L099'; //'Legacy One crore and Above';
    const L002 = 'L002'; //'Loan amount security';
    const L001 = 'L001'; //'Loan instalment payment';
    const M001 = 'M001'; //'Mutual Fund Payment';
    const U099 = 'U099'; //'Others';
    const F001 = 'F001'; //'Subscription Fees';
    const T002 = 'T002'; //'TReDS';
    const T001 = 'T001'; //'Tax Payment';
    const U001 = 'U001'; //'Utility Bill Payment Electricity';
    const U003 = 'U003'; //'Utility Bill payment Gas Supply Cos';
    const U005 = 'U005'; //'Utility Bill payment mobile telephone broadband';
    const U006 = 'U006'; //'Utility Bill payment water';

    protected static $mccToCategoryCodeMapping = [
        '6012' => self::L001,
    ];

    public static function getCategoryCodeFromMcc($mcc)
    {
        if (isset(self::$mccToCategoryCodeMapping[$mcc]) === true)
        {
            return self::$mccToCategoryCodeMapping[$mcc];
        }

        return self::A001;
    }

}
