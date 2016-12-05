<?php

namespace RZP\Models\Bank;

use Razorpay\IFSC\IFSC as BaseIFSC;

class Name extends BaseIFSC
{
    public static function getName($ifsc)
    {
        self::init();
        return BaseIFSC::getBankName($ifsc);
    }

    public static function getNames($ifsc)
    {
        self::init();
        return array_intersect_key(self::$bankNames, array_flip($ifsc));
    }
}
