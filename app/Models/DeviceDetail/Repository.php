<?php

namespace RZP\Models\DeviceDetail;

use DB;
use App\User;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\User\Role;
use RZP\Base\ConnectionType;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\MerchantUser;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Base\QueryCache\CacheQueries;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends Base\Repository
{
    use CacheQueries;

    use RepositoryUpdateTestAndLive;

    protected $entity = 'user_device_detail';

    public function fetchByMerchantIdAndUserId(string $merchantId, string $userId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::USER_ID, '=', $userId)
            ->first();
    }

    public function fetchByMerchantId(string $merchantId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->first();
    }

    public function fetchByAppsflyerId(string $appsflyerId)
    {
        return $this->newQuery()
            ->where(Entity::APPSFLYER_ID, '=', $appsflyerId)
            ->first();
    }
    public function fetchById(string $id)
    {
        return $this->newQuery()
                    ->where(Entity::ID, '=', $id)
                    ->first();
    }
    public function fetchNotNullSignupCampaignByMerchantId(string $merchantId)
    {
        $signupCampaignColumn = $this->repo->user_device_detail->dbColumn(Entity::SIGNUP_CAMPAIGN);

        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->whereNotNull($signupCampaignColumn)
                    ->first();
    }
    public function fetchByMerchantIdAndUserRole(string $merchantId, $role = Role::OWNER)
    {
        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $userIdColumn = $this->dbColumn(Entity::USER_ID);
        $merchantUserIdColumn = $this->repo->merchant_user->dbColumn(MerchantUser\Entity::USER_ID);
        $merchantUserRoleColumn = $this->repo->merchant_user->dbColumn(MerchantUser\Entity::ROLE);

        $connectionType = $this->getDataWarehouseConnection(ConnectionType::DATA_WAREHOUSE_MERCHANT);

        $query = $this->newQueryWithConnection($connectionType);

        $query = $query->select(DB::raw("/*+ MAX_EXECUTION_TIME(60000) */ *"));

        return $query->join(Table::MERCHANT_USER, $merchantUserIdColumn, '=', $userIdColumn)
                     ->where($merchantIdColumn, '=', $merchantId)
                     ->where($merchantUserRoleColumn, '=', $role)
                     ->first();
    }

    public function fetchByMerchantIdAndUserRoleFromMaster(string $merchantId, $role = Role::OWNER)
    {
        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $userIdColumn = $this->dbColumn(Entity::USER_ID);
        $merchantUserIdColumn = $this->repo->merchant_user->dbColumn(MerchantUser\Entity::USER_ID);
        $merchantUserRoleColumn = $this->repo->merchant_user->dbColumn(MerchantUser\Entity::ROLE);

        return $this->newQuery()
            ->join(Table::MERCHANT_USER, $merchantUserIdColumn, '=', $userIdColumn)
            ->where($merchantIdColumn, '=', $merchantId)
            ->where($merchantUserRoleColumn, '=', $role)
            ->first();
    }

    public function filterSignupCampaignAndSourceFromMerchantIdList(array $merchantIdList, string $signupCampaign, array $signupSources, $role = Role::OWNER)
    {
        // TODO Phantom Onboarding add for phantom_onboarding signup campaign as well
        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $merchantUserIdColumn = $this->repo->merchant_user->dbColumn(MerchantUser\Entity::USER_ID);
        $merchantUserRoleColumn = $this->repo->merchant_user->dbColumn(MerchantUser\Entity::ROLE);

        $connectionType = $this->getDataWarehouseConnection(ConnectionType::DATA_WAREHOUSE_MERCHANT);

        $query = $this->newQueryWithConnection($connectionType);

        $query = $query->select(DB::raw("/*+ MAX_EXECUTION_TIME(60000) */ *"));

        return $query->join(Table::MERCHANT_USER, $merchantUserIdColumn, '=', $this->dbColumn(Entity::USER_ID))
                     ->where($merchantUserRoleColumn, '=', $role)
                     ->whereIn($merchantIdColumn, $merchantIdList)
                     ->where(Entity::SIGNUP_CAMPAIGN, '=', $signupCampaign)
                     ->orWhereIn(Entity::SIGNUP_SOURCE, $signupSources)
                     ->distinct()
                     ->pluck($merchantIdColumn)
                     ->toArray();
    }

    public function removeSignupCampaignIdsFromMerchantIdList(array $merchantIdList, string $signupCampaign, $role = Role::OWNER)
    {
        // TODO Phantom Onboarding add for phantom_onboarding signup campaign as well
        $merchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $merchantUserIdColumn = $this->repo->merchant_user->dbColumn(MerchantUser\Entity::USER_ID);
        $merchantUserRoleColumn = $this->repo->merchant_user->dbColumn(MerchantUser\Entity::ROLE);

        $connectionType = $this->getDataWarehouseConnection(ConnectionType::DATA_WAREHOUSE_MERCHANT);

        $query = $this->newQueryWithConnection($connectionType);

        $query = $query->select(DB::raw("/*+ MAX_EXECUTION_TIME(60000) */ *"));

        $excludeMerchantIdList = $query->join(Table::MERCHANT_USER, $merchantUserIdColumn, '=', $this->dbColumn(Entity::USER_ID))
                                       ->where($merchantUserRoleColumn, '=', $role)
                                       ->whereIn($merchantIdColumn, $merchantIdList)
                                       ->where(Entity::SIGNUP_CAMPAIGN, '=', $signupCampaign)
                                       ->distinct()
                                       ->pluck($merchantIdColumn)
                                       ->toArray();

        return array_diff($merchantIdList, $excludeMerchantIdList);
    }

    public function fetchByUserId(string $userId)
    {
        return $this->newQuery()
            ->where(Entity::USER_ID, '=', $userId)
            ->first();
    }

    public function getDeviceDetailsForUserId($userId): array
    {
        return $this->newQuery()
            ->where(Entity::USER_ID, $userId)
            ->get()
            ->toArray();
    }

    public function updateDeviceDetailForUser($user)
    {
        $this->newQuery()
            ->where(Entity::USER_ID, '=', array_get($user, Entity::USER_ID))
            ->where(Entity::MERCHANT_ID, '=', array_get($user, Entity::MERCHANT_ID))
            ->update([
                Entity::SIGNUP_CAMPAIGN => array_get($user, Entity::SIGNUP_CAMPAIGN),
                Entity::SIGNUP_SOURCE => array_get($user, Entity::SIGNUP_SOURCE),
                Entity::METADATA => array_get($user, Entity::METADATA),
                Entity::APPSFLYER_ID => array_get($user, Entity::APPSFLYER_ID),
            ]);

        return $this->newQuery()
            ->where(Entity::USER_ID, '=', array_get($user, Entity::USER_ID))
            ->where(Entity::MERCHANT_ID, '=', array_get($user, Entity::MERCHANT_ID))
            ->first();
    }

    public function createDeviceDetailForUser(array $user)
    {
        return $this->create([
            Entity::APPSFLYER_ID        => array_get($user, Entity::APPSFLYER_ID),
            Entity::USER_ID             =>array_get($user, Entity::USER_ID),
            Entity::MERCHANT_ID         => array_get($user, Entity::MERCHANT_ID),
            Entity::SIGNUP_CAMPAIGN     => array_get($user, Entity::SIGNUP_CAMPAIGN),
            Entity::SIGNUP_SOURCE       => array_get($user, Entity::SIGNUP_SOURCE),
            Entity::METADATA            => array_get($user, Entity::METADATA),
        ]);
    }

    public function deleteByMerchantIdAndUserId(string $merchantId, string $userId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::USER_ID, '=', $userId)
            ->delete();
    }

}
