<?php

namespace RZP\Models\Admin\Admin;

use RZP\Constants\Table;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Base;
use RZP\Base\ConnectionType;
use RZP\Models\Admin\Permission;

class Repository extends Base\Repository
{
    protected $entity = 'admin';

    protected $appFetchParamRules = [
        Entity::EMAIL => 'sometimes|email',
    ];

    // TODO: Deprecate this function
    // This function shouldn't be used anywhere in the code since 1 email can be used under different orgs
    // use findByOrgIdAndEmail or getAdminFromId based on usecase
    // store admin_id instead of emails for admin identification, in email id based flow, all operations must be done
    // in the context of the org
    public function findByEmail($email)
    {
        $email = strtolower($email);

        return $this->newQuery()
                    ->where(Entity::EMAIL, '=', $email)
                    ->firstOrFailPublic();
    }

    public function getAdminFromId($id)
    {
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
                    ->where(Entity::ID, '=', $id)
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

    public function fetchByOrgIDAndEmailIDs($orgId, $emails)
    {
        Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        return $this->newQuery()
            ->orgId($orgId)
            ->whereIn(Entity::EMAIL, $emails)
            ->get();
    }

}
