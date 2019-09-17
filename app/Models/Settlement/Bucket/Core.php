<?php

namespace RZP\Models\Settlement\Bucket;

use Cache;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Models\Merchant\Preferences;
use RZP\Models\Merchant\Balance\Type;

class Core extends Base\Core
{
    protected $preference;

    public function __construct()
    {
        $this->preference = new Preference;

        parent::__construct();
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
            $this->addMerchantToSettlementBucket("", $record['merchant_id'], $record['settled_at']);
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
            'count' => count($result)
        ];
    }

    /**
     * will return all the merchant ids who's settlement has to go in given bucket
     *
     * @param null $bucketTimestamp
     * @return array
     */
    public function getMerchantIdsFromBucket($bucketTimestamp = null): array
    {
        // if the bucket timestamp is not given then derive the same for current timestamp
        if (empty($bucketTimestamp) === true)
        {
            $bucketTimestamp = Carbon::now(Timezone::IST)->getTimestamp();
        }

        $merchantIDs = $this->repo
                            ->settlement_bucket
                            ->getMerchantIdsFromBucket($bucketTimestamp)
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
        // check is the transaction can be settled
        $status = $this->isSettleableTransaction($transactionId);

        if ($status === false)
        {
            return false;
        }

        // check merchant specific conditions
        $status = $this->preference
                       ->skipMerchantSettlement($merchantId);

        if ($status === true)
        {
            return false;
        }

        // check early settlement preferences
        list($status, $timestamp) = $this->preference
                                         ->getEarlySettlementBucketIfApplicable($merchantId, $settlementTime);

        if ($status === true)
        {
            return $this->addToBucket($merchantId, $settlementTime, $timestamp);
        }

        // check merchant preference
        list($status, $timestamp) = $this->preference
                                         ->getMerchantSpecificBucket($merchantId, $settlementTime);

        if ($status === true)
        {
            return $this->addToBucket($merchantId, $settlementTime, $timestamp);
        }

        $currentTimestamp = Carbon::now(Timezone::IST);

        $bucketTimestamp = ($settlementTime < $currentTimestamp->getTimestamp()) ?
            Preference::getNextBucket($currentTimestamp->getTimestamp()) :
            Preference::getNextBucket($settlementTime);

        return $this->addToBucket($merchantId, $settlementTime, $bucketTimestamp);
    }

    /**
     * marks merchant settlement before give time as completed
     * if timestamp is not provided then timestamp is set to current time
     *
     * @param string $merchantId
     * @param null   $timestamp
     */
    public function markMerchantSettlementAsComplete(string $merchantId, $timestamp = null)
    {
        if (empty($timestamp) === true)
        {
            $timestamp = Carbon::now(Timezone::IST)->getTimestamp();
        }

        $this->repo
             ->settlement_bucket
             ->markAsComplete($merchantId, $timestamp);
    }

    /**
     * It'll add merchant to next available settlement bucket
     *
     * @param string $merchantId
     */
    public function addToNextBucket(string $merchantId)
    {
        $currentTimestamp = Carbon::now(Timezone::IST);

        $bucketTimestamp = Preference::getNextBucket($currentTimestamp->getTimestamp());

        $this->addToBucket($merchantId, $bucketTimestamp);
    }

    /**
     * returns true if the transaction belongs to settleable balance type
     * currently we settle only primary balance
     *
     * @param string $transactionId
     * @return bool
     */
    protected function isSettleableTransaction(string $transactionId): bool
    {
        // this is added only for back filling purpose.
        // should remove once done.
        if (empty($transactionId) === true)
        {
            return true;
        }

        $balanceType = $this->repo
                            ->transaction
                            ->getTransactionBalanceType($transactionId);

        //
        // currently we settlement only primary balance to merchant
        // if partner settlement has to be done then,
        // add a type to whitelist and have a type section in bucket
        //
        if (($balanceType === Type::PRIMARY) or ($balanceType === null))
        {
            return true;
        }

        return false;
    }

    /**
     * creates entry in settlement bucket for the merchant id if its not already added to that bucket
     *
     * @param string $merchantId
     * @param string $settlementTime
     * @param int    $bucketTimestamp
     * @return bool
     */
    public function addToBucket(string $merchantId, int $bucketTimestamp, $settlementTime = null): bool
    {
        $data = [
            Entity::MERCHANT_ID      => $merchantId,
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

        $this->trace->info(
            TraceCode::FAILED_TO_ADD_MERCHANT_TO_BUCKET,
            $traceData);

        return false;
    }
}
