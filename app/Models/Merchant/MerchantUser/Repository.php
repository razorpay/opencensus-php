<?php

namespace RZP\Models\Merchant\MerchantUser;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::MERCHANT_USER;

    //
    // Default order defined in RepositoryFetch is created_at, id
    // Overriding here because pivot table does not have an id col.
    //
    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::CREATED_AT, 'desc');
    }

    //Returns an array of distinct merchant ids a user id is associated with
    public function returnMerchantIdsForUserId(string $userId, int $limit = 100): array
    {
        return $this->newQuery()
                    ->select(Entity::MERCHANT_ID)
                    ->where(Entity::USER_ID, $userId)
                    ->limit($limit)
                    ->distinct()
                    ->get()
                    ->pluck(Entity::MERCHANT_ID)
                    ->toArray();
    }

    public function findByRolesAndMerchantId(array $roles, string $merchantId): Base\PublicCollection
    {
        return $this->newQuery()
                        ->select()
                        ->where(Entity::MERCHANT_ID, $merchantId)
                        ->whereIn(Constants\Entity::ROLE, $roles)
                        ->get();
    }
}
