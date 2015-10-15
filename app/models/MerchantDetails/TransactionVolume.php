<?php

namespace Models\MerchantDetails;

class TransactionVolume
{
    const RANGE1    = 100000;
    const RANGE2    = 1000000;
    const RANGE3    = 10000000;
    const RANGE4    = 100000000;


    public static function getVolume($num)
    {
        if (empty($num) === true)
        {
            return;
        }

        return constant(__CLASS__.'::'.'RANGE'.$num);
    }
}