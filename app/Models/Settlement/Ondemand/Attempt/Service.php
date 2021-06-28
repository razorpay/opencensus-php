<?php

namespace RZP\Models\Settlement\Ondemand\Attempt;
use RZP\Models\Base;

class Service extends Base\Service
{
    public function makeBulkPayoutRequest($settlementOndemandAttemptId, $currency, $settlementOndemandTransfer)
    {
        return $this->core()->makeBulkPayoutRequest($settlementOndemandAttemptId, $currency, $settlementOndemandTransfer);
    }

    public function updateStatusAfterPayoutRequest($payoutStatus, $payoutId, $settlementOndemandAttempt, $response, $failureReason)
    {
        return $this->core()->updateStatusAfterPayoutRequest($payoutStatus, $payoutId, $settlementOndemandAttempt, $response, $failureReason);
    }
}
