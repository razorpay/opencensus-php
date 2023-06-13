<?php

namespace RZP\Models\Settlement\Bucket;

use Cache;
use Config;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Jobs\Settlement\Bucket;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Models\Settlement\SlackNotification;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Models\Payment;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Models\Merchant as ME;
use RZP\Models\Merchant\Balance;
use RZP\Models\Currency\Currency;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Preferences;
use RZP\Models\Payment\Refund;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Jobs\Settlement\TransactionMigrationPublish;
use RZP\Models\Transfer\Constant as TransferConstant;
use RZP\Constants\Country;

class Core extends Base\Core
{
    const SETTLEMENT_TRANSACTION = 'settlement_transaction';

    protected $preference;

    public function __construct()
    {
        $this->preference = new Preference;

        parent::__construct();
    }

    /**
     * give next settlement time based on bucketing entry
     *
     * @param MerchantEntity $merchant
     * @param Balance\Entity $balance
     * @return int
     */
    public function getNextSettlementTime(MerchantEntity $merchant, Balance\Entity $balance): int
    {
        $bucket = $this->repo
                       ->settlement_bucket
                       ->getNextSettlementTime($merchant->getId(), $balance->getType());

        if ($bucket === null)
        {
            return 0;
        }

        return $bucket->getBucketTimestamp();
    }

    public function deleteCompletedBucketEntries(array $input): array
    {
        $this->trace->info(
            TraceCode::DELETING_COMPLETED_BUCKET_ENTRIES,
            $input);

        $timestamp = Carbon::now(Timezone::IST)->subDay();

        if (isset($input['timestamp']) === true)
        {
            $timestamp = $input['timestamp'];
        }

        $recordsDeletedCount = $this->repo
                                    ->settlement_bucket
                                    ->removeCompletedEntriesBeforeTimestamp($timestamp);

        $result = [
            'count' => $recordsDeletedCount,
        ];

        $this->trace->info(
            TraceCode::COMPLETED_BUCKET_ENTRIES_DELETED,
            $result);

        return $result;
    }

    public function backfillSettlementBucket(array $input)
    {
        // Time limit of 10 mins
        RuntimeManager::setTimeLimit(600);

        $currentTime = Carbon::now(Timezone::IST);

        $startTime = $currentTime->subMinutes($currentTime->minute)
                                 ->subSecond($currentTime->second)
                                 ->getTimestamp();
        $endTime = null;

        if (empty($input['start']) === false)
        {
            $startTime = $input['start'];
        }

        if (empty($input['end']) === false)
        {
            $endTime = $input['end'];
        }

        $this->trace->info(
            TraceCode::BUCKETING_INITIATE,
            [
                'start' => $startTime,
                'end'   => $endTime,
            ]);

        $featuredMids = $this->repo->feature->findMerchantsHavingFeatures([
            Feature\Constants::ES_AUTOMATIC,
            Feature\Constants::DAILY_SETTLEMENT,
            Feature\Constants::BLOCK_SETTLEMENTS,
        ])->pluck('entity_id')
          ->toArray();

        $featuredMids = array_merge($featuredMids, Preferences::NO_SETTLEMENT_MIDS);

        $result = $this->repo->transaction->getMerchantSettledAtTime($featuredMids, $startTime, $endTime);

        foreach ($result->toArray() as $record)
        {
            $this->addMerchantToSettlementBucket('', $record['merchant_id'], $record['settled_at']);
        }

        $this->trace->info(
            TraceCode::BUCKETING_DONE,
            [
                'count' => $result->count(),
                'start' => $startTime,
                'end'   => $endTime,
            ]
        );

        return [
            'count' => $result->count(),
        ];
    }

    /**
     * will return all the merchant ids who's settlement has to go in given bucket
     *
     * @param string $balanceType
     * @param null   $bucketTimestamp
     *
     * @return array
     */
    public function getMerchantIdsFromBucket(string $balanceType, $bucketTimestamp = null): array
    {
        // if the bucket timestamp is not given then derive the same for current timestamp
        if (empty($bucketTimestamp) === true)
        {
            $bucketTimestamp = Carbon::now(Timezone::IST)->getTimestamp();
        }

        $merchantIDs = $this->repo
                            ->settlement_bucket
                            ->getMerchantIdsFromBucket($balanceType, $bucketTimestamp)
                            ->pluck(Entity::MERCHANT_ID)
                            ->toArray();

        return [$bucketTimestamp, $merchantIDs];
    }

