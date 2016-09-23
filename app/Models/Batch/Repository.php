<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;
use RZP\Models\Batch\Entity as Batch;
use RZP\Models\Batch\Status;
use RZP\Exception;
use RZP\Constants\Table;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'batch';

    protected $proxyFetchParamRules = [
        Entity::MERCHANT_ID     => 'sometimes|alpha_num'
    ];

    public function findUnprocessedEntries($limit = 10)
    {
        $status = array(Status::CREATED, Status::PROCESSING);
        return $this->newQuery()
                    ->whereIn(Batch::STATUS, $status)
                    ->oldest()
                    ->limit($limit)
                    ->get();
    }

    public function getBatches($merchantId, $type, $skip, $take = 10)
    {
        return $this->newQuery()
                    ->latest()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::TYPE, '=', $type)
                    ->skip($skip)
                    ->take($take)
                    ->get();
    }
}
