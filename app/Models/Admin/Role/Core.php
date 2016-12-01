<?php

namespace RZP\Models\Admin\Role;

use RZP\Models\Admin\Org;
use RZP\Models\Admin\Permission;
use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(Org\Entity $org, array $input)
    {
        $role = (new Entity)->generateId();

        $role->build($input);

        $this->repo->role->validateOrgHasNoSuchRole($role, $org);

        $role->org()->associate($org);

        $this->repo->saveOrFail($role);

        if (isset($input['permissions']) === true)
        {
            Permission\Entity::verifyIdAndStripSignMultiple($input['permissions']);

            $role->permissions()->sync($input['permissions']);
        }
        else
        {
            // Deletion of all
            $role->permissions()->sync([]);
        }

        return $role;
    }

    public function edit(Entity $role, array $input)
    {
        $role->edit($input);

        if (isset($input['permissions']) === true)
        {
            Permission\Entity::verifyIdAndStripSignMultiple($input['permissions']);

            $role->permissions()->sync($input['permissions']);
        }
        else
        {
            // Deletion of all
            $role->permissions()->sync([]);
        }

        return $role;
    }
}
