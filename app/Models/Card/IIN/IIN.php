<?php

namespace RZP\Models\Card\IIN;

use RZP\Models\Bank;
use RZP\Exception;
use RZP\Error\ErrorCode;

class IIN
{
    protected static $emiBanks = array(
        Bank\IFSC::UTIB,
        Bank\IFSC::INDB,
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
        Bank\IFSC::INDB => [
            "37715100",
            "37715120",
            "37715121",
            "37715122",
            "37715123",
            "37715124",
            "37715125",
            "37715126",
            "37715160",
            "37715170",
            "37715180",
            "37715180",
            "41475200",
            "41475210",
            "41475212",
            "41475213",
            "41475220",
            "41477200",
            "41477200",
            "41477210",
            "42712400",
            "42712410",
            "44128300",
            "44128400",
            "44128410",
            "44128411",
            "44128500",
            "44128502",
            "44128503",
            "44128510",
            "44128520",
            "44128530",
            "44128531",
            "44128540",
            "44128550",
            "44128560",
            "44128561",
            "44128570",
            "44128570",
            "44128571",
            "44128575",
            "44128576",
            "44128577",
            "44128578",
            "44128580",
            "44128581",
            "44128590",
            "44128591",
            "44128595",
            "46378700",
            "46893600",
            "49872600",
            "51606800",
            "51606801",
            "51606802",
            "52448010",
            "52448011",
            "52448012",
            "52448020",
            "52448030",
            "52448040",
            "52686100",
            "52686101",
            "52686102",
            "52686110",
            "53765210",
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