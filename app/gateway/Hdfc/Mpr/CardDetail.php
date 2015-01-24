<?php

namespace Gateway\Hdfc\Mpr;

use EE\Exception;

class CardDetail
{
    const DC = 'DC';
    const DD = 'DD';
    const FC = 'FC';
    const FD = 'FD';

    public static function checkValue($value)
    {
        if (defined(__CLASS__.'::'.$value) === false)
        {
            throw new Exception\LogicException(
                'Not a valid mpr card type value. Value: ' . $value);
        }
    }

    public static function isInternational($value)
    {
        self::checkValue($value);

        return ($value[0] === 'F');
    }

    public static function isCredit($value)
    {
        self::checkValue($value);

        return ($value[1] === 'C');
    }
}
