<?php

namespace Models\Terminal;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;

class Validator extends Base\Validator
{
    protected static $addPlanRuleRules = array(
        Entity::GATEWAY                   => 'required|in:hdfc',
        Entity::GATEWAY_TERMINAL_ID       => 'required',
        Entity::GATEWAY_TERMINAL_PASSWORD => 'required');

    public function addTerminalValidate($input, $terminals)
    {
        foreach ($terminals as $terminal)
        {
            if ($terminal['gateway_terminal_id'] === $input['gateway_terminal_id'])
                throw new Exception\BadRequestException(
                    null,
                    ErrorCode::BAD_REQUEST_TERMINAL_ID_EXISTS);
        }
    }


    protected function processValidationFailure($messages, $operation, $input)
    {
        throw new Exception\BadRequestException($messages);
    }
}
