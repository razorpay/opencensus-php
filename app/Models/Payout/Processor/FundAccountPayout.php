<?php

namespace RZP\Models\Payout\Processor;

use RZP\Exception\BadRequestException;
use RZP\Models\Payout;
use RZP\Models\Settlement;
use RZP\Models\Transaction;

class FundAccountPayout extends Base
{
    /**
     * {@inheritDoc}
     * After payout creation dispatches transaction.created event.
     */
    public function createPayout(array $input): Payout\Entity
    {
        $payout = parent::createPayout($input);

        (new Transaction\Core)->dispatchEventForTransactionCreated($payout->transaction);

        return $payout;
    }

    /**
     * {@inheritDoc}
     */
    protected function setChannel($input = [])
    {
        $this->channel = Settlement\Channel::YESBANK;
    }

    protected function handleInsufficientFunds(BadRequestException $ex, Payout\Entity $payout)
    {
        if ($payout->toBeQueued() === false)
        {
            throw $ex;
        }

        $payout->setStatus(Payout\Status::QUEUED);
    }
}