    /**
     * will add the merchant to settlement bucket which will be derive based on settlement time provided
     *
     * @param string $transactionId
     * @param string $merchantId
     * @param        $settlementTime
     * @return bool
     */
    public function addMerchantToSettlementBucket(string $transactionId, string $merchantId, $settlementTime): bool
    {
        $balanceType = $this->repo->transaction->getTransactionBalanceType($transactionId);

        if (Balance\Type::isSettleableBalanceType($balanceType) === false)
        {
            return false;
        }

        $balanceType = $balanceType ?? Balance\Type::PRIMARY;

        // check merchant specific conditions
        $status = $this->preference
                       ->skipMerchantSettlement($merchantId);

        if ($status === true)
        {
            return false;
        }

        $balance = $this->repo->balance->getMerchantBalanceByType($merchantId, $balanceType);

        $status = $this->shouldProcessViaNewService($merchantId, $balance);

        if ($status === true)
        {
            return false;
        }

        // check early settlement preferences
        list($status, $timestamp) = $this->preference
                                         ->getEarlySettlementBucketIfApplicable($merchantId, $settlementTime);

        if ($status === true)
        {
            return $this->addToBucket($merchantId, $timestamp, $balanceType, $settlementTime);
        }

        // check merchant preference
        list($status, $timestamp) = $this->preference
                                         ->getMerchantSpecificBucket($merchantId, $settlementTime);

        if ($status === true)
        {
            return $this->addToBucket($merchantId, $timestamp, $balanceType, $settlementTime);
        }

        $currentTimestamp = Carbon::now(Timezone::IST);

        $settlementTime = Carbon::createFromTimestamp($settlementTime, Timezone::IST);

        $settlementTime = Preference::getCeilTimestamp($settlementTime);

        $bucketTimestamp = ($settlementTime->getTimestamp() < $currentTimestamp->getTimestamp()) ?
            Preference::getNextBucket($currentTimestamp->getTimestamp()) :
            Preference::getNextBucket($settlementTime->getTimestamp());

        return $this->addToBucket($merchantId, $bucketTimestamp, $balanceType, $settlementTime);
    }

