<?php

namespace RZP\Gateway\Blade\Mock;

class CardNumber
{
    const ENROLLED_13_DIGIT_PAN   = '4532249047240';
    const VALID_ENROLL_NUMBER     = '4532249047240';
    const VALID_NOT_ENROLL_NUMBER = '4486705296247132';
    const INVALID_MEESGAE         = '4024007197911620';
    const BLANK_MEESGAE           = '5257834104683413';
    const INVALID_VERSION         = '5110731267079214';

    const CARD_ACC_ID_MAP = [
        self::ENROLLED_13_DIGIT_PAN => 'abcdef',
    ];

    public static function getAccId($cardNumber)
    {
        return self::CARD_ACC_ID_MAP[$cardNumber];
    }

    public static function getCardNumber($accId)
    {
        $flipped = array_flip(self::CARD_ACC_ID_MAP);

        return $flipped[$accId];
    }
}
