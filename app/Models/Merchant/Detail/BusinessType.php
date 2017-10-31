<?php

namespace RZP\Models\Merchant\Detail;

class BusinessType
{
    const TYPE1     = 'Private Limited';
    const TYPE2     = 'Proprietorship';
    const TYPE3     = 'Partnership';
    const TYPE4     = 'Individual';
    const TYPE5     = 'Not yet registered';
    const TYPE6     = 'Public Limited';
    const TYPE7     = 'LLP';
    const TYPE8     = 'Educational Institutes';
    const TYPE9     = 'Trust / Society';
    const TYPE10    = 'NGO';
    const TYPE11    = 'Other';

    public static function getType($num)
    {
        if (empty($num) === true)
        {
            return;
        }

        return constant(__CLASS__.'::'.'TYPE'.$num);
    }
}