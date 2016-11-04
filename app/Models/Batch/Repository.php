<?php

namespace RZP\Models\Batch;

use RZP\Models\Base;
use RZP\Models\Batch\Entity as Batch;
use RZP\Models\Batch\Status;
use RZP\Exception;
use RZP\Constants\Table;

class Repository extends Base\Repository
{
    protected $entity = 'batch';

    protected $proxyFetchParamRules = array(
        Entity::TYPE           => 'sometimes|in:refund',
    );

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID        => 'sometimes|alpha_num',
        Entity::TYPE               => 'sometimes|in:refund',
        Entity::STATUS             => 'sometimes|in:created,processing,processed',
    );

    public function findUnprocessedEntries($limit = 10)
    {
        $status = array(Status::CREATED, Status::PROCESSING);

        return $this->newQuery()
                    ->whereIn(Batch::STATUS, $status)
                    ->oldest()
                    ->limit($limit)
                    ->get();
    }
}
