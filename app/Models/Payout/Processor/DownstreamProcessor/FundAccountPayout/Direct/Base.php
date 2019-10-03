<?php

namespace RZP\Models\Payout\Processor\DownstreamProcessor\FundAccountPayout\Direct;

use RZP\Trace\TraceCode;
use RZP\Models\Payout\Entity;
use RZP\Models\Payout\Status;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Payout\Processor\DownstreamProcessor\FundAccountPayout;

class Base extends FundAccountPayout\Base
{
    public function process(Entity $payout, PublicEntity $ftaAccount)
    {
        $this->setChannel($payout);

        $queued = $this->queueIfLowBalance($payout);

        if ($queued === false)
        {
            $this->createFundTransferAttempt($payout, $ftaAccount);
        }
    }

    protected function queueIfLowBalance(Entity $payout) : bool
    {
        if ($payout->toBeQueued() === false)
        {
            return false;
        }

        $payoutAmount = $payout->getAmount();

        $merchantBalance = $payout->balance->getBalance();

        $hasBalance = ($merchantBalance >= $payoutAmount);

        if ($hasBalance === false)
        {
            $payout->setStatus(Status::QUEUED);

            $this->trace->info(
                TraceCode::PAYOUT_QUEUED,
                [
                    'payout_id'         => $payout->getId(),
                    'payout_amount'     => $payout->getAmount(),
                    'balance'           => $merchantBalance,
                    'queue_flag'        => $payout->toBeQueued(),
                    'batch_id'          => $payout->getBatchId()
                ]);

            return true;
        }

        return false;
    }
}