    /**
     * It will fetch the relevant data from transaction and publish it for settlement processing
     *
     * @param Transaction\Entity $txn
     * @param Balance\Entity|null $balance
     * @param bool $initialRamp
     */
    public function publishForSettlement(Transaction\Entity $txn, Balance\Entity $balance = null, $initialRamp = false)
    {
        $meta         = null;
        $balanceType  = ($balance === null) ? Balance\Type::PRIMARY : $balance->getType();

        // Only primary and commission balance are eligible for settlement
        if (Balance\Type::isSettleableBalanceType($balanceType) === false)
        {
            return;
        }

        //$settledBy by should be passed mandatory by the txn pushing service.
        $settledBy = 'Razorpay';

        // currently meta details present only for payment type
        if ($txn->isTypePayment() === true)
        {
            $payment = $txn->source;

            if ($payment->getSettledBy() !== 'Razorpay')
            {
                $settledBy = $payment->getSettledBy();
            }

            $meta = [
                'method'        => $payment->getMethod(),
                'international' => $payment->isInternational(),
            ];

            if (($payment->merchant->isLinkedAccount() === true) and
                ($payment->getMethod() === Payment\Method::TRANSFER))
            {
                try
                {
                    $this->addOriginMethodForLinkedAccount($payment, $meta);
                }
                catch(\Throwable $e)
                {
                    throw new Exception\LogicException('Either transfer not found or transfer source not found');
                }
            }

            if($payment->isInternational() === true)
            {
                $meta += [
                    'gateway'       => $payment->getGateway(),
                    'remitter_info' => [
                        "remitter_name"    => $this->getRemitterName($payment),
                        "remitter_address" => $this->getRemitterAddress($payment),
                        "remitter_country" => $this->getRemitterCountry($payment)
                    ],
                    'amount_meta'   => [
                        "conversion_amount"   => $this->getConversionAmount($payment, Currency::USD),
                        "conversion_currency" => Currency::USD,
                        "settlement_currency" => $this->getSettlementCurrencyOfPayment($payment)
                    ],
                ];

                if (Payment\Gateway::isOPGSPSettlementGateway($payment->getGateway()) === true &&
                    (empty($meta['remitter_info']['remitter_name']) or empty($meta['remitter_info']['remitter_address'])))
                {
                    $this->trace->info(
                        TraceCode::REMITTER_DETAILS_MISSING_FOR_INTL_PAYMENT_SETTLEMENT,
                        [
                            'payment_id'    => $payment->getId(),
                            'merchant_id'   => $payment->getMerchantId(),
                            'gateway'       => $payment->getGateway()
                        ]);

                    throw new Exception\LogicException('Remitter Name or Address not found for OPGSP Settlement Gateway');
                }
            }
        }

        // Add meta details for refund type txn
        if (($txn->isTypeRefund() === true) || ($txn->isTypeTransfer() === true))
        {
            $meta = $this->getMetaForSource($txn);
        }

        $onHoldReason = ($txn->getOnHold() === true) ? 'created with transaction on hold' : '';

        $payload = [
            'id'                => $txn->getId(),
            'merchant_id'       => $txn->getMerchantId(),
            'source_id'         => $txn->getEntityId(),
            'source_type'       => $txn->getType(),
            'balance_type'      => strtoupper($balanceType),
            'currency'          => $txn->getCurrency(),
            'credit'            => $txn->getCredit(),
            'debit'             => $txn->getDebit(),
            'fee'               => $txn->getFee(),
            'tax'               => $txn->getTax(),
            'settled_by'        => $settledBy,
            'on_hold'           => $txn->getOnHold(),
            'on_hold_reason'    => $onHoldReason,
            'meta'              => $meta,
        ];

        if ($initialRamp === true)
        {
            $payload['created_at'] = $txn->getCreatedAt();
        }

        try
        {
            $this->app['sns']->publish(json_encode($payload), self::SETTLEMENT_TRANSACTION);

            $this->trace->info(
                TraceCode::SETTLEMENT_SERVICE_TRANSACTION_PUSH_SUCCESSFUL,
                [
                    'transaction_id' => $txn->getId(),
                    'merchant_id'    => $txn->getMerchantId(),
                ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_TRANSACTION_STREAMING_FAILED,
                $payload);

            throw $e;
        }
    }

    private function getRemitterName(Payment\Entity $payment) {

        $remitterName = null;

        //If card payment get remitter name from card entity.
        if (($payment->isCard() === true) and ($payment->card !== null))
        {
            $remitterName = $payment->card->getName();
        }
       
        // If Payment is not a card Payment and If gateway is under ADDRESS_NAME_REQUIRED_GATEWAYS array
        // we will fetch remitter name from addresses table.
        else if (Payment\Gateway::isAddressAndNameRequiredGateway($payment->getGateway()) === true)
        {
            $billingAddress = $payment->fetchBillingAddress();
            
            if(empty($billingAddress) === false)
            {
                $remitterName = $billingAddress->getName();
            }
        }

        //if empty, try fetching from customer
        if ((empty($remitterName) === true) and ($payment->customer !== null)){
            $remitterName = $payment->customer->getName();
        }

        return $remitterName;
    }

    private function getRemitterAddress(Payment\Entity $payment)
    {
        $address = $payment->fetchBillingAddress();

        return (empty($address)===false)?$address->formatAsText():null;
    }

    /**

     * Returns Country Name from address saved in
     * addresses table linked with payment entity.
     *
     * @param Payment\Entity
     * @return String | null
     */

    private function getRemitterCountry(Payment\Entity $payment)
    {
        $address = $payment->fetchBillingAddress();

        if(empty($address) === true){
            return null;
        }

        return $address->getCountryNameFormatted();
    }

    private function getConversionAmount(Payment\Entity $payment, string $currency) {
        if ($payment->getGatewayCurrency() === $currency)
        {
            return $payment->getGatewayAmount();
        }

        return (new \RZP\Models\Currency\Core())->convertAmount($payment->getGatewayAmount(), $payment->getGatewayCurrency(), $currency);
    }

    protected function addOriginMethodForLinkedAccount(Payment\Entity $payment, array &$meta)
    {
        $transfer = $payment->transfer;

        $sourceType = $transfer->getSourceType();

        $sourcePayment = null;

        if ($sourceType === TransferConstant::PAYMENT)
        {
            $sourcePayment = $transfer->source;
        }
        else if ($sourceType === TransferConstant::ORDER)
        {
            $sourcePayment = $transfer->source->payments()->whereIn(Payment\Entity::STATUS, [Payment\Status::CAPTURED, Payment\Status::REFUNDED])->first();
        }
        else
        {
            //
            // $sourceType is `merchant` here, meaning this is a direct
            // transfer which does not have an associated source payment.
            //
            return ;
        }

        $meta += [
            'origin_method' => $sourcePayment->getMethod(),
        ];
    }

     /**
     * Returns Settlement Currency of Payment in Case of OPGSP
     * Settlements. Returns NULL in case of gateways not on
     * OGPSP Based Settlements.
     *
     * @param Payment\Entity
     * @return String | null
     */

    private function getSettlementCurrencyOfPayment(Payment\Entity $payment)
    {
        return Payment\Gateway::getSettlementCurrencyOfPaymentByGateway($payment);
    }


    protected function getMetaForSource(Transaction\Entity $txn)
    {
        $type = $txn->getType();

        try
        {
            $txnSource = $txn->source;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_SERVICE_TRANSACTION_SOURCE_FETCH_ERROR,
                [
                    'transaction_id' => $txn->getId(),
                    'source_id' => $txn->getEntityId()
                ]);

            if ($type === Transaction\Type::REFUND)
            {
                $txnSource = (new Refund\Repository())->fetchExternalRefundById($txn->getEntityId(), '', [], true);
            }
            else {
                throw $e;
            }
        }

        $international = false;

        switch ($type)
        {
            case Transaction\Type::REFUND:
                $metaSource     = $txnSource->payment;
                $international  = $metaSource->isInternational();
                break;

            case Transaction\Type::TRANSFER:
                if ($txnSource->getSourceType() === Transaction\Type::PAYMENT)
                {
                    $metaSource = $txnSource->source;
                    $international = $metaSource->isInternational();
                }
                else
                {
                    return null;
                }
                break;

            default:
                $metaSource = $txnSource;
        }

        return [
            'source_type'       => $metaSource->getEntity(),
            'source_id'         => $metaSource->getId(),
            'source_method'     => $metaSource->getMethod(),
            'source_settled'    => $metaSource->transaction->isSettled(),
            'international'     => $international
        ];
    }

