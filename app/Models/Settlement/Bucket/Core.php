<?php

namespace RZP\Models\Settlement\Bucket;

use Cache;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Models\Merchant as ME;
use RZP\Models\Merchant\Balance;
use RZP\Models\Merchant\Preferences;
use RZP\Models\Merchant\Entity as MerchantEntity;

class Core extends Base\Core
{
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
}
