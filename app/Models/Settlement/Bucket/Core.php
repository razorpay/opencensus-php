<?php

namespace RZP\Models\Settlement\Bucket;

use Cache;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Constants\Timezone;

class Core extends Base\Core
{
    protected $preference;

    public function __construct()
    {
        $this->preference = new Preference;

        parent::__construct();
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
            $currentTimestamp = Carbon::now(Timezone::IST);

            $offset = $currentTimestamp->minute - $currentTimestamp->second;

            // start of the hour will always be bucket timestamp
            $bucketTimestamp = $currentTimestamp->getTimestamp() - $offset;
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
     * @param string $merchantId
     * @param        $settlementTime
     */
    public function addMerchantToSettlementBucket(string $merchantId, $settlementTime)
    {
        list($status, $timestamp) = $this->preference
                                         ->getEarlySettlementBucketIfApplicable($merchantId, $settlementTime);

        if ($status === true)
        {
            $this->addToBucket($merchantId, $timestamp);

            return;
        }

        list($status, $timestamp) = $this->preference
                                         ->getMerchantSpecificBucket($merchantId, $settlementTime);

        if ($status === true)
        {
            $this->addToBucket($merchantId, $timestamp);

            return;
        }

        $currentTimestamp = Carbon::now(Timezone::IST);

        $bucketTimestamp = ($settlementTime < $currentTimestamp->getTimestamp()) ?
            Preference::getNextBucket($currentTimestamp->getTimestamp()) :
            Preference::getNextBucket($settlementTime);

        $this->addToBucket($merchantId, $bucketTimestamp);
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
     * creates entry in settlement bucket for the merchant id if its not already added to that bucket
     *
     * @param string $merchantId
     * @param int    $bucketTimestamp
     */
    public function addToBucket(string $merchantId, int $bucketTimestamp)
    {
        $data = [
            Entity::MERCHANT_ID      => $merchantId,
            Entity::BUCKET_TIMESTAMP => $bucketTimestamp,
        ];

        try
        {
            $entity = new Entity;

            $entity->fill($data);

            $entity->save();
        }
        catch (\Throwable $e)
        {
            // todo: use insert ignore or ignore this error
        }
    }
}
