<?php

namespace RZP\Models\OrderOutbox;

use App;
use DB;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Partitions;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Base\Traits\PartitionRepo;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends Base\Repository
{
    use PartitionRepo;

    protected $entity = 'order_outbox';

    protected function getPartitionStrategy() : string
    {
        return Partitions::DAILY;
    }

    protected function getDesiredOldPartitionsCount() : int
    {
        return 7;
    }

    public function fetchOldOutboxEntriesForRetry($limit, $startTimestamp, $endTimestamp)
    {
        return  $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::IS_DELETED, '=', false)
            ->where(Entity::CREATED_AT, '>=', $startTimestamp)
            ->where(Entity::CREATED_AT, '<=', $endTimestamp)
            ->where(Entity::RETRY_COUNT, '<', Constants::MAX_RETRY_COUNT)
            ->orderBy(Entity::CREATED_AT, 'DESC')
            ->limit($limit)
            ->get();
    }

    public function fetchByOrderId($orderID)
    {
        return $this->newQuery()
            ->where(Entity::ORDER_ID,'=', $orderID)
            ->orderBy(Entity::CREATED_AT, 'DESC')
            ->first();
    }

    public function fetchByOrderIdAndCreatedAt($orderID, $createdAt)
    {
        return $this->newQuery()
            ->where(Entity::ORDER_ID,'=', $orderID)
            ->where(Entity::CREATED_AT,'<=', $createdAt)
            ->orderBy(Entity::CREATED_AT, 'DESC')
            ->get();
    }
}
