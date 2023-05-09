<?php

namespace RZP\Models\Merchant\MerchantUser;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\User\Role;
use RZP\Base\ConnectionType;
use RZP\Exception\LogicException;
use RZP\Constants\Table;
use RZP\Models\User\Entity as UserEntity;
use RZP\Models\Base\RepositoryUpdateTestAndLive;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Balance\Type as ProductType;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

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

    public function fetchMerchantIdsForUserIdsAndRole(array $userIds, string $role = Role::OWNER)
    {
        return $this->newQueryWithConnection($this->getDataWarehouseConnection())
            ->select(Entity::MERCHANT_ID)
            ->whereIn(Entity::USER_ID, $userIds)
            ->where(Entity::ROLE, $role)
            ->get()
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();
    }

    public function fetchMerchantIdForUserIdAndRole(string $userId, string $role = Role::OWNER)
    {
        return $this->newQuery()
                    ->select(Entity::MERCHANT_ID)
                    ->where(Entity::USER_ID, $userId)
                    ->where(Entity::ROLE, $role)
                    ->get()
                    ->pluck(Entity::MERCHANT_ID)
                    ->toArray();
    }

    public function fetchPrimaryMerchantIdsForUserIdAndRole(string $userId, string $role = Role::OWNER)
    {
        return $this->newQuery()
                    ->select(Entity::MERCHANT_ID)
                    ->where(Entity::USER_ID, $userId)
                    ->where(Entity::ROLE, $role)
                    ->where(Entity::PRODUCT, 'primary')
                    ->get()
                    ->pluck(Entity::MERCHANT_ID)
                    ->toArray();
    }

    public function fetchBankingSignUpTimeStampOfOwner(string $merchantId)
    {
        $query =  $this->newQuery()
                    ->select(Entity::CREATED_AT)
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::ROLE, Role::OWNER)
                    ->where(Entity::PRODUCT, 'banking')
                    ->orderBy(Entity::CREATED_AT);

        return $query->pluck(Entity::CREATED_AT)
            ->first();
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

    public function getAllUsersByMerchantId(string $merchantId)
    {
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->get();
    }

    public function isOwnerForUserId(string $userId): bool
    {
        return $this->newQuery()
            ->where(Entity::USER_ID, $userId)
            ->whereNotIn(Entity::ROLE, [ROLE::OWNER])
            ->count() === 0;
    }

    public function isOwnerRoleExistForUserIdAndProduct(string $userId, $product)
    {
        return $this->newQuery()
                ->where(Entity::USER_ID, $userId)
                ->where(Entity::ROLE, ROLE::OWNER)
                ->where(Entity::PRODUCT, $product)
                ->count() > 0;
    }

    public function getMerchantUserRoles(string $userID, string $merchantID)
    {
        return $this->newQuery()
                    ->where(Entity::USER_ID, $userID)
                    ->where(Entity::MERCHANT_ID, $merchantID)
                    ->get();
    }

    public function fetchMerchantUsersForUserIdRoleAndProduct(string $userId, array $roles, string $product, array $submerchantIds)
    {
        return $this->newQuery()
                    ->where(Entity::USER_ID, $userId)
                    ->where(Entity::PRODUCT, $product)
                    ->whereIn(Entity::ROLE, $roles)
                    ->whereIn(Entity::MERCHANT_ID, $submerchantIds)
                    ->get();
    }

    public function fetchMerchantIdForUserIdRoleAndProduct(string $userId, string $role, string $product) : array
    {
        return $this->newQuery()
                    ->where(Entity::USER_ID, $userId)
                    ->where(Entity::ROLE, $role)
                    ->where(Entity::PRODUCT, $product)
                    ->get()
                    ->pluck(Entity::MERCHANT_ID)
                    ->toArray();
    }

    public function fetchPrimaryUserIdForMerchantIdAndRole(string $merchantId, string $role='owner')
    {
        $query = $this->newQuery()
            ->select(Entity::USER_ID)
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::PRODUCT, 'primary')
            ->where(Entity::ROLE, $role)
            ->get();

        return $query->pluck(Entity::USER_ID)
            ->toArray();
    }

    public function getBankingUserCountByMerchantIdAndRoleIdsAsQuery(string $merchantID, array $roleIds)
    {
        $query = $this->newQuery()
            ->select($this->getTableName() . '.*')
            ->select(Entity::ROLE)
            ->selectRaw('COUNT( merchant_users.' . Entity::USER_ID . ') AS count')
            ->where(Entity::MERCHANT_ID, $merchantID)
            ->whereIn(Entity::ROLE, $roleIds)
            ->where(Entity::PRODUCT, 'banking')
            ->groupBy(Entity::ROLE);

        return $query->get();
    }

    public function checkIfBankingMerchantUsersAreLinkedToRoleId(string $merchantId, string $roleId) :bool
    {
        $merchantUsers = $this->newQuery()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::ROLE, $roleId)
            ->where(Entity::PRODUCT, 'banking')
            ->get();

        if($merchantUsers->isEmpty() === true)
        {
            return false;
        }
        return true;
    }

    public function getBankingUserCountByMerchantIdAndRoleIds(string $merchantId, array $roleIds)
    {

        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->whereIn(Entity::ROLE, $roleIds)
            ->where(Entity::PRODUCT, 'banking')
            ->get()->count();
    }

    public function fetchOwnerByMerchantIdAndBankingProduct(string $merchantId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::ROLE, 'owner')
            ->where(Entity::PRODUCT, 'banking')
            ->firstOrFail();
    }

    /**
     * Fetches all the merchant_users for given merchantIds and roles from the provided connection mode
     *
     * @param   array       $merchantIds    Merchant IDs
     * @param   array       $roles          User roles
     * @param   string|null $mode           connection mode
     *
     * @return  Base\PublicCollection
     */
    public function fetchMerchantUsersByMerchantIdsAndRoles(array $merchantIds, array $roles, string $mode = null)
    {
        $query = ($mode === null) ? $this->newQuery() : $this->newQueryWithConnection($mode);

        return $query->whereIn(Entity::MERCHANT_ID, $merchantIds)
                     ->where(Entity::ROLE, $roles)
                     ->whereIn(Entity::PRODUCT, ['primary', 'banking'])
                     ->get();
    }

    /**
     * Fetches all the merchant_users for given merchantIds and roles.
     * It fails if data is not in sync in test and live DB.
     *
     * @param   array       $merchantIds    Merchant IDs
     * @param   array       $roles          User roles
     *
     * @return  Base\PublicCollection
     * @throws  LogicException
     */
    public function fetchMerchantUsersByMerchantIdsInSyncOrFail(array $merchantIds, array $roles)
    {
        $liveEntities = $this->fetchMerchantUsersByMerchantIdsAndRoles($merchantIds, $roles, 'live');
        $testEntities = $this->fetchMerchantUsersByMerchantIdsAndRoles($merchantIds, $roles, 'test');
        $isSynced = $this->areEntitiesSyncOnLiveAndTest($liveEntities, $testEntities);
        if ($isSynced === true)
        {
            return $liveEntities;
        }
        else
        {
            $this->trace->critical(
                TraceCode::DATA_MISMATCH_ON_LIVE_AND_TEST,
                [
                    'on_live' => $liveEntities,
                    'on_test' => $testEntities
                ]
            );
            throw new LogicException("Data is not synced on Live and Test DB");
        }
    }
    public function getBankingUsersForMerchantRoles(array $merchantIdToRolesMapping): Base\PublicCollection
    {
        /*
         * select
         *      `merchant_users`.`user_id`,
         *      `users`.`name`,
         *      `users`.`email`,
         *      `merchants`.`name` as `business_name`,
         *      `merchants`.`id`,
         *      `merchant_users`.`role`
         * from `merchant_users`
         *      inner join `users` on `merchant_users`.`user_id` = `users`.`id`
         *      inner join `merchants` on `merchant_users`.`merchant_id` = `merchants`.`id`
         * where (
         *      (`product` = ? and `merchant_id` = ? and `role` in (?, ?))
         *      or (`product` = ? and `merchant_id` = ? and `role` in (?, ?))
         *      or (`product` = ? and `merchant_id` = ? and `role` in (?, ?))
         *      or (`product` = ? and `merchant_id` = ? and `role` in (?, ?))
         * )
         */

        $userIdColumn               = $this->repo->user->dbColumn(UserEntity::ID);
        $userNameColumn             = $this->repo->user->dbColumn(UserEntity::NAME);
        $userEmailColumn            = $this->repo->user->dbColumn(UserEntity::EMAIL);

        $merchantIdColumn           = $this->repo->merchant->dbColumn(MerchantEntity::ID);
        $merchantNameColumn         = $this->repo->merchant->dbColumn(MerchantEntity::NAME);

        $muRoleColumn               = $this->repo->merchant_user->dbColumn(Entity::ROLE);
        $muUserIdColumn             = $this->repo->merchant_user->dbColumn(Entity::USER_ID);
        $muMerchantIdColumn         = $this->repo->merchant_user->dbColumn(Entity::MERCHANT_ID);

        $userAttrs = [
            $muUserIdColumn,
            $userNameColumn,
            $userEmailColumn,
            $merchantNameColumn.' AS business_name',
            $muMerchantIdColumn,
            $muRoleColumn
        ];

        $query = $this->newQuery()
                      ->select($userAttrs)
                      ->join(Table::USER, $muUserIdColumn, '=', $userIdColumn)
                      ->join(Table::MERCHANT, $muMerchantIdColumn, '=', $merchantIdColumn)
                      ->where(function($query) use ($merchantIdToRolesMapping)
                      {
                          foreach ($merchantIdToRolesMapping as $mid => $roles)
                          {
                              $query->orWhere(function($query) use ($mid, $roles)
                              {
                                  $query->where(Entity::PRODUCT, ProductType::BANKING)
                                        ->where(Entity::MERCHANT_ID, $mid)
                                        ->whereIn(Entity::ROLE, $roles);
                              });
                          }
                      });

        return $query->get();
    }

    public function fetchMerchantUsersIdsByMerchantIds(array $merchantIds, string $mode = null)
    {
        $query = ($mode === null) ? $this->newQuery() : $this->newQueryWithConnection($mode);

        return $query->select(Entity::USER_ID)
                     ->whereIn(Entity::MERCHANT_ID, $merchantIds)
                     ->get()
                     ->pluck(Entity::USER_ID)
                     ->toArray();
    }

    public function checkUserForMerchantIds(array $merchantIds, string $userId): Base\PublicCollection
    {
        $userIdCol = $this->dbColumn(Entity::USER_ID);
        $merchantIdCol = $this->dbColumn(Entity::MERCHANT_ID);

        return $this->newQuery()
                    ->where($userIdCol, $userId)
                    ->whereIn($merchantIdCol, $merchantIds)
                    ->get();
    }
}
