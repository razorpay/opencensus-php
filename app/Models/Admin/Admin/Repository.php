<?php

namespace RZP\Models\Admin\Admin;

use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Base;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Group;

class Repository extends Base\Repository
{
    protected $entity = 'admin';

    protected $appFetchParamRules = [
        Entity::EMAIL => 'sometimes|email',
    ];

    public function findByEmail($email)
    {
        $email = strtolower($email);

        return $this->newQuery()
                    ->where(Entity::EMAIL, '=', $email)
                    ->firstOrFailPublic();
    }

    public function findByOrgIdAndEmail($orgId, $email, $relations = [])
    {
        $email = strtolower($email);

        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        return $this->newQuery()
                    ->orgId($orgId)
                    ->where(Entity::EMAIL, '=', $email)
                    ->with($relations)
                    ->first();
    }

    public function lockUnactivatedAccounts($timestamp)
    {
        return $this->newQuery()
                    ->whereNull(Entity::LAST_LOGIN_AT)
                    ->where(Entity::CREATED_AT, '<=', $timestamp)
                    ->update([
                        Entity::LOCKED => true,
                    ]);
    }

    public function lockUnusedAccounts($timestamp)
    {
        return $this->newQuery()
                    ->where(Entity::LAST_LOGIN_AT, '<=', $timestamp)
                    ->update([
                        Entity::LOCKED => true,
                    ]);
    }
}
