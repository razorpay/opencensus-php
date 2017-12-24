<?php

namespace RZP\Models\Merchant\Detail;

class TransactionVolume
{
    const RANGE1    = 1;
    const RANGE2    = 500000;
    const RANGE3    = 2500000;
    const RANGE4    = 5000000;
    const RANGE5    = 10000000;
    const RANGE6    = 1000000000;


    public static function getVolume($num)
    {
        if (empty($num) === true)
        {
            return;
        }

        return constant(__CLASS__.'::'.'RANGE'.$num);
    }
}