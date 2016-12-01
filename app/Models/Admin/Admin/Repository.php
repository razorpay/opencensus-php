<?php

namespace RZP\Models\Admin\Admin;

use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Base;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Group;

class Repository extends Base\Repository
{
    protected $entity = 'admin';

    public function findByEmail($email)
    {
        $email = strtolower($email);

        return $this->newQuery()
                    ->where(Entity::EMAIL, '=', $email)
                    ->first();
    }

    public function retrieveByOrgIdAndIdOrFail(
        string $orgId,
        string $adminId)
    {
        return $this->newQuery()
                    ->where(Entity::ORG_ID, '=', $orgId)
                    ->where(Entity::ID, '=', $adminId)
                    ->with('groups')
                    ->with('roles')
                    ->firstOrFail();
    }

    public function lockUnactivatedAccounts($timestamp)
    {
        return $this->newQuery()
                    ->whereNull(Entity::LAST_LOGIN)
                    ->where(Entity::CREATED_AT, '<=', $timestamp)
                    ->update([
                        Entity::LOCKED => true,
                    ]);
    }

    public function lockUnusedAccounts($timestamp)
    {
        return $this->newQuery()
                    ->where(Entity::LAST_LOGIN, '<=', $timestamp)
                    ->update([
                        Entity::LOCKED => true,
                    ]);
    }
}
