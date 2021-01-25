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

    /**
     * select `user_id` from `merchant_users`
     *         where `merchant_id` in (?)
     *         and `product` = ?
     *         order by `merchant_id` asc, `product` asc
     *
     * @param array $merchantIds
     *
     * @return Base\PublicCollection
     */
    public function fetchAllBankingUserIdsForMerchantIds(array $merchantIds): Base\PublicCollection
    {
        return $this->newQuery()
                    ->select(Entity::USER_ID)
                    ->whereIn(Entity::MERCHANT_ID, $merchantIds)
                    ->where(Entity::PRODUCT, 'banking')
                    ->orderBy(Entity::MERCHANT_ID)
                    ->orderBy(Entity::PRODUCT)
                    ->get();

    }

    /**
     * returns the products used for given merchant ids and product as an array in the form of {merchant_id1, product1}
     *
     * @param array $merchantIds
     * @param $product
     * @param null $limit
     *
     * @return Base\PublicCollection
     */
    public function fetchProductUsedForMerchantIds(array $merchantIds, $product, $limit = null): Base\PublicCollection
    {
        $query =  $this->newQuery()
                       ->select(Entity::MERCHANT_ID, Entity::PRODUCT)
                       ->whereIn(Entity::MERCHANT_ID, $merchantIds)
                       ->groupBy(Entity::MERCHANT_ID, Entity::PRODUCT);

        if (empty($product) === false)
        {
            $query->where(Entity::PRODUCT, $product);
        }

        if (empty($limit) === false)
        {
            $query->take($limit);
        }

        return $query->get();
    }
}
