<?php

namespace RZP\Models\Workflow\Step;

use RZP\Error;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Workflow\Base;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $step = new Entity;

        $step->generateId();

        $step->build($input);

        $allSteps = $step->workflow->steps;

        $allSteps = $allSteps->map(function ($step) {
                                return $step->toArrayPublic();
                             })
                             ->prepend($input)
                             ->toArray();

        $step->getValidator()->validateStepLevel($allSteps);

        $step->getValidator()->validateStepUniqueness($allSteps);

        $allPermissions = $step->workflow
                               ->permissions
                               ->map(function ($permission) {
                                 return $permission->getId();
                               })
                               ->toArray();

        $minLevel = $this->getMinLevelFromSteps($allSteps);

        $this->validateExistingWorkflows($allPermissions, $minLevel, $step->workflow);

        $this->repo->saveOrFail($step);

        return $step;
    }

    protected function validateExistingWorkflows($permissions, $minLevel, $thisWorkflow)
    {
        $options = ['workflow_id' => [$thisWorkflow->getId()]];

        $workflows = $this->repo
                          ->workflow
                          ->fetchWorkflowsWithStepsByPermissions($permissions, $options);

        $this->validateWorkflowsForLevel($workflows, $minLevel);
    }

    protected function validateWorkflowsForLevel($workflows, $minLevel)
    {
        foreach ($workflows as $workflow)
        {
            $minLevelFromSteps = $this->getMinLevelForWorkflow($workflow);

            if ($minLevelFromSteps === $minLevel)
            {
                throw new Exception\BadRequestException(
                    Error\ErrorCode::BAD_REQUEST_WORKFLOW_PERMISSION_EXISTS);
            }
        }
    }

}
