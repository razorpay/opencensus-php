<?php

namespace RZP\Models\Payout\Processor;

use RZP\Models\Payout;
use RZP\Models\Settlement;
use RZP\Models\Transaction;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Balance\AccountType;

class FundAccountPayout extends Base
{
    /**
     * {@inheritDoc}
     * After payout creation dispatches transaction.created event.
     */
    public function createPayout(array $input): Payout\Entity
    {
        $payout = parent::createPayout($input);

        //
        // In case of payouts with status=(queued, payouts), we don't create the transaction yet.
        // This event will be dispatched later when we are actually processing the payout.
        //
        if ($payout->isStatusBeforeCreate() === false)
        {
            //
            // Ideally, this should be done as part of downstream processor,
            // but we do it here since, we do not want to dispatch this even if
            // payout creation flow fails for any reason after downstream processor runs.
            //

            if (($payout->isStatusBeforeCreate() === false) and
                ($payout->balance->getAccountType() !== AccountType::DIRECT))
            {
                (new Transaction\Core)->dispatchEventForTransactionCreated($payout->transaction);
            }
        }

        return $payout;
    }
}
