<?php

namespace RZP\Models\Merchant\Detail;

class Department
{
    const TYPE1 = 'Engineering';
    const TYPE2 = 'Product';
    const TYPE3 = 'Business';
    const TYPE4 = 'Finance';
    const TYPE5 = 'Strategy';
    const TYPE6 = 'Others';

    public static function getType($num)
    {
        if (empty($num) === true)
        {
            return;
        }

        return constant(__CLASS__.'::'.'TYPE'.$num);
    }
}
