<?php

namespace RZP\Models\FundTransfer\Base;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Base;
use RZP\Models\FundTransfer\Mode;

class NodalAccount extends Base\Core
{
    const MIN_RTGS_AMOUNT = 200000;
    const RTGS_CUTOFF_HOUR = 16;

    protected function getTransferMode($amount)
    {
        $currentHour = Carbon::now(Timezone::IST)->hour;

        $mode = Mode::NEFT;

        if (($currentHour < self::RTGS_CUTOFF_HOUR) and
            ($amount >= self::MIN_RTGS_AMOUNT))
        {
            $mode = Mode::RTGS;
        }

        return $mode;
    }

}
