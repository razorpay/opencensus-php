<?php


namespace RZP\Models\TrustedBadge;

use Carbon\Carbon;
use RZP\Models\Base;


class Repository extends Base\Repository
{
    protected $entity = 'trusted_badge';

    //
    // Default order defined in RepositoryFetch is created_at, id
    // Overriding here because pivot table does not have an id col.
    //
    protected function addQueryOrder($query): void
    {
        $query->orderBy(Entity::CREATED_AT, 'desc');
    }

    public function fetchByMerchantId($merchantId)
    {
        $query = $this->newQueryWithConnection($this->getSlaveConnection());

        return $query->merchantId($merchantId)->get()->first();
    }

    public function updateByMerchantId($merchantId, array $params): void
    {
        $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->update($params);
    }

    public function isMerchantLiveOnRTB($merchantId): bool
    {
        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->merchantId($merchantId)
            ->where(Entity::STATUS,'=', Entity::ELIGIBLE)
            ->where(Entity::MERCHANT_STATUS,'!=', Entity::OPTOUT);

        $merchantLiveOnRTB = $query->get()->first();

        return isset($merchantLiveOnRTB);
    }

    public function fetchRTBBlacklistedMerchantIds(): array
    {
        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->select(Entity::MERCHANT_ID)
            ->where(Entity::STATUS, Entity::BLACKLIST)
            ->get();

        return $query->pluck(Entity::MERCHANT_ID)->toArray();
    }
}
