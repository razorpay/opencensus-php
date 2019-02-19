<?php

namespace RZP\Models\FundTransfer\Yesbank;

use RZP\Models\FundTransfer;
use RZP\Models\FundTransfer\Yesbank\Request\Constants;

class Mode extends FundTransfer\Mode
{
    public static $modeMap = [
        self::RTGS => self::RTGS,
        self::IMPS => self::IMPS,
        self::NEFT => self::NEFT,
        self::IFT  => Constants::FT,
    ];
}
