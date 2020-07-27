<?php


namespace RZP\Models\VirtualVpaPrefix;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::VIRTUAL_VPA_PREFIX;

    public function getPrefixCount(string $prefix) : int
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->where(Entity::PREFIX, $prefix)
                    ->count(Entity::PREFIX);
    }

    public function getMerchantIdCount(string $merchantId) : int
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->count(Entity::MERCHANT_ID);
    }

    public function fetchEntityByMerchantId(string $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->first();
    }
}
