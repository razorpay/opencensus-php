<?php

namespace RZP\Models\Admin\Role;

use RZP\Models\Admin\Org\Entity as Org;
use RZP\Models\Admin\Permission;
use RZP\Models\Base;

class Core extends Base\Core
{
    public function create($orgId, array $input)
    {
        Org::verifyIdAndStripSign($orgId);

        $role = (new Entity)->build($input);

        $org = $this->repo->org->findOrFailPublic($orgId);

        $role->org()->associate($org);

        $this->repo->saveOrFail($role);

        $permIds = $input['permissions'];

        // Perm IDs without sign
        $newPermIds = [];

        foreach ($permIds as $permId)
        {
            $newPermIds[] = Permission\Entity::verifyIdAndStripSign($permId);
        }

        $perms = $this->repo->permission->retrieveByIds($newPermIds);

        foreach ($perms as $perm)
        {
            $role->permissions()->attach($perm);
        }

        $this->repo->saveOrFail($role);

        return $role;
    }
}
