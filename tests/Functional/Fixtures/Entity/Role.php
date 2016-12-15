<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Tests\Functional\Fixtures\Entity\Permission;

class Role extends Base
{
    public function createAdminRole(array $input)
    {
        $perms = (new Permission)->getAllPermissions($input);

        $role = $this->fixtures->create('role',
        [
            'org_id'      => $input['org_id'],
            'name'        => 'SuperAdmin',
            'description' => 'test super admin role',
        ]);

        $role->permissions()->attach($perms);

        return $role;
    }
}
