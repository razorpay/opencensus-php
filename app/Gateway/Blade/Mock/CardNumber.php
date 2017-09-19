<?php

namespace RZP\Gateway\Blade\Mock;

class CardNumber
{
    const ENROLLED_13_DIGIT_PAN   = '5567630000002004';
    const VALID_ENROLL_NUMBER     = '5567630000002004';
    const VALID_NOT_ENROLL_NUMBER = '4486705296247132';
    const INVALID_MEESAGE         = '4024007197911620';
    const BLANK_MEESAGE           = '5257834104683413';
    const INVALID_VERSION         = '5110731267079214';

    public static function getAccId($cardNumber)
    {
        return base64_encode($cardNumber);
    }

    public static function getCardNumberFromAccId($accId)
    {
        return base64_decode($accId);
    }
}
