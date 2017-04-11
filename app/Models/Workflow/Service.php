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

    public function fetch(string $orgId, string $id)
    {
        $workflow = $this->repo->workflow
                               ->findByPublicIdAndOrgIdWithRelations(
                                   $id, $orgId, ['steps', 'permissions']);

        return $workflow->toArrayPublic();
    }

    public function fetchMultiple(string $orgId, array $input)
    {
        Org\Entity::verifyIdAndStripSign($orgId);

        $workflows = $this->repo->workflow->findByOrgId($orgId);

        return $workflows->toArrayPublic();
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
        $data = (new Manager)->getActionsForChecker();

        return $data;
    }

    public function getActionsByMaker()
    {
        $actions = (new Manager)->getActionsByMaker();

        return $actions->toArrayPublic();
    }

    public function permissionHasWorkflow(array $routePermissions, string $orgId)
    {
        $workflows = $this->core()->getWorkflowsForPermissions($routePermissions, $orgId);

        return ($workflows->isEmpty() === false);
    }
}