    /**
     * marks merchant settlement before give time as completed
     * if timestamp is not provided then timestamp is set to current time
     *
     * @param ME\Entity $merchant
     * @param string    $balanceType
     * @param null      $timestamp
     */
    public function markMerchantSettlementAsComplete(ME\Entity $merchant, string $balanceType, $timestamp = null)
    {
        if (empty($timestamp) === true)
        {
            $timestamp = Carbon::now(Timezone::IST)->getTimestamp();
        }

        $this->repo->settlement_bucket->markAsComplete($merchant->getId(), $balanceType, $timestamp);
    }

    public function addToNextBucket(string $merchantId, string $balanceType = Balance\Type::PRIMARY)
    {
        $currentTimestamp = Carbon::now(Timezone::IST);

        $bucketTimestamp = Preference::getNextBucket($currentTimestamp->getTimestamp());

        $this->addToBucket($merchantId, $bucketTimestamp, $balanceType);
    }

    /**
     * creates entry in settlement bucket for the merchant id if its not already added to that bucket
     *
     * @param string $merchantId
     * @param int    $bucketTimestamp
     * @param string $balanceType
     * @param string $settlementTime
     *
     * @return bool
     */
    public function addToBucket(
        string $merchantId,
        int $bucketTimestamp,
        string $balanceType,
        $settlementTime = null): bool
    {
        $data = [
            Entity::MERCHANT_ID      => $merchantId,
            Entity::BALANCE_TYPE     => $balanceType,
            Entity::BUCKET_TIMESTAMP => $bucketTimestamp,
        ];

        $traceData = [
                'settled_at' => $settlementTime,
            ] + $data;

        try
        {
            $entity = new Entity;

            $entity->fill($data);

            $entity->save();

            $this->trace->info(
                TraceCode::MERCHANT_ADDED_TO_BUCKET,
                $traceData);

            return true;
        }
        catch (\Throwable $e)
        {
            // todo: use insert ignore or ignore this error
        }

        return false;
    }

    /**
     * check if the settlement should be skipped for the merchant because it is
     * being processed by the new service
     * @param string $merchantId
     * @param $balance
     * @return bool
     */
    public function shouldProcessViaNewService(string $merchantId, $balance = null)
    {
        $result = $this->repo->feature->getMerchantIdsHavingFeature(
            Feature\Constants::NEW_SETTLEMENT_SERVICE,
            [
                $merchantId
            ]);

        // TODO remove this when we migrate to the yes bank to new settlement service

        $balanceType = ($balance == null) ? Balance\Type::PRIMARY : $balance->getType();

        $status = (empty($result) === false);

        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_RAMP,
            [
                'merchant_id'  => $merchantId,
                'status'       => $status,
                'balance_type' => $balanceType,
            ]);

