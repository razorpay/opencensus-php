<?php

namespace Models\Terminal;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Payment\Gateway;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::MERCHANT_ID               => 'required|alpha_num',
        Entity::GATEWAY                   => 'required|in:hdfc,atom',
        Entity::GATEWAY_MERCHANT_ID       => 'required',
        Entity::GATEWAY_TERMINAL_ID       => 'required_if:gateway,hdfc',
        Entity::GATEWAY_TERMINAL_PASSWORD => 'required');

    protected static $createValidators = array('terminal_id');

    protected function validateTerminalId($input)
    {
        if (($input[Entity::GATEWAY] === Gateway::HDFC) and
            ($input[Entity::GATEWAY_TERMINAL_ID] === ''))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_GATEWAY_TERMINAL_ID_INPUT);
        }
        else if (($input[Entity::GATEWAY] === Gateway::ATOM) and
                 ($input[Entity::GATEWAY_TERMINAL_ID] !== ''))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_GATEWAY_TERMINAL_ID_INPUT);
        }
    }
}
