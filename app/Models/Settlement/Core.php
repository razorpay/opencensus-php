<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Diag\EventCode;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Adjustment;
use RZP\Models\Transaction;
use RZP\Constants\Timezone;
use RZP\Jobs\Settlement\Bucket;
use RZP\Models\Merchant\Balance;
use RZP\Models\FundTransfer\Attempt;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Merchant as MerchantModel;
use RZP\Models\Settlement\Bucket\Preference;
use RZP\Jobs\Settlement\TransactionMigration;

class Core extends Base\Core
{
    public function retrieveById($id)
    {
        Entity::verifyIdAndStripSign($id);

        $setl = $this->repo->settlement->findOrFail($id);

        return $setl;
    }

    public function postInitiateTransfer(array $input): array
    {
        (new Validator)->validateInput('nodal_transfer', $input);

        if (isset($input[Payment\Entity::GATEWAY]) === true)
        {
            $gateway = $input[Entity::GATEWAY];

            $channel = Channel::getChannelFromGateway($gateway);

            $amount = $this->getAmountFromPaymentsForLastDay($gateway);
        }
        else
        {
            $amount = $input[Entity::AMOUNT];

            $channel = $input[Entity::CHANNEL];
        }

        $response = [
            'message' => 'Amount to be transferred is zero or negative'
        ];

        if ($amount > 0)
        {
            $destination = $input[Entity::DESTINATION];

            $merchantId = NodalAccount::ACCOUNT_MAP[$this->mode][$destination];

            $adjInput = [
                Adjustment\Entity::MERCHANT_ID  => $merchantId,
                Adjustment\Entity::AMOUNT       => $amount,
                Adjustment\Entity::CHANNEL      => $channel,
                Adjustment\Entity::DESCRIPTION  => 'Nodal Nodal Transfer',
                Adjustment\Entity::CURRENCY     => 'INR'
            ];

            $adjustment = (new Adjustment\Service)->addAdjustment($adjInput);

            return $adjustment;
        }

        return $response;
    }

    public function addBeneficiary(string $channel, array $input)
    {
        (new Validator)->validateInput($channel . '_add_beneficiary', $input);

        return $this->getNodalAccount($channel)->addBeneficiary($input);
    }

    public function updateChannel(array $input): array
    {
        (new Validator)->validateInput('update_channel', $input);

        $settlementIds = $input['settlement_ids'];

        Entity::verifyIdAndStripSignMultiple($settlementIds);

        $channel = $input['channel'];

        $failedIds = [];

        $successIds = [];

        foreach ($settlementIds as $settlementId)
        {
            try
            {
                $transactionIds = $this->repo
                                       ->transaction
                                       ->fetch([Transaction\Entity::SETTLEMENT_ID => $settlementId])
                                       ->pluck(Transaction\Entity::ID)
                                       ->toArray();

                $this->repo->transaction(function () use ($settlementId, $transactionIds, $channel)
                {
                    $this->repo
                         ->settlement
                         ->updateChannel($settlementId, $channel);

                    $this->repo
                         ->transaction
                         ->updateChannelForSettlement($settlementId, $transactionIds, $channel);
                });

                $successIds[] = $settlementId;
            }
            catch (\Throwable $ex)
            {
                $failedIds[] = $settlementId;

                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::SETTLEMENTS_CHANNEL_UPDATE_FAILED,
                    [
                        'settlement_id' => $settlementId,
                        'channel'       => $channel,
                    ]);
            }
        }

        $response = [
            'channel'       => $channel,
            'total'         => count($settlementIds),
            'success'       => count($successIds),
            'failed'        => count($failedIds),
            'failed_ids'    => $failedIds,
        ];

        $this->trace->info(
            TraceCode::SETTLEMENTS_CHANNEL_BULK_UPDATE_RESPONSE,
            $response
        );

