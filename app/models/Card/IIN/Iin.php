<?php

namespace Models\Card\IIN;

use Models\Bank;

class Iin
{
    public static $iinBankMap  = array(
        [ '401200', '401300', Bank\IFSC::HDFC],
    );

    public function getBankByIIN($iin)
    {
        foreach (self::$iinBankMap as $iinEntry) {
            if($iin >= $iinEntry[0] and $iin <= $iinEntry[1])
            {
                return $iinEntry[2];
            }
        }

        throw new Exception("Missing IIN number from DB", $iin);
    }
}