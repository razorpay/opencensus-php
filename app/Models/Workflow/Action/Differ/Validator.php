<?php

namespace RZP\Models\Workflow\Action\Differ;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Workflow\Action;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ENTITY_NAME                 => 'required|string',
        Entity::ENTITY_ID                   => 'sometimes|nullable|string',
        Entity::MAKER_ID                    => 'required|string',
        Entity::MAKER_TYPE                  => 'required|string',
        Entity::MAKER                       => 'required|string',
        Entity::TYPE                        => 'required|string|custom',
        Entity::URL                         => 'required|string',
        Entity::ROUTE_PARAMS                => 'sometimes|array',
        Entity::METHOD                      => 'required|string',
        Entity::PAYLOAD                     => 'sometimes|array',
        Entity::CONTROLLER                  => 'required|string',
        Entity::ROUTE                       => 'required|string',
        Entity::ACTION_ID                   => 'required|string|max:14',
        Entity::STATE                       => 'required|string|max:25',
        Entity::PERMISSION                  => 'required|string|max:50',
        Entity::DIFF                        => 'sometimes|array',
        Entity::WORKFLOW_OBSERVER_DATA      => 'sometimes|array',
        Entity::AUTH_DETAILS                => 'sometimes|array',
        Action\Entity::TAGS                 => 'sometimes|array',
    ];

    protected function validateType($attribute, $type)
    {
        if (Type::exists($type) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACTION_INVALID_TYPE);
        }
    }
}
