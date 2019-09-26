<?php

namespace RZP\Models\Merchant\Detail;

class Department
{
    const TYPE1 = 'Tech/Engineering';
    const TYPE2 = 'Product';
    const TYPE3 = 'Business';
    const TYPE4 = 'Finance';
    const TYPE5 = 'Strategy';
    const TYPE6 = 'Others';
    const TYPE7 = 'Founder/Proprietor';
    const TYPE8 = 'NA';

    const DefaultDepartment = 8;

    public static function getType($num)
    {
        if (empty($num) === true)
        {
            return;
        }

        return constant(__CLASS__.'::'.'TYPE'.$num);
    }

    public static function getDefaultDepartment()
    {
        return self::DefaultDepartment;
    }
}
