<?php

namespace RZP\Models\FundTransfer\Axis2;

use RZP\Models\FundTransfer\Mode;

final class Constants
{
    // Available Payment Modes
    const NEFT = 'NE';
    const RTGS = 'RT';
    const IMPS = 'PA';
    const IFT  = 'FT';

    // Currency type used in transfer file
    const CURRENCY = 'INR';

    // Corp code of Razorpay provided by bank
    const CORP_CODE = 'RAZORPAY';

    // To be used while adding bene
    const PRIME_CORP_CODE = 'RAZORPAY';

    const DEBIT_ACCOUNT_NUMBER = '917020041206002';
    //
    // Identifier of payment file.
    // It'll be always `P` indicating that it's a payment file
    //
    const IDENTIFIER = 'P';

    const MODE_MAPPING = [
        Mode::NEFT    => self::NEFT,
        Mode::RTGS    => self::RTGS,
        Mode::IFT     => self::IFT,
        Mode::IMPS    => self::IMPS,
    ];
}
