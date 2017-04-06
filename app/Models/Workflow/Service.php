<?php

namespace RZP\Models\Workflow;

use RZP\Models\Base;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Permission;

class Service extends Base\Service
{
    public function create(array $input)
    {
        Org\Entity::verifyIdAndStripSign($input[Entity::ORG_ID]);

        Permission\Entity::verifyIdAndStripSignMultiple($input[Entity::PERMISSIONS]);

        $workflow = $this->core()->create($input);

        return $workflow->toArrayPublic();
    }

    public function fetch(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $workflow = $this->repo->workflow->findOrFailPublic($id);

        return $workflow->toArrayPublic();
    }

    public function update(string $id, array $input)
    {
        Entity::verifyIdAndStripSign($id);

        Permission\Entity::verifyIdAndStripSignMultiple($input[Entity::PERMISSIONS]);

        $workflow = $this->repo->workflow->findOrFailPublic($id);

        $workflow = $this->core()->update($workflow, $input);

        return $workflow->toArrayPublic();
    }

    public function delete(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $workflow = $this->repo->workflow->findOrFailPublic($id);

        $workflow = $this->core()->delete($workflow);

        return $workflow->toArrayPublic();
    }

    public function getActionsForChecker()
    {
        $data = (new Manager)->getActionsForAdmin();

        return $data;
    }

    public function getActionsByMaker()
    {
        $actions = (new Manager)->getActionsByMaker();

        return $actions->toArrayPublic();
    }

    public function permissionHasWorkflow($routePermissions, $orgId)
    {
        $permissionIds = $this->repo
                              ->permission
                              ->retrieveIdsByNames($routePermissions, $orgId)
                              ->map(function ($permission){
                                    return $permission->getId();
                                })
                              ->toArray();

        $workflows = $this->repo->workflow->fetchWorkflowsByPermissions($permissionIds);

        return ($workflows->isEmpty() === false);
    }
}
