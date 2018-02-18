<?php

namespace RZP\Models\FundTransfer\Base;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Base;
use RZP\Models\FundTransfer\Mode;

class NodalAccount extends Base\Core
{
    const MIN_RTGS_AMOUNT       = 200000;
    const RTGS_CUTOFF_HOUR      = 15;
    const RTGS_CUTOFF_MINUTE    = 45;

    protected function getTransferMode($amount): string
    {
        $rtgsCutoffTime = Carbon::createFromTime(
                                self::RTGS_CUTOFF_HOUR,
                                self::RTGS_CUTOFF_MINUTE,
                                0,
                                Timezone::IST)->getTimestamp();

        $now = Carbon::now(Timezone::IST)->getTimestamp();

        $mode = Mode::NEFT;

        if (($now <= $rtgsCutoffTime) and
            ($amount >= self::MIN_RTGS_AMOUNT))
        {
            $mode = Mode::RTGS;
        }

        return $mode;
    }

}
