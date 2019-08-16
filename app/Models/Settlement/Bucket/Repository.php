<?php

namespace RZP\Models\Settlement\Bucket;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'settlement_buckets';

    public function getMerchantIdsFromBucket(string $bucketTimestamp)
    {
        return $this->newQuery()
                    ->where(Entity::BUCKET_TIMESTAMP, $bucketTimestamp)
                    ->get();
    }
}
