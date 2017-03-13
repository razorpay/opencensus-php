<?php

namespace RZP\Models\Workflow\Action\Differ;

use RZP\Base;
use RZP\Error;
use RZP\Error\ErrorCode;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ENTITY_NAME => 'required|string',
        Entity::ENTITY_ID   => 'required|string',
        Entity::ACTOR       => 'required|string',
        Entity::TYPE        => 'required|string|custom',
        Entity::URI         => 'required|string',
        Entity::METHOD      => 'required|string|custom',
        Entity::PAYLOAD     => 'required|array',
    ];

    protected function validateType($attribute, $type)
    {
        if (Type::exists($type) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACTION_INVALID_TYPE);
        }
    }

    protected function validateMethod($attribute, $method)
    {
        if (Method::exists($method) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACTION_INVALID_METHOD);
        }
    }
}
