<?php

namespace RZP\Models\Workflow\Step;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Workflow\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ROLE_ID        => 'required|string',
        Entity::LEVEL          => 'required|integer',
        Entity::REVIEWER_COUNT => 'required|integer|min:1',
        Entity::OP_TYPE        => 'required|string|in:and,or',
        Entity::WORKFLOW_ID    => 'required|string|size:14',
    ];

    protected static $editRules = [
        Entity::ROLE_ID        => 'sometimes|string',
        Entity::LEVEL          => 'sometimes|integer',
        Entity::REVIEWER_COUNT => 'sometimes|integer|min:1',
    ];

    // Validate all the levels passed in steps array should be incremental value by 1
    public function validateStepLevel(array $steps)
    {
        $levels = array_column($steps, Entity::LEVEL);

        $levels = array_unique($levels);

        if (((max($levels) - min($levels)) === (count($levels) - 1)) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_STEP_LEVEL_SEQUENCE);
        }
    }

    // Validate combination of role and level should be unique in the step array
    public function validateStepUniqueness(array $steps)
    {
        $levelRole = [];

        foreach ($steps as $step)
        {
            $levelRole[] = $step[Entity::LEVEL] . '_' . $step[Entity::ROLE_ID];
        }

        $uniqueLevelRole = array_unique($levelRole);

        if (count($uniqueLevelRole) !== count($levelRole))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_STEP_ROLE_LEVEL_UNIQUE);
        }
    }
}
