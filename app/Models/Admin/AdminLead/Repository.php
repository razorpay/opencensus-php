<?php

namespace RZP\Models\Admin\AdminLead;

use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Base;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Group;

class Repository extends Base\Repository
{
    protected $entity = 'admin_lead';

    protected $appFetchParamRules = [
        Entity::ADMIN_ID => 'sometimes|string|max:14',
        Entity::EMAIL    => 'sometimes|email',
        Entity::ORG_ID   => 'sometimes|string|max:14',
    ];

    protected $adminFetchParamRules = [
        Entity::ADMIN_ID => 'sometimes|string|max:14',
        Entity::EMAIL    => 'sometimes|email',
    ];

    public function findByTokenOrFail(string $token)
    {
        return $this->newQuery()
                    ->where(Entity::TOKEN, '=', $token)
                    ->firstOrFailPublic();
    }

    public function findByAdminId(string $adminId)
    {
        return $this->newQuery()
                    ->where(Entity::ADMIN_ID, '=', $adminId)
                    ->first();
    }
}
