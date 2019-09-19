<?php

namespace RZP\Models\Settlement\Bucket;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'settlement_bucket';

    public function getMerchantIdsFromBucket(string $bucketTimestamp)
    {
        return $this->newQuery()
                    ->select(Entity::MERCHANT_ID)
                    ->where(Entity::BUCKET_TIMESTAMP, '<=', $bucketTimestamp)
                    ->where(Entity::COMPLETED, '=', 0)
                    ->distinct()
                    ->get();
    }

    public function markAsComplete(string $merchantId, $timestamp)
    {
        return $this->newQuery()
                    ->where(Entity::BUCKET_TIMESTAMP, '<', $timestamp)
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->update([Entity::COMPLETED => 1]);
    }

    public function removeCompletedEntriesBeforeTimestamp($timestamp)
    {
        return $this->newQuery()
                    ->where(Entity::BUCKET_TIMESTAMP, '<=', $timestamp)
                    ->where(Entity::COMPLETED, 1)
                    ->delete();
    }
}
