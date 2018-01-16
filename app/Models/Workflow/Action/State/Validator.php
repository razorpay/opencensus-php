<?php

namespace RZP\Models\Workflow\Action\State;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity as E;

class Validator extends Base\Validator
{
    const VALID_ACTION_STATES = [
        Entity::APPROVED,
        Entity::REJECTED,
        Entity::EXECUTED,
        Entity::OPEN,
        Entity::CLOSED,
    ];

    protected static $createRules = [
        Entity::ACTION_ID => 'required|string|max:14',
        Entity::ADMIN_ID  => 'sometimes|string|max:14',
        Entity::NAME      => 'required|string|max:150|custom',
    ];

    public function validateName(string $attr, string $state)
    {
        if (in_array($state, self::VALID_ACTION_STATES, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_STATE, null,
                [E::STATE => $state]);
        }
    }
}
