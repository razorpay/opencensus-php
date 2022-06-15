<?php

namespace  RZP\Models\AccessPolicyAuthzRolesMap;

use RZP\Models\Base;
use RZP\Constants;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = Constants\Table::ACCESS_POLICY_AUTHZ_ROLES_MAP;

    public function findByPrivilegeIdAndAction(string $privilegeId, string $action)
    {
        $query =  $this->newQuery()
            ->where(Entity::PRIVILEGE_ID, '=', $privilegeId)
            ->where(Entity::ACTION, '=', $action);

        return $query->first();
    }

    public function deleteAll()
    {
        $this->newQueryWithConnection('live')->truncate();

        $this->newQueryWithConnection('test')->truncate();
    }

    public function getAllAuthzRolesForAccessPolicyIds(array $accessPolicyIds) :array
    {
        $response = [];

        $accessPolicyMaps =  $this->newQuery()
            ->whereIn(Entity::ID, $accessPolicyIds)->get();

        foreach ($accessPolicyMaps as $accessPolicyMap)
        {
            $authzRoles = $accessPolicyMap->getAuthzRoles();

            if(empty($authzRoles) === true)
            {
                continue;
            }
            $response = array_merge($response, $authzRoles);
        }
        return array_values(array_unique($response));
    }

    public function getAccessPoliciesCountByIds($ids)
    {
         return $this->newQuery()
             ->whereIn(Entity::ID, $ids)
             ->get()->count();
    }

    public function findOrFailById($id)
    {
        return $this->newQuery()
            ->where(Entity::ID, '=', $id)
            ->firstOrFail();
    }

    protected function setBaseQueryIfApplicable(bool $useMasterConnection)
    {
        if ($useMasterConnection === true)
        {
            $mode = $this->app['rzp.mode'];
            $this->baseQuery = $this->newQueryWithConnection($mode)->useWritePdo();
        }
        else
        {
            $this->baseQuery = $this->newQueryWithConnection($this->getSlaveConnection());
        }
    }

}
