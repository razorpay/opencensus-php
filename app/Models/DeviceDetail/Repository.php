<?php

namespace RZP\Models\DeviceDetail;

use App\User;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\User\Role;
use RZP\Models\Merchant\MerchantUser;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'user_device_detail';

    public function fetchByMerchantIdAndUserId(string $merchantId, string $userId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::USER_ID, '=', $userId)
            ->first();
    }

    public function fetchByAppsflyerId(string $appsflyerId)
    {
        return $this->newQuery()
            ->where(Entity::APPSFLYER_ID, '=', $appsflyerId)
            ->first();
    }

    public function fetchByMerchantIdAndUserRole(string $merchantId, $role = Role::OWNER)
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
}
