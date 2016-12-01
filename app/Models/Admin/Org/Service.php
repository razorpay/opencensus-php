<?php

namespace RZP\Models\Admin\Org;

use RZP\Models\Base;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Permission;
use RZP\Models\Admin\Admin;
use Config;

class Service extends Base\Service
{
    public function create(array $input)
    {
        $org = $this->core()->create($input);

        $role = $this->createDefaultRole($org);

        $adminInput = $input['admin'];

        $adminInput['roles'] = (array) $role->getPublicId();

        $adminInput['email'] = $input['email'];

        (new Admin\Core)->create($org, $adminInput);

        return $org->toArrayPublic();
    }

    protected function createDefaultRole(Entity $org)
    {
        $permissions = Config::get('heimdall.permissions') ?: [];
        $permissions = (new Permission\Service)->getMultiplePermissionIdsByNames($permissions);

        $input = [
            'name' => 'superadmin',
            'description' => 'This role has all permissions possible',
            'permissions' => $permissions,
        ];

        return (new Role\Core)->create($org, $input);
    }


    public function fetch(string $id)
    {
        $org = $this->core()->fetch($id);

        return $org->toArrayPublic();
    }

    public function fetchByHostname(string $hostname)
    {
        $org = $this->repo->org->findOrFailByHostname($hostname);

        return $org->toArrayPublic();
    }

    public function delete(string $id)
    {
        return $this->core()->delete($id);
    }

    public function edit(string $id, array $input)
    {
        if (is_array($input['email_domains']))
        {
            $input['email_domains'] = implode(',', $input['email_domains']);
        }
        $org = $this->core()->edit($id, $input);

        return $org->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $orgs = $this->repo->org->fetch($input);

        return $orgs->toArrayPublic();
    }
}