        return $status;
    }

    public function fetchAndEnqueueSettlableTransactionsBatch(string $mode, string $merchantId, array $opt)
    {
        $startTime = microtime(true);

        $batch = 0;
        $batchSize = 1000;

        $balance = $this->repo->balance->getMerchantBalanceByType($merchantId, $opt['balance_type']);

        $transactions = $this->repo->transaction->getSettlableTransactions($merchantId, $opt, $balance, true);

        $transactionIds = $transactions->getIds();

        $txnCount = sizeof($transactionIds);

        $transactionIdBatches = array_chunk($transactionIds, $batchSize);

        $pushJobStart= microtime(true);

        $totalPushTimeTaken=0;

        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_TRANSACTION_MIGRATION_DEBUG_LOG,
            [
                'merchant_id'            => $merchantId,
                'transactions_count'     => $txnCount,
                'push_job_start_time'    => $pushJobStart,
            ]);

        foreach ($transactionIdBatches as $transactionIdBatch)
        {
            $opt['transaction_ids'] = $transactionIdBatch;

            $currentPushStartTime = microtime(true);

            TransactionMigrationPublish::dispatch($mode, $merchantId, $opt);

            $timeforCurrentPush = microtime(true) - $currentPushStartTime;

            $totalPushTimeTaken=$totalPushTimeTaken+$timeforCurrentPush;

            $batch++;

            $this->trace->info(
                TraceCode::SETTLEMENT_SERVICE_TRANSACTION_MIGRATION_DEBUG_LOG,
                [
                    'merchant_id'            => $merchantId,
                    'batch_number'           => $batch,
                    'current_batch_push_time'=> $timeforCurrentPush,
                    'current_time_taken'     => $totalPushTimeTaken
                ]);
        }

        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_TRANSACTION_MIGRATION_BATCH_ENQUEUE_TIME_TAKEN,
            [
                'merchant_id'            => $merchantId,
                'batch_count'            => $batch,
                'transactions_count'     => $txnCount,
                'time_taken'             => microtime(true) - $startTime,
                'total_push_time_taken'  => $totalPushTimeTaken,
            ]);

        return [
            'batch_count' => $batch,
            'transactions_count' => $txnCount,
        ];
    }

    public function migrateSettlableTransactionsBatch(string $merchantId, array $opt)
    {
        $stat = [
            'total_count' => 0,
        ];

        $balance = $this->repo->balance->getMerchantBalanceByType($merchantId, $opt['balance_type']);

        $transactions = $this->repo->transaction->getSettlableTransactions($merchantId, $opt, $balance);

        $startTime = microtime(true);

        foreach($transactions as $txn)
        {
            if (isset($stat[$txn->getType()]) === false) {
                $stat[$txn->getType()] = [
                    'count'  => 0,
                    'amount' => 0,
                ];
            }

            $stat['total_count']++;
            $stat[$txn->getType()]['count']++;
            $stat[$txn->getType()]['amount'] += $txn->getCredit() - $txn->getDebit();

            try
            {
                $this->publishForSettlement($txn, $balance, $opt['initial_ramp']);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::SETTLEMENT_SERVICE_TRANSACTION_MIGRATION_FAILED,
                    [
                        'merchant_id'    => $merchantId,
                        'transaction_id' => $txn->getId(),
                    ]);
            }
        }

        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_TRANSACTION_MIGRATION_BATCH_PUBLISH_TIME_TAKEN,
            [
                'merchant_id' => $merchantId,
                'txn_count'   => $transactions->count(),
                'time_taken'  => microtime(true) - $startTime,
            ]);

        return $stat;
    }

    /**
     * This is used to call the settlements api based on the transaction hold and release
     * @param array $txnIds
     * @param string $reason
     */
    public function settlementServiceToggleTransactionHold($txnIds = [], $reason = null)
    {
        try
        {
            if ($reason != null)
            {
                app('settlements_api')->transactionHold($txnIds, $reason);
            }
            else
            {
                app('settlements_api')->transactionRelease($txnIds);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_SERVICE_CALL_FOR_TXN_ON_HOLD_CLEAR_FAILED,
                [
                    'transaction_ids'    => $txnIds,
                    'reason_for_hold'    => $reason,
                ]);

            $operation = 'Transactions on hold toggle failed to update in new settlement service';

            (new SlackNotification)->send(
                $operation,
                $txnIds,
                $e,
                1,
                'settlement_alerts');
        }
    }
}
