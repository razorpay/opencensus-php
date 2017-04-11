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
        $admin = $this->app['basicauth']->getAdmin();

        $data = (new Manager)->getActionsForChecker($admin);

        return $data;
    }

    public function getActionsByMakerAndType(array $input)
    {
        $admin = $this->app['basicauth']->getAdmin();

        $orgId = $admin->getOrgId();

        $type = $input['type'] ?? 'maker';

        switch ($type)
        {
            case 'all':
                $actions = (new Manager)->getAllActionsByOrg($orgId);
                break;

            case 'closed':
                $actions = (new Manager)->getClosedActionsByMaker($admin);
                break;

            case 'open':
                $actions = (new Manager)->getOpenActionsByOrg($orgId);
                break;

            case 'maker':
            default:
                $actions = (new Manager)->getActionsByMaker($admin);
                break;
        }

        return $actions->toArrayPublic();
    }

    public function permissionHasWorkflow($routePermissions, $orgId)
    {
        $permissionIds = $this->repo
                              ->permission
                              ->retrieveIdsByNamesAndOrg($routePermissions, $orgId)
                              ->map(function ($permission){
                                    return $permission->getId();
                                })
                              ->toArray();

        $workflows = $this->repo->workflow->fetchWorkflowsByPermissions($permissionIds);

        return ($workflows->isEmpty() === false);
    }
}
