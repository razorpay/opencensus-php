<?php

namespace RZP\Models\Admin\Org;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $org = (new Entity)->build($input);

        $this->repo->saveOrFail($org);

        return $org;
    }

    public function delete($id)
    {
        $org = $this->repo->org->findOrFail($id);

        $groups = $this->repo->group->fetchGroupsForOrg($id);

        $admins = $this->repo->admin->fetchAdminsForOrg($id);

        $roles = $this->repo->role->fetchRolesForOrg($id);

        foreach ($groups as $entity)
        {
            $this->repo->deleteOrFail($entity);
        }

        foreach ($admins as $entity)
        {
            $this->repo->deleteOrFail($entity);
        }

        foreach ($roles as $entity)
        {
            $this->repo->deleteOrFail($entity);
        }

        $this->repo->deleteOrFail($org);

        return ['success' => true];
    }
}
