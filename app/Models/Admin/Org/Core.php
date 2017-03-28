<?php

namespace RZP\Models\Admin\Org;

use RZP\Models\Admin\Action;
use RZP\Models\Base;
use RZP\Models\Admin\Permission;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $org = (new Entity)->generateId();

        $org->setAuditAction(Action::CREATE_ORG);

        $org->build($input);

        $this->repo->saveOrFail($org);

        $this->syncPermissions($org, $input);

        return $org;
    }

    public function fetch(string $orgId)
    {
        $orgId = Entity::verifyIdAndStripSign($orgId);

        return $this->repo->org->findOrFailWithHostname($orgId);
    }

    public function edit(string $orgId, array $input)
    {
        $orgId = Entity::verifyIdAndStripSign($orgId);

        $org = $this->repo->org->findOrFailPublic($orgId);

        $org->setAuditAction(Action::EDIT_ORG);

        $this->syncPermissions($org, $input);

        $org->edit($input);

        $this->repo->saveOrFail($org);

        return $org;
    }

    public function delete($id)
    {
        $id = Entity::verifyIdAndStripSign($id);

        $org = $this->repo->org->findOrFail($id);

        $org->setAuditAction(Action::DELETE_ORG);

        (new Hostname\Core)->deleteHostnamesOfOrg($id);

        $this->repo->org->deleteOrFail($org);

        return $org->toArrayDeleted();
    }

    protected function syncPermissions($org, array $input)
    {
        if (isset($input['permissions']) === true)
        {
            if (($input['permissions'] instanceof Base\PublicCollection) === false)
            {
                Permission\Entity::verifyIdAndStripSignMultiple($input['permissions']);
            }

            $this->repo->sync($org, 'permissions', $input['permissions']);
        }

        return $org;
    }
}
