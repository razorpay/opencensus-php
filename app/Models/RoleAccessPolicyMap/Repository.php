<?php

namespace RZP\Models\RoleAccessPolicyMap;

use Illuminate\Support\Facades\DB;
use RZP\Models\Base;
use RZP\Constants\Table;

class Repository extends Base\Repository
{

    protected $entity = Table::ROLE_ACCESS_POLICY_MAP;

    public function findByRoleId($roleId)
    {
        return $this->newQuery()->where('role_id', '=', $roleId)->first();
    }

    public function findByRoleIds(array $roleIds)
    {
        return $this->newQuery()->whereIn('role_id', $roleIds)->get();
    }

    public function deleteAll()
    {
        $this->newQueryWithConnection('live')->truncate();

        $this->newQueryWithConnection('test')->truncate();
    }

    public function findAllRolesByAccessPolicyId($access_policy_id)
    {
        return $this->newQuery()->whereJsonContains('access_policy_ids', $access_policy_id)->get();
    }

    public function findOrFailByRoleId($roleId)
    {
        return $this->newQuery()
            ->where(Entity::ROLE_ID, '=', $roleId)
            ->firstOrFail();
    }
}


