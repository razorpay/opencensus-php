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


    public function fetchAllEscalatedMerchantIds()
    {
        return $this->newQueryWithConnection($this->getMasterReplicaConnection())
            ->select(Entity::MERCHANT_ID)
            ->distinct()
            ->get()
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();
    }

    public function fetchAllEscalatedMerchantIdsOfType($type)
    {
        return $this->newQueryWithConnection($this->getMasterReplicaConnection())
            ->select(Entity::MERCHANT_ID)
            ->where($this->dbColumn(Entity::ESCALATION_TYPE), '=', $type)
            ->distinct()
            ->get()
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();
    }


    public function fetchMerchantIdsNotEscalatedToType(string $type)
    {
        $allEscalatedMerchants = $this->fetchAllEscalatedMerchantIds();

        $typeEscalatedMerchants = $this->fetchAllEscalatedMerchantIdsOfType($type);

        $finalResult = array_diff($allEscalatedMerchants, $typeEscalatedMerchants);

        return $finalResult;
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
