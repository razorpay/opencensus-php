<?php

namespace RZP\Models\Payout\SourceUpdater;

use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\PayoutSource\Entity as PayoutSourceEntity;

class PayoutLinkUpdater extends Base
{
    public function update()
    {
        try
        {
            $payoutLinkService = $this->app['payout-links'];

            $sourceDetails = $this->payout->getSourceDetails();

            foreach ($sourceDetails as $sourceDetail)
            {
                switch ($sourceDetail->getSourceType())
                {
                    case PayoutSourceEntity::PAYOUT_LINK:
                        $payoutLinkService->pushPayoutStatus($sourceDetail->getSourceId(), $this->payout->getId(), $this->payout->getStatus(), $this->mode);
                        break;
                }
            }
        }
        catch (\Exception $e)
        {
            $trace = $this->app['trace'];

            $trace->traceException($e,
                                   Trace::ERROR,
                                   TraceCode::PAYOUT_LINK_PAYOUT_UPDATER_ERROR,
                                   [
                                       'payout_id' => $this->payout->getPublicId(),
                                   ]);
        }
    }

}
