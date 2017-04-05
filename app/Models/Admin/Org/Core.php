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

        $this->addOrgRelatedEntities($org, $input);

        return $org;
    }

    public function fetch(string $orgId)
    {
        Entity::verifyIdAndStripSign($orgId);

        return $this->repo->org->findOrFailPublicWithRelations(
            $orgId, ['hostnames', 'permissions']);
    }

    public function edit(string $orgId, array $input)
    {
        $orgId = Entity::verifyIdAndStripSign($orgId);

        $org = $this->repo->org->findOrFailPublic($orgId);

        $org->setAuditAction(Action::EDIT_ORG);

        $org->edit($input);

        $this->repo->transactionOnLiveAndTest(function() use($org, $input)
        {

            $this->repo->saveOrFail($org);

            if (isset($input[Entity::PERMISSIONS]) === true)
            {
                $oldPerms = $org->permissions()->getRelatedIds()->toArray();

                // These perms are deleted from the organization
                $diffPerms = array_diff($oldPerms, $input[Entity::PERMISSIONS]);

                $this->deleteUnassignedPermissionsFromRoles($org, $diffPerms);

                $this->addOrgRelatedEntities($org, $input);
            }
        });

        $org = $this->fetch($org->getPublicId());

        return $org;
    }

    protected function deleteUnassignedPermissionsFromRoles(Entity $org, array $diffPerms)
    {
        $orgId = $org->getPublicId();

        $roles = $this->repo->role->fetchByOrgId($orgId);

        // Laravel detach removes all the entities in pivot table if you send
        // empty array
        if (empty($diffPerms) === true)
        {
            return;
        }

        foreach ($roles as $role)
        {
            $role->permissions()->detach($diffPerms);
        }
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

    protected function addOrgRelatedEntities(Entity $org, array $input)
    {
        if (isset($input[Entity::PERMISSIONS]) === true)
        {
            $this->repo->sync(
                $org, Entity::PERMISSIONS, $input[Entity::PERMISSIONS]);
        }
    }
}
