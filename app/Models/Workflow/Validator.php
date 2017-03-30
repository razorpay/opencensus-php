<?php

namespace RZP\Models\Workflow;

use RZP\Base;
use RZP\Error;
use RZP\Exception;
use RZP\Models\Workflow\Step;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME        => 'required|string|max:150',
        Entity::ORG_ID      => 'required|string|max:14',
        Entity::PERMISSIONS => 'required|array',
        Entity::STEPS       => 'required|array|custom',
    ];

    protected static $editRules = [
        Entity::NAME        => 'sometimes|string|max:150',
        Entity::PERMISSIONS => 'sometimes|array',
    ];

    public function validateSteps(string $attribute, array $value)
    {
        $this->validateStepLevel($value);

        $this->validateStepUniqueness($value);
    }

    // Validate all the levels passed in steps array should be incremental value by 1
    protected function validateStepLevel(array $steps)
    {
        $levels = array_column($steps, Step\Entity::LEVEL);

        $levels = array_unique($levels);

        if (((max($levels) - min($levels)) === (count($levels) - 1)) === false)
        {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_WORKFLOW_STEP_LEVEL_SEQUENCE);
        }
    }

    // Validate combination of role and level should be unique in the step array
    protected function validateStepUniqueness(array $steps)
    {
        $levelRole = [];

        foreach ($steps as $step)
        {
            $levelRole[] = $step[Step\Entity::LEVEL] . '_' . $step[Step\Entity::ROLE_ID];
        }

        $uniqueLevelRole = array_unique($levelRole);

        if (count($uniqueLevelRole) !== count($levelRole))
        {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_WORKFLOW_STEP_ROLE_LEVEL_UNIQUE);
        }
    }
}
