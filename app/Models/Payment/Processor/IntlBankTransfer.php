<?php

namespace RZP\Models\Payment\Processor;

use RZP\Models\Merchant\Methods;


class IntlBankTransfer
{
    const ACH   = 'ach';
    const SWIFT = 'swift';
    const FPS = 'fps';
    const SEPA = 'sepa';

    public static $fullName = [
        self::ACH    => 'ACH',
        self::SWIFT    => 'SWIFT',
        self::FPS     => 'FPS',
        self::SEPA      => 'SEPA',
    ];

    const MIN_INTL_BANK_TRANSFER_AMOUNT = 1163000; // Rs 11,630 slack ref: https://razorpay.slack.com/archives/C024U3B04LD/p1694511377729109?thread_ts=1694510816.389659&cid=C024U3B04LD
    const MAX_INTL_BANK_TRANSFER_AMOUNT = 83000000; // Rs 8,30,000

    public static function isValidIntlBankTransferMode($mode): bool
    {
        return (in_array($mode, Methods\Entity::getAddonMethodsList(Methods\Entity::INTL_BANK_TRANSFER), true));
    }

    public static function getName($mode): string
    {
        return self::$fullName[$mode];
    }
}
