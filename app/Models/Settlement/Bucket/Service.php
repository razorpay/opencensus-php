<?php

namespace RZP\Models\Settlement\Bucket;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function fillSettlementBucket(array $input)
    {
        return (new Core)->backfillSettlementBucket($input);
    }

    public function deleteCompletedBucketEntries(array $input)
    {
        return (new Core)->deleteCompletedBucketEntries($input);
    }
}
