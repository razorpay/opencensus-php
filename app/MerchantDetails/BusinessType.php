<?php

namespace App\MerchantDetails;

class BusinessType
{
    const TYPE1     = 'Proprietorship';
    const TYPE2     = 'Individual';
    const TYPE3     = 'Partnership';
    const TYPE4     = 'Private Limited';
    const TYPE5     = 'Public Limited';
    const TYPE6     = 'LLP';
    const TYPE7     = 'NGO';
    const TYPE8     = 'Educational Institutes';
    const TYPE9     = 'Trust';
    const TYPE10    = 'Society';
    const TYPE11    = 'Not yet registered';
    const TYPE12    = 'Other';

    const INDIVIDUAL             = 'individual';
    const NOT_YET_REGISTERED     = 'not_yet_registered';

    protected static $typeIndexMap = [
        self::INDIVIDUAL             => 2,
        self::NOT_YET_REGISTERED     => 11,
    ];

    public static function isBusinessTypeForNotRegisteredBusiness($key)
    {
        $typeArray = array_flip(self::$typeIndexMap);

        return (isset($typeArray[$key]) === true);
    }

    public static function getType($num)
    {
        if (empty($num) === true)
        {
            return;
        }

        return constant(__CLASS__.'::'.'TYPE'.$num);
    }
}
