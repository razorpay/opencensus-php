<?php

namespace RZP\Models\Workflow\Base;

use RZP\Error;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Workflow;
use RZP\Error\ErrorCode;
use RZP\Models\Workflow\Step;

class Core extends Base\Core
{
    protected function validateExistingWorkflows($permissions, $minLevel, $options = [])
    {
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
                    ErrorCode::BAD_REQUEST_WORKFLOW_PERMISSION_EXISTS);
            }
        }
    }

    protected function getMinLevelForWorkflow(Workflow\Entity $workflow)
    {
        $minLevel = PHP_INT_MAX;

        $steps = $workflow->steps;

        foreach ($steps as $step)
        {
            if ($step->getLevel() < $minLevel)
            {
                $minLevel = $step->getLevel();
            }
        }

        return $minLevel;
    }

    protected function getMinLevelFromSteps(array $steps)
    {
        $minLevel = PHP_INT_MAX;

        foreach ($steps as $step)
        {
            if ($step[Step\Entity::LEVEL] < $minLevel)
            {
                $minLevel = $step[Step\Entity::LEVEL];
            }
        }

        return $minLevel;
    }
}
