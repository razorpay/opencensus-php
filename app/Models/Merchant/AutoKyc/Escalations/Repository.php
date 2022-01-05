<?php


namespace RZP\Models\Merchant\AutoKyc\Escalations;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'merchant_auto_kyc_escalations';

    public function fetchEscalationsForMerchant(string $merchantId){
        return $this->newQueryWithConnection($this->getMasterReplicaConnection())
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->get();
    }

    public function fetchEscalationsForType(string $type)
    {
        return $this->newQueryWithConnection($this->getMasterReplicaConnection())
            ->where(Entity::ESCALATION_TYPE, $type)
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->get();
    }

    public function fetchEscalationsForMerchants(array $merchantIds, string $type)
    {
        return $this->newQueryWithConnection($this->getMasterReplicaConnection())
            ->whereIn(Entity::MERCHANT_ID, $merchantIds)
            ->where(Entity::ESCALATION_TYPE, $type)
            ->get();
    }

    public function fetchMerchantIdsNotEscalatedToType(string $type)
    {
        $escalations = $this->fetchEscalationsForType($type);
        $excludeList = [];
        if(empty($escalations) === false)
        {
            $excludeList = $escalations
                ->pluck(Entity::MERCHANT_ID)
                ->toArray();
        }

        return $this->newQueryWithConnection($this->getMasterReplicaConnection())
            ->select(Entity::MERCHANT_ID)
            ->whereNotIn(Entity::MERCHANT_ID, $excludeList)
            ->whereIn(Entity::ESCALATION_TYPE, Constants::LOWER_ESCALATION_TYPE_MAP[$type])
            ->get()
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();
    }

    public function fetchEscalationsForMerchantAndTypeAndLevel(string $merchantId, string $type, string $level)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::ESCALATION_TYPE, $type)
            ->where(Entity::ESCALATION_LEVEL, $level)
            ->get()
            ->toArray();
    }
}
