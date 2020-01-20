<?php

namespace RZP\Models\Workflow;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Models\Workflow\Step;
use RZP\Models\Workflow\Action;
use RZP\Models\Admin\Permission;

class Service extends Base\Service
{
    public function create(array $input)
    {
        Org\Entity::verifyIdAndStripSign($input[Entity::ORG_ID]);

        Permission\Entity::verifyIdAndStripSignMultiple($input[Entity::PERMISSIONS]);

        $this->validateIfPayoutWorkflow($input);

        $merchant = $this->auth->getMerchant();

        $workflow = $this->core()->create($input, $merchant);

        return $this->convertDataToDashboardFormat(
            $workflow->toArrayPublic());
    }

    public function fetch(string $orgId, string $id)
    {
        $workflow = $this->repo->workflow
                               ->findByPublicIdAndOrgIdWithRelations(
                                   $id, $orgId, ['steps', 'permissions']);

        $data = $workflow->toArrayPublic();

        $data['isEditable'] = $this->core()->workflowHasOpenActions($workflow);

        // Dashboard requires the API in certain format
        $response = $this->convertDataToDashboardFormat($data);

        return $response;
    }

    public function fetchMultiple(string $orgId, array $input)
    {
        Org\Entity::verifyIdAndStripSign($orgId);

        $workflows = $this->repo->workflow->findByOrgIdAndPermissionName($orgId, $input);

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

    public function permissionHasWorkflow(string $routePermission, string $orgId, string $merchantId = null)
    {
        $workflows = (new Action\Core)->getWorkflowsForPermission($routePermission, $orgId, $merchantId);

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

    public function validateIfPayoutWorkflow($input)
    {
        $orgId = $input[Entity::ORG_ID];

        $permissions = $input[Entity::PERMISSIONS];

        // Ensure that merchant id is also passed if create_payout permission is attached, otherwise not required
        $createPayoutPerm = $this->repo
                                 ->permission
                                 ->retrieveIdsByNamesAndOrg(Permission\Name::CREATE_PAYOUT, $orgId)
                                 ->first();

        $hasCreatePayoutPermission =  (in_array($createPayoutPerm, $permissions, true) === true);

        if ($hasCreatePayoutPermission === true)
        {
            if (empty($merchant) === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_ID_NOT_PASSED);
            }
        }
    }
}
