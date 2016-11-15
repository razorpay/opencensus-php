<?php

namespace RZP\Models\Admin\Role;

use RZP\Models\Admin\Org\Entity as Org;
use RZP\Models\Admin\Permission;
use RZP\Models\Base;

class Core extends Base\Core
{
    public function create($orgId, array $input)
    {
        $role = (new Entity)->build($input);

        $role->getValidator()->validateCreateInput($orgId, $input);

        $org = $this->repo->org->findOrFailPublic($orgId);

        $role->org()->associate($org);

        $this->repo->saveOrFail($role);

        if (isset($input['permissions']) === true)
        {
            $perms = $this->repo->permission->retrieveByIds($input['permissions']);

            foreach ($perms as $perm)
            {
                $role->permissions()->attach($perm);
            }

            $this->repo->saveOrFail($role);
        }

        $role = $this->repo->role->retrieveByOrgIdAndIdOrFail($orgId, $role->getId());

        return $role;
    }

    public function edit(string $orgId, string $roleId, array $input)
    {
        $role = $this->repo->role->retrieveByOrgIdAndIdOrFail($orgId, $roleId);

        $role->edit($input);

        if (isset($input['permissions']) === true)
        {
            $role->permissions()->sync($input['permissions']);

        }

        $this->repo->saveOrFail($role);

        $role = $this->repo->role->retrieveByOrgIdAndIdOrFail($orgId, $roleId);

        return $role;
    }
}
