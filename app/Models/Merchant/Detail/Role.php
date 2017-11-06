<?php

namespace RZP\Models\Merchant\Detail;

class Role
{
    const TYPE1 = 'Founder / Co-founder';
    const TYPE2 = 'C-level / SVP';
    const TYPE3 = 'VP / Director / Head';
    const TYPE4 = 'Manager';
    const TYPE5 = 'Individual Contributor';
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
