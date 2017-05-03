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

            if (empty($input[Entity::WORKFLOW_ORGS]) === false)
            {
                $this->enableWorkflowForOrgs(
                    $permission, $input[Entity::WORKFLOW_ORGS]);
            }
        });

        $permission = $this->repo->permission
                                 ->findOrFailPublicWithRelations(
                                     $permission->getId(), ['orgs']);

        $workflowOrgs = $this->getOrgsWithWorkflow($permission);

        $permission->setWorkflowOrgs($workflowOrgs);

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
                $this->toggleAssignableOrgs($permission, $input);
            }

            if (isset($input[Entity::WORKFLOW_ORGS]) === true)
            {
                $this->toggleWorkflowsOnPermission($permission, $input);
            }
        });

        $permission = $this->repo->permission
                                 ->findOrFailPublicWithRelations(
                                     $permission->getId(), ['orgs']);

        $workflowOrgs = $this->getOrgsWithWorkflow($permission);

        $permission->setWorkflowOrgs($workflowOrgs);

        return $permission;
    }

    public function delete(Entity $permission)
    {
        $permission->setAuditAction(Action::DELETE_PERMISSION);

        $this->repo->deleteOrFail($permission);

        return $permission;
    }

    public function getOrgsWithWorkflow(Entity $permission)
    {
        $permId = $permission->getId();

        $orgs = $this->repo->org
                           ->getOrgsWithWorkflowEnabled($permId);

        return $orgs;
    }

    public function enableWorkflowForOrgs(Entity $permission, array $orgIds)
    {
        $permId = $permission->getId();

        $this->repo->permission
                   ->toggleWorkflowOnPermissionForOrgs(
                       $permId, $orgIds, true);

        return ['success' => true];
    }

    public function disableWorkflowForOrgs(Entity $permission, array $orgIds)
    {
        $permId = $permission->getId();

        $this->repo->permission
                   ->toggleWorkflowOnPermissionForOrgs(
                       $permId, $orgIds, false);

        return ['success' => true];
    }

    protected function toggleAssignableOrgs(Entity $permission, array $input)
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

    public function toggleWorkflowsOnPermission(
        Entity $permission,
        array $input)
    {
        // orgs for which workflow is enabled
        $enabledOrgs = $this->getOrgsWithWorkflow($permission);

        $orgIds = [];

        foreach ($enabledOrgs as $org)
        {
            $orgIds[] = $org[Org\Entity::ID];
        }

        $newOrgs = array_diff($input[Entity::WORKFLOW_ORGS], $orgIds);

        $disabledOrgs = array_diff(
            $orgIds, $input[Entity::WORKFLOW_ORGS]);

        if (empty($newOrgs) === false)
        {
            $this->enableWorkflowForOrgs($permission, $newOrgs);
        }

        if (empty($disabledOrgs) === false)
        {
            $this->disableWorkflowForOrgs($permission, $disabledOrgs);
        }
    }
}
