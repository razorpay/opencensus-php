<?php

namespace RZP\Models\Card\IIN;

use RZP\Models\Bank;
use RZP\Exception;
use RZP\Error\ErrorCode;

class IIN
{
    protected static $emiBanks = array(
        Bank\IFSC::UTIB,
    );

    protected static $emiIins = array(
        Bank\IFSC::UTIB => [
            "40599500",
            "40743800",
            "40743900",
            "40743903",
            "41114600",
            "41114601",
            "41114602",
            "41114603",
            "41114604",
            "41114605",
            "41821201",
            "41821202",
            "43656000",
            "45050600",
            "45145600",
            "45145604",
            "45145700",
            "46111600",
            "46111700",
            "46111800",
            "46411800",
            "47186000",
            "47186001",
            "47186003",
            "47186100",
            "47186101",
            "47186102",
            "47186300",
            "47186301",
            "47186302",
            "47186400",
            "52417800",
            "52417810",
            "52417811",
            "52424000",
            "52450800",
            "52451200",
            "53056200",
            "53056202",
            "55934000",
            "55934100",
            "55934200",
        ],
    );

    protected static function isValidCardForBank($bank, $cardNumber)
    {
        if (in_array($bank, self::$emiBanks))
        {
            foreach (self::$emiIins[$bank] as $iin)
            {
                if (substr($cardNumber, 0, strlen($iin)) === $iin)
                {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    public static function validateEmiAvailableForCard($iin, $cardNumber)
    {
        if (self::isEmiAvailableForCard($iin, $cardNumber) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_EMI_NOT_AVAILABLE_ON_CARD);
        }
    }

    /**
     * This checks for the special case of Axis Bank which works on first 8 digits
     * of the card instea of the first 6.
     */
    public static function isEmiAvailableForCard($iin, $cardNumber)
    {
        $emi = (($iin->isEmiAvailable()) and
                (self::isValidCardForBank($iin->getIssuer(), $cardNumber)));

        return $emi;
    }
}