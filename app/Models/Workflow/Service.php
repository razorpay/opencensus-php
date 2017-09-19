<?php

namespace RZP\Models\Workflow;

use RZP\Models\Base;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Permission;
use RZP\Models\Workflow\Action;
use RZP\Models\Workflow\Step;

class Service extends Base\Service
{
    public function create(array $input)
    {
        Org\Entity::verifyIdAndStripSign($input[Entity::ORG_ID]);

        Permission\Entity::verifyIdAndStripSignMultiple($input[Entity::PERMISSIONS]);

        $workflow = $this->core()->create($input);

        return $this->convertDataToDashboardFormat(
            $workflow->toArrayPublic());
    }

    public function fetch(string $orgId, string $id)
    {
        $workflow = $this->repo->workflow
                               ->findByPublicIdAndOrgIdWithRelations(
                                   $id, $orgId, ['steps', 'permissions']);

        $data = $workflow->toArrayPublic();

        $data['isEditable'] = $this->core()->isWorkflowEditable($workflow);

        // Dashboard requires the API in certain format
        $response = $this->convertDataToDashboardFormat($data);

        return $response;
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

        if (empty($input[Entity::PERMISSIONS]) === false)
        {
            Permission\Entity::verifyIdAndStripSignMultiple(
                $input[Entity::PERMISSIONS]);
        }

        if (empty($input[Entity::ORG_ID]) === false)
        {
            Org\Entity::verifyIdAndStripSign($input[Entity::ORG_ID]);
        }

        $workflow = $this->repo->workflow->findOrFailPublic($id);

        $workflow = $this->core()->update($workflow, $input);

        $data = $this->convertDataToDashboardFormat($workflow->toArrayPublic());

        return $data;
    }

    public function delete(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $workflow = $this->repo->workflow->findOrFailPublic($id);

        $workflow = $this->core()->delete($workflow);

        return $workflow->toArrayPublic();
    }

    public function permissionHasWorkflow(string $routePermission, string $orgId)
    {
        $permissionIds = $this->repo
                             ->permission
                             ->retrieveIdsByNamesAndOrg($routePermission, $orgId)
                             ->toArray();

        if (empty($permissionIds) === true)
        {
            return false;
        }

        $permissionId = $permissionIds[0];

        $workflows = (new Action\Core)->getWorkflowsForPermission(
            $permissionId, $orgId);

        return ($workflows->isEmpty() === false);
    }

    public function convertDataToDashboardFormat(array $data)
    {
        $steps = $data[Entity::STEPS] ?? [];

        // Get all the levels in the steps.

        $levels = array_map(function($step){
            return $step[Step\Entity::LEVEL];
        }, $steps);

        $levels = array_unique($levels, SORT_NUMERIC);

        $levelData = [];

        foreach ($levels as $level)
        {
            $levelDetails = [];

            $levelSteps = [];

            foreach ($steps as $step)
            {
                if ($step[Step\Entity::LEVEL] === $level)
                {
                    $levelDetails[Step\Entity::OP_TYPE] = $step[Step\Entity::OP_TYPE];
                    $levelDetails[Step\Entity::LEVEL] = $level;

                    unset($step[Step\Entity::OP_TYPE]);
                    unset($step[Step\Entity::LEVEL]);

                    $levelSteps[] = $step;
                }
            }

            $levelDetails['steps'] = $levelSteps;

            $levelData[] = $levelDetails;
        }

        // returns null if the key is not present
        unset($data[Entity::STEPS]);

        $data[Entity::LEVELS] = $levelData;

        return $data;
    }
}
