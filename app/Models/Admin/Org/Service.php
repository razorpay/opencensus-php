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
        $adminInput = $this->getAdminInput($input);
        $orgInput = array_diff($input, $adminInput);
        $orgInput['email'] = $input['email'];

        $org = $this->core()->create($orgInput);

        $role = $this->createDefaultRole($org);
        $adminInput['roles'] = [$role['id']];

        $admin = $this->createDefaultAdmin($org, $adminInput);

        return $org->toArrayPublic();
    }

    protected function createDefaultRole($org)
    {
        $permissions = Config::get('heimdall.permissions');
        $permissions = (new Permission\Service)->getMultiplePermissionIdsByNames($permissions);
        foreach ($permissions['items'] as $value)
        {
            $input['permissions'][] = $value['id'];
        }
        $input['name'] = 'default';
        $input['description'] = 'desc';

        return (new Role\Service)->createRole('org_'.$org->id, $input);
    }

    protected function createDefaultAdmin($org, $input)
    {
        return (new Admin\Service)->createAdmin('org_'.$org['id'], $input);
    }

    protected function getAdminInput($input)
    {
        unset($input['email_domains']);
        unset($input['logo_url']);
        unset($input['auth_type']);
        unset($input['display_name']);
        unset($input['business_name']);

        return $input;
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
        $org = $this->core()->edit($id, $input);

        return $org->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $orgs = $this->repo->org->fetch($input);

        return $orgs->toArrayPublic();
    }
}
