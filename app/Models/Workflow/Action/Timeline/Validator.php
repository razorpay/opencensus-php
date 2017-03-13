<?php

namespace RZP\Models\Workflow\Action\Timeline;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Workflow\Action\State;

class Validator extends Base\Validator
{
    const VALID_TIMELINE_STATES = [
        State::APPROVED,
        State::REJECTED,
    ];

    protected static $createRules = [
        Entity::ACTION_ID => 'sometimes|string|max:14',
        Entity::ADMIN_ID  => 'sometimes|string|max:14',
        Entity::STATE     => 'required|string|max:150',
    ];

    public function validateState(string $state)
    {
        if (in_array($value, self::VALID_TIMELINE_STATES, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_WORKFLOW_TIMELINE_INVALID_STATE,
                ['state' => $value]);
        }
    }
}
