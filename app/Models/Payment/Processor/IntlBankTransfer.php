<?php

namespace RZP\Models\Payment\Processor;

use RZP\Models\Merchant\Methods;


class IntlBankTransfer
{
    const ACH   = 'ach';
    const SWIFT = 'swift';


    public static $fullName = [
        self::ACH    => 'ACH',
        self::SWIFT    => 'SWIFT',
    ];

    public static function isValidIntlBankTransferMode($mode): bool
    {
        return (in_array($mode, Methods\Entity::getAddonMethodsList(Methods\Entity::INTL_BANK_TRANSFER), true));
    }

    public static function getName($mode): string
    {
        return self::$fullName[$mode];
    }
}
