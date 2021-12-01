<?php

namespace RZP\Models\Settlement\Ondemand\Transfer;

use Config;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer;
use RZP\Models\Settlement\Ondemand;
use RZP\Models\Settlement\Ondemand\Bulk;
use RZP\Models\Settlement\OndemandPayout;
use RZP\Models\Settlement\Ondemand\Attempt;
use RZP\Jobs\SettlementOndemand\CreateSettlementOndemandBulkTransfer;
use RZP\Jobs\SettlementOndemand\CreateSettlementOndemandBulkTransfer as BulkJob;

class Core extends Base\Core
{
    const MIN_SPLIT_AMOUNT = 10000;

    public function createSettlementTransfer($amount, $mode = FundTransfer\Mode::NEFT)
    {
        $this->trace->info(TraceCode::SETTLEMENT_ONDEMAND_TRANSFER_CREATE, [
            'amount' => $amount,
        ]);

        $data = [
            Entity::AMOUNT    => $amount,
            Entity::ATTEMPTS  => 0,
            Entity::STATUS    => Ondemand\Status::CREATED,
            Entity::MODE      => $mode,
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

    public function createMultipleSettlementOndemandTransfer($settlementOndemand)
    {
        $splitAmounts = $this->splitAmount($settlementOndemand->getAmountToBeSettled());

        $transfers = [];

        $attempts = [];

        foreach($splitAmounts as $splitAmount)
        {
            [$transfer, $attempt] = $this->createSettlementTransfer($splitAmount, FundTransfer\Mode::IMPS);

            array_push($transfers, $transfer);

            array_push($attempts, $attempt);
        }

        return [$transfers, $attempts];
    }

    public function splitAmount($amount)
    {
        $totalAmountRemaining = $amount;

        $splitAmount = [];

        $maxIMPSLimit = OndemandPayout\Core::MAX_IMPS_AMOUNT;

        while($totalAmountRemaining > 0)
        {
            if ($totalAmountRemaining > $maxIMPSLimit)
            {
                $totalAmountRemaining -= $maxIMPSLimit;

                $payoutAmount = $maxIMPSLimit;

                if(($totalAmountRemaining < self::MIN_SPLIT_AMOUNT) and ($totalAmountRemaining > 0))
                {
                    $payoutAmount -= self::MIN_SPLIT_AMOUNT;

                    $totalAmountRemaining += self::MIN_SPLIT_AMOUNT;
                }

                array_push($splitAmount, $payoutAmount);
            }
            else
            {
                array_push($splitAmount, $totalAmountRemaining);

                $totalAmountRemaining = 0;
            }
        }

        return $splitAmount;
    }

    public function updateStatusAttemptsAndPayoutId($payoutStatus, $settlementOndemandTransfer, $payoutId)
    {
        $settlementOndemandTransfer->setPayoutId($payoutId);

        $presentAttempts = $settlementOndemandTransfer->getAttempts();

        $settlementOndemandTransfer->setAttempts($presentAttempts + 1);

        $this->updateStatusAndRetryIfRequired($payoutId, $payoutStatus, $settlementOndemandTransfer);
    }

    public function updateStatusAndRetryIfRequired($payoutId, $payoutStatus, $settlementOndemandTransfer)
    {
        $settlementOndemandTransfer->setPayoutId($payoutId);

        switch ($payoutStatus)
        {
            case Status::REVERSED:
                $this->handleReversedAndRetryIfRequired($settlementOndemandTransfer);
                break;
            case Status::PROCESSED:
                $this->setProcessed($settlementOndemandTransfer);
                break;
            case Status::PROCESSING:
                $this->setProcessing($settlementOndemandTransfer);
                break;
            default:
                throw new Exception\InvalidArgumentException(
                    'not a valid ondemand_transfer status');
        }
    }

    public function handleReversedAndRetryIfRequired($settlementOndemandTransfer)
    {
        if ($settlementOndemandTransfer->canRetry())
        {
            $this->setProcessing($settlementOndemandTransfer);

            $this->retry($settlementOndemandTransfer);

            return;
        }

        $settlementOndemandTransfer->setStatus(Status::REVERSED);

        $settlementOndemandTransfer->setReversedAt(Carbon::now(Timezone::IST)->getTimestamp());

        $settlementOndemandTransfer->setProcessedAt(null);

        $this->repo->saveOrFail($settlementOndemandTransfer);
    }

    public function setProcessed($settlementOndemandTransfer)
    {
        $settlementOndemandTransfer->setStatus(Status::PROCESSED);

        $settlementOndemandTransfer->setProcessedAt(Carbon::now(Timezone::IST)->getTimestamp());

        $this->repo->saveOrFail($settlementOndemandTransfer);
    }

    public function setProcessing($settlementOndemandTransfer)
    {
        $settlementOndemandTransfer->setStatus(Status::PROCESSING);

        $this->repo->saveOrFail($settlementOndemandTransfer);
    }

    public function setLastAttemptAt($settlementOndemandTransfer, $time)
    {
        $settlementOndemandTransfer->setLastAttemptAt($time);

        $this->repo->saveOrFail($settlementOndemandTransfer);
    }

    public function markAsProcessed($settlementOndemandTransfer)
    {
        if($settlementOndemandTransfer->getStatus() === Status::REVERSED)
        {
            $this->repo->transaction(function () use ($settlementOndemandTransfer)
            {
                $attempt = (new Attempt\Core)->createAttempt($settlementOndemandTransfer);

                $attempt->setStatus(Status::PROCESSED);

                $this->repo->saveOrFail($attempt);

                $settlementOndemandTransfer->setPayoutId(null);

                $settlementOndemandTransfer->setStatus(Status::PROCESSED);

                $settlementOndemandTransfer->setAttempts($settlementOndemandTransfer->getAttempts() + 1);

                $this->repo->saveOrFail($settlementOndemandTransfer);
            });
        }
    }

    public function retry($settlementOndemandTransfer)
    {
        $settlementOndemandAttempt = (new Attempt\Core)->createAttempt($settlementOndemandTransfer);

        CreateSettlementOndemandBulkTransfer::dispatch(
            $this->mode,
            $settlementOndemandAttempt->getId(),
            $settlementOndemandTransfer,
            Config::get('applications.razorpayx_client.live.ondemand_x_merchant.id'));

    }
}
