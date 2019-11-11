<?php

namespace RZP\Models\Settlement\Bucket;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'settlement_bucket';

    public function getMerchantIdsFromBucket(string $balanceType, string $bucketTimestamp)
    {
        $bucketMerchantId   = $this->dbColumn(Entity::MERCHANT_ID);
        $bucketBalanceType  = $this->dbColumn(Entity::BALANCE_TYPE);
        $bucketTimestampCol = $this->dbColumn(Entity::BUCKET_TIMESTAMP);
        $bucketCompleted    = $this->dbColumn(Entity::COMPLETED);

        return $this->newQuery()
                    ->select([$bucketMerchantId, $bucketBalanceType])
                    ->where($bucketTimestampCol, '<=', $bucketTimestamp)
                    ->where($bucketCompleted, '=', 0)
                    ->where($bucketBalanceType, $balanceType)
                    ->distinct()
                    ->get();
    }

    public function markAsComplete(string $merchantId, string $balanceType, $timestamp)
    {
        return $this->newQuery()
                    ->where(Entity::BUCKET_TIMESTAMP, '<', $timestamp)
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::BALANCE_TYPE, $balanceType)
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
