<?php

namespace RZP\Models\RoleAccessPolicyMap;

use RZP\Models\Base;

class Service extends Base\Service
{

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function create(array $input) :array
    {
        $entity = $this->core->create($input);

        return $entity->toArrayPublic();
    }

    public function edit(array $input) :array
    {
        $entity = $this->core->edit($input);

        return $entity->toArrayPublic();
    }

    public function getAuthzRolesForRoleId(string $roleId) :array
    {
        $roleMap = $this->repo->role_access_policy_map->findByRoleId($roleId);

        if(empty($roleMap) === false)
        {
            return $roleMap->getAuthzRoles();
        }
        return [];
    }
}
