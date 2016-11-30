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
        $orgId = $org->id;
        $orgId = Entity::getSignedId($orgId);

        $role = $this->createDefaultRole($orgId);
        $adminInput['roles'] = [$role['id']];

        $admin = $this->createDefaultAdmin($orgId, $adminInput);

        return $org->toArrayPublic();
    }

    protected function createDefaultRole($orgId)
    {
        $permissions = Config::get('heimdall.permissions');
        $permissions = (new Permission\Service)->getMultiplePermissionIdsByNames($permissions);
        foreach ($permissions['items'] as $value)
        {
            $input['permissions'][] = $value['id'];
        }
        $input['name'] = 'superadmin';
        $input['description'] = 'This role has all permissions possible';

        return (new Role\Service)->createRole($orgId, $input);
    }

    protected function createDefaultAdmin($orgId, $input)
    {
        return (new Admin\Service)->createAdmin($orgId, $input);
    }

    protected function getAdminInput($input)
    {
        unset($input['email_domains']);
        unset($input['logo_url']);
        unset($input['auth_type']);
        unset($input['display_name']);
        unset($input['business_name']);
        unset($input['hostname']);

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
