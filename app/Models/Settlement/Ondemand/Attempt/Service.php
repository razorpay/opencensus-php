<?php

namespace RZP\Models\Settlement\Ondemand\Attempt;

use RZP\Models\Settlement\Ondemand\Attempt\Repository;
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

    public function updatePayoutId(array $inputs)
    {
        foreach ($inputs as $input)
        {
            $settlementOndemandAttempt = (new Repository)->findById($input['settlement_ondemand_attempt_id']);

            $settlementOndemandAttempt->setPayoutId($input['payout_id']);

            $settlementOndemandTransfer = $settlementOndemandAttempt->settlementOndemandTransfer;

            $settlementOndemandTransfer->setPayoutId($input['payout_id']);

            $this->repo->transaction(function () use ($settlementOndemandTransfer, $settlementOndemandAttempt) {

                $this->repo->saveOrFail($settlementOndemandTransfer);

                $this->repo->saveOrFail($settlementOndemandAttempt);

            });
        }

        return [];
    }
}
