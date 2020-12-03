<?php

namespace RZP\Models\Settlement\Ondemand\Transfer;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Settlement\Ondemand;
use RZP\Models\Settlement\Ondemand\Bulk;
use RZP\Models\Settlement\Ondemand\Attempt;
use RZP\Jobs\SettlementOndemand\CreateSettlementOndemandBulkTransfer as BulkJob;

class Core extends Base\Core
{
    public function createSettlementTransfer($amount)
    {
        $this->trace->info(TraceCode::SETTLEMENT_ONDEMAND_TRANSFER_CREATE, [
            'amount' => $amount,
        ]);

        $data = [
            Entity::AMOUNT    => $amount,
            Entity::ATTEMPTS  => 0,
            Entity::STATUS    => Ondemand\Status::CREATED,
            Entity::MODE      => 'NEFT',
        ];

        $transfer = (new Entity)->build($data);

        $transfer->generateId();

        $this->repo->saveOrFail($transfer);

        $attempt = (new Attempt\Core)->createAttempt($transfer);

        return [$transfer, $attempt];
    }

    public function createSettlementOndemandTransfer()
    {
        $bulks = (new Bulk\Core)->findSettlementOndemandBulksInPastCycle();

        if(count($bulks) !== 0)
        {
            $bulkAmount = 0;

            foreach ($bulks as $bulk)
            {
                $bulkAmount += $bulk->amount;
            }

            [$transfer, $attempt] = $this->createSettlementTransfer($bulkAmount);

            (new Bulk\Core)->fillTransferId($bulks, $transfer->getId());

            return [$attempt, $transfer];
        }

        return [null, null];
    }

    public function updateStatusAfterPayoutRequest($payoutStatus, $settlementOndemandTransfer, $payoutId)
    {
        $settlementOndemandTransfer->setPayoutId($payoutId);

        $presentAttempts = $settlementOndemandTransfer->getAttempts();

        $settlementOndemandTransfer->setAttempts($presentAttempts + 1);

        if($payoutStatus === Status::REVERSED and
           $settlementOndemandTransfer->getAttempts() > BulkJob::PAYOUT_REVERSAL_RETRY_LIMIT)
        {
            $this->setReversed($settlementOndemandTransfer);
        }

        else if ($payoutStatus === Status::PROCESSED)
        {
            $this->setProcessed($settlementOndemandTransfer);
        }
        else
        {
            $settlementOndemandTransfer->setStatus(Status::PROCESSING);

            $this->repo->saveOrFail($settlementOndemandTransfer);
        }
    }

    public function updateStatusAfterWebhookResponse($payoutStatus, $settlementOndemandTransfer)
    {
        if ($payoutStatus === Status::PROCESSED)
        {
           $this->setProcessed($settlementOndemandTransfer);
        }
        else if ($payoutStatus === Status::REVERSED and
                 $settlementOndemandTransfer->getAttempts() > BulkJob::PAYOUT_REVERSAL_RETRY_LIMIT)
        {
            $this->setReversed($settlementOndemandTransfer);
        }
        else
        {
            $settlementOndemandTransfer->setStatus(Status::PROCESSING);

            $this->repo->saveOrFail($settlementOndemandTransfer);
        }
    }

    public function setReversed($settlementOndemandTransfer)
    {
        $settlementOndemandTransfer->setStatus(Status::REVERSED);

        $settlementOndemandTransfer->setReversedAt(Carbon::now(Timezone::IST)->getTimestamp());

        $settlementOndemandTransfer->setProccesedAt(null);

        $this->repo->saveOrFail($settlementOndemandTransfer);
    }

    public function setProcessed($settlementOndemandTransfer)
    {
        $settlementOndemandTransfer->setStatus(Status::PROCESSED);

        $settlementOndemandTransfer->setProcessedAt(Carbon::now(Timezone::IST)->getTimestamp());

        $this->repo->saveOrFail($settlementOndemandTransfer);
    }

    public function setLastAttemptAt($settlementOndemandTransfer, $time)
    {
        $settlementOndemandTransfer->setLastAttemptAt($time);

        $this->repo->saveOrFail($settlementOndemandTransfer);
    }
}
