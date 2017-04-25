<?php

namespace RZP\Models\Admin\Permission;

use RZP\Models\Base;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Action;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $permission = (new Entity)->build($input);

        $permission->generateId();

        $permission->setAuditAction(Action::CREATE_PERMISSION);

        $this->repo->transactionOnLiveAndTest(function() use($permission, $input)
        {
            $this->repo->saveOrFail($permission);

            if (empty($input[Entity::ORGS]) === false)
            {
                $orgs = $this->repo->org->findMany($input[Entity::ORGS]);

                foreach ($orgs as $org)
                {
                    (new Org\Core)->addPermissionToOrg($permission, $org);
                }
            }
        });

        $permission = $this->repo->permission
                                 ->findOrFailPublicWithRelations(
                                     $permission->getId(), ['orgs']);

        return $permission;
    }

    public function edit(Entity $permission, array $input)
    {
        $permission->edit($input);

        $permission->setAuditAction(Action::EDIT_PERMISSION);

        $this->repo->transactionOnLiveAndTest(function() use($permission, $input)
        {
            $this->repo->saveOrFail($permission);

            if (isset($input[Entity::ORGS]) === true)
            {
                $assignedOrgs = $permission->orgs()->getRelatedIds()->toArray();

                $newOrgs = array_diff($input[Entity::ORGS], $assignedOrgs);

                $unassignedOrgs = array_diff($assignedOrgs, $input[Entity::ORGS]);

                if (empty($newOrgs) === false)
                {
                    $orgs = $this->repo->org->findMany($newOrgs);

                    foreach ($orgs as $org)
                    {
                        (new Org\Core)->addPermissionToOrg($permission, $org);
                    }
                }

                if (empty($unassignedOrgs) === false)
                {
                    $orgs = $this->repo->org->findMany($unassignedOrgs);

                    foreach ($orgs as $org)
                    {
                        (new Org\Core)->deletePermissionFromOrg($permission, $org);
                    }
                }
            }
        });

        $permission = $this->repo->permission
                                 ->findOrFailPublicWithRelations(
                                     $permission->getId(), ['orgs']);

        return $permission;
    }

    public function delete(Entity $permission)
    {
        $permission->setAuditAction(Action::DELETE_PERMISSION);

        $this->repo->deleteOrFail($permission);

        return $permission;
    }
}
