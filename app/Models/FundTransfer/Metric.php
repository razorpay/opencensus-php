<?php

namespace RZP\Models\FundTransfer;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Metric extends Base\Core
{
    const PAYOUT_M2P_CREATED_TOTAL = "payout_m2p_created_total";

    public function pushM2PMerchantBlacklist($metricKey, $dimensions)
    {
        $startTime = millitime();
        app('trace')->histogram($metricKey, millitime() - $startTime, $dimensions);
    }

    public function pushM2PTransfersCount()
    {
        app('trace')->count(self::PAYOUT_M2P_CREATED_TOTAL);
    }
}