        return $response;
    }

    /**
     * @param Entity $entity
     * @param array  $ftaData
     * @throws Exception\LogicException
     */
    public function updateStatusAfterFtaRecon(Entity $entity, array $ftaData)
    {
        $attemptStatus = $ftaData[Attempt\Constants::FTA_STATUS];

        $attemptFailureReason = $ftaData[Attempt\Constants::FAILURE_REASON];

        $status = $this->getDerivedStatus($entity, $attemptStatus);

        $entity->setStatus($status);

        $entity->setUtr($ftaData[Attempt\Constants::UTR]);

        $entity->setFailureReason($attemptFailureReason);

        $this->repo->saveOrFail($entity);

        $batchFta = $entity->batchFundTransfer;

        $batchFtaId = null;

        //BatchFTA can be null in test mode
        if (empty($batchFta) === false)
        {
            $batchFtaId = $batchFta->getId();
        }

        $customProperties = [
            'merchant_id'                       => $ftaData['merchant_id'],
            'channel'                           => $entity->getChannel(),
            'fund_transfer_attempt_id'          => $ftaData['fta_id'],
            'batch_fund_transfer_attempt_id'    => $batchFtaId,
            'fund_transfer_attempt_mode'        => $ftaData['mode'],
            'fund_transfer_attempt_amount'      => $entity->getAmount(),
            'settlement_id'                     => $entity->getId(),
            'error_message'                     => $attemptFailureReason,
        ];

        $this->app['diag']->trackSettlementEvent(
            EventCode::SETTLEMENT_STATUS_UPDATED,
            null,
            null,
            $customProperties);
    }

    public function updateStatusAfterFtaInitiated(Entity $entity, Attempt\Entity $fta)
    {
        $entity->batchFundTransfer()->associate($fta->batchFundTransfer);

        $entity->setStatus(Status::INITIATED);

        $this->repo->saveOrFail($entity);
    }

    public function updateWithDetailsBeforeFtaRecon(Entity $entity, array $ftaData)
    {
        $entity->setUtr($ftaData[Attempt\Constants::UTR]);

        $entity->setRemarks($ftaData[Attempt\Constants::REMARKS]);

        $this->trace->info(
            TraceCode::FTA_RECON_SOURCE_UPDATED,
            [
                'source_id'         => $entity->getId(),
                'fta_id'            => $ftaData[Attempt\Constants::FTA_ID],
                'source_original'   => $entity->getOriginalAttributesAgainstDirty(),
                'source_dirty'      => $entity->getDirty(),
            ]);

        $this->repo->saveOrFail($entity);
    }

    public function getAccountBalance(string $channel): array
    {
        return $this->getNodalAccount($channel)->getAccountBalance();
    }

    /**
     * @param Entity $entity
     * @param string $ftaStatus
     * @return mixed|string
     * @throws Exception\LogicException
     */
    protected function getDerivedStatus(Entity $entity, string $ftaStatus)
    {
        switch ($ftaStatus)
        {
            case Attempt\Status::CREATED:
            case Attempt\Status::INITIATED:
                return $entity->getStatus();

            case Attempt\Status::FAILED:
                return Status::FAILED;

            case Attempt\Status::PROCESSED:
                return Status::PROCESSED;

            default:
                throw new Exception\LogicException('Unrecognized attempt status: ' . $ftaStatus);
        }
    }

    protected function getAmountFromPaymentsForLastDay(string $gateway) : int
    {
        $from = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $to = Carbon::today(Timezone::IST)->getTimestamp() - 1;

        // Get the amount for captured payments on gateway for last day
        $paymentAmount = $this->repo->payment->getCapturedAmountByGateway($gateway, $from, $to);

        // Get the amount for refunds on gateway for last day
        $refundAmount = $this->repo->refund->getRefundedAmountByGateway($gateway, $from, $to);

        // amount to be transferred in paisa
        $amount = $paymentAmount - $refundAmount;

        // Transfer 99% of the derived amount
        $amount = 0.99 * $amount;

        return (int) $amount;
    }

    /**
     * Sends a webhook to the merchant for successfully settled payments
     *
     * @param Entity $settlement
     */
    public function triggerSettlementWebhook(Entity $settlement)
    {
        if ($this->shouldSendWebhook($settlement) === false)
        {
            return;
        }

        $eventPayload = [
            ApiEventSubscriber::MAIN => $settlement
        ];

        $this->app['events']->fire('api.settlement.processed', $eventPayload);

    }

    /**
     * Returns false,
     *   if the settlement was not processed, or,
     *   if the settlement was not made for a linked account.
     *
     * @param Entity $settlement
     *
     * @return bool
     */
    protected function shouldSendWebhook(Entity $settlement): bool
    {
        // Proceed only if the settlement has successfully processed
        if ($settlement->isStatusProcessed() === false)
        {
            return false;
        }

        // Proceed only if the settlement was made to a linked account
        if ($settlement->merchant->isLinkedAccount() === false)
        {
            return false;
        }

        return true;
    }

    /**
     * Creates the nodalAccount object for given channel
     *
     * @param string $channel channel name
     *
     * @return Object Nodal Account Object
     */
    public function getNodalAccount(string $channel)
    {
        $nodalClass = 'RZP\Models\FundTransfer\\' . ucwords($channel) . '\NodalAccount';

        return new $nodalClass();
    }

    public function updateEntityWithFtsTransferId(Entity $entity, $ftsTransferId)
    {
        if (empty($ftsTransferId) === false)
        {
            $entity->setFTSTransferId($ftsTransferId);

            $this->repo->saveOrFail($entity);
        }
    }

    /**
     * calculates the settlement amount fot given merchant and balanceType
     *
     * @param MerchantModel\Entity $merchant
     * @param Balance\Entity       $balance
     * @param int                  $timestamp
     * @return array
     */
    public function getMerchantSettlementAmount(
        MerchantModel\Entity $merchant,
        Balance\Entity $balance,
        int $timestamp = 0)
    {
        $isMerchantSettlementScheduled = ($timestamp !== 0);

        $timestamp = $this->getValidSettlementTime($timestamp);

        $amount = $this->repo
                       ->transaction
                       ->getMerchantSettlementAmount(
                            $merchant->getId(),
                            $balance,
                            $timestamp->getTimestamp())
                       ->toArray();

        $settlementAmount = (int) $amount['settlement_amount'];

        //
        // In case merchant is not bucketed and has valid settlement amount
        // then enqueue him for bucketing so the settlement can go as expected
        //
        if (($isMerchantSettlementScheduled === false) and
            ($settlementAmount <= $balance->getBalance()) and
            ($settlementAmount >= 100))
        {
            Bucket::dispatch($this->mode, '', $merchant->getId(), $timestamp->getTimestamp());
        }

        return [
            'settlement_amount'    => $settlementAmount,
            'next_settlement_time' => $timestamp->getTimestamp(),
        ];
    }

    /**
     * Gives the valid timestamp when the settlement will be processed
     * This takes are of holidays in case the bucket timestamp fell under holiday
     *
     * @param int $timestamp
     * @return Carbon
     */
    public function getValidSettlementTime(int $timestamp): Carbon
    {
        //
        // If there is not future bucket for settlement for the merchant then consider the current timestamp
        //
        $timestamp = ($timestamp === 0) ? Preference::getCeilTimestamp(Carbon::now(Timezone::IST)) :
                                            Carbon::createFromTimestamp($timestamp, Timezone::IST);

        //
        // If the timestamp given is a holiday then calculate the next working day
        // This situation can come up if the holiday is marked at last moment
        // also set the hour anchor to 9 AM as in that settlement cycle
        // we'll be settling the amount for this merchant
        //
        if (Holidays::isWorkingDay($timestamp) === false)
        {
            $timestamp = Holidays::getNthWorkingDayFrom($timestamp, 1)->addHours(9);
        }

        return $timestamp;
    }

    public function enqueueForReplay(array $input)
    {
        $opt = [
            'from'                => $input['from'] ?? null,
            'to'                  => $input['to'] ?? null,
            'balance_type'        => $input['balance_type'],
            'transaction_ids'     => $input['transaction_ids'] ?? [],
        ];

        foreach ($input['merchant_ids'] as $mid)
        {
            TransactionMigration::dispatch($this->mode, $mid, $opt);
        }
    }
}
