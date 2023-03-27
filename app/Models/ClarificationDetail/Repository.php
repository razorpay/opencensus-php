<?php


namespace RZP\Models\ClarificationDetail;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Base\ConnectionType;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'clarification_detail';

    public function hasClarificationDetailsForMerchantId($merchantId): bool
    {
        $result = $this->newQueryWithConnection($this->getReportingReplicaConnection())
                       ->where(Entity::MERCHANT_ID, $merchantId)
                       ->limit(1);

        return $result->count() > 0;
    }

    public function getByMerchantId($merchantId)
    {
        return $this->newQueryWithConnection($this->getReportingReplicaConnection())
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->get();
    }

    public function getByMerchantIdAndStatus($merchantId, string $status)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::STATUS, $status)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->get();
    }

    public function getByMerchantIdAndStatuses($merchantId, array $status)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->whereIn(Entity::STATUS, $status)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->get();
    }


    public function getLatestByMerchantIdAndGroup($merchantId, string $groupName)
    {
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::GROUP_NAME, $groupName)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->first();
    }

    public function getAllByMerchantIdAndGroup($merchantId, string $groupName)
    {
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::GROUP_NAME, $groupName)
                    ->get();
    }

    public function getAllByMerchantIdStatusAndGroup($merchantId, string $groupName, string $status)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::STATUS, $status)
                    ->where(Entity::GROUP_NAME, $groupName)
                    ->get();
    }

    public function getByMerchantIdAndStatusFromReplica($merchantId, string $status)
    {
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::STATUS, $status)
                    ->get();
    }
}
