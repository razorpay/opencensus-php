<?php

namespace RZP\Models\Merchant\Detail;

class BusinessType
{
    const TYPE1     = 'Proprietership';
    const TYPE2     = 'Individual';
    const TYPE3     = 'Partnership';
    const TYPE4     = 'Private Limited';
    const TYPE5     = 'Public Limited';
    const TYPE6     = 'LLP';
    const TYPE7     = 'NGO';
    const TYPE8     = 'Educational Institutes';
    const TYPE9     = 'Trust';
    const TYPE10    = 'Society';

    public static function getType($num)
    {
        if (empty($num) === true)
        {
            return;
        }

        $type = 'TYPE' . $num;

        self::validateType($type);

        return constant(__CLASS__.'::'.'TYPE'.$num);
    }

    public static function validateType($type)
    {
        if (defined(__CLASS__.'::'.strtoupper($type)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid BusinessType: ' . $type);
        }
    }
}
