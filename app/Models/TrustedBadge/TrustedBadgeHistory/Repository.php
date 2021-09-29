<?php


namespace RZP\Models\TrustedBadge\TrustedBadgeHistory;

use RZP\Models\Base;


class Repository extends Base\Repository
{
    protected $entity = 'trusted_badge_history';

    public function fetchIsDelisted($merchantId, $firstEligibleTime)
    {
        $query = $this->newQueryWithConnection($this->getSlaveConnection());

        return $query->merchantId($merchantId)
                     ->where(Entity::STATUS, Entity::INELIGIBLE)
                     ->where(Entity::CREATED_AT, '>', $firstEligibleTime)
                     ->first();
    }

    public function fetchFirstEligible($merchantId)
    {
        $query = $this->newQueryWithConnection($this->getSlaveConnection());

        return $query->merchantId($merchantId)
                     ->where(Entity::STATUS, Entity::ELIGIBLE)
                     ->first();
    }

    /**
     * @param string $merchantId
     * @param string $status
     * @param string $merchantStatus
     * @return int
     */
    public function fetchHistoryCount(string $merchantId, string $status = '', string $merchantStatus = ''): int
    {
        $where = [Entity::MERCHANT_ID => $merchantId];

        if ($status !== '') {
            $where[Entity::STATUS] = $status;
        }

        if ($merchantStatus !== '') {
            $where[Entity::MERCHANT_STATUS] = $merchantStatus;
        }

        return $this->newQuery()->where($where)->count();
    }
}
