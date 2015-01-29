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
        Entity::GATEWAY_TERMINAL_ID       => 'required',
        Entity::GATEWAY_TERMINAL_PASSWORD => 'required');

    public function validateExistingTerminalsCount($existingTerminals)
    {
        $count = $existingTerminals->count();

        // Right now, at max two terminals are allowed
        if ($count === 2)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_GATEWAY_TERMINAL_ONLY_TWO_ALLOWED);
        }
        else if ($count === 1)
        {
            // If 1 exists, then another should not be added for the same gateway
            if ($this->entity->getGateway() === $existingTerminals->first()->getGateway())
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_TERMINAL_EXISTS_FOR_GATEWAY);
            }
        }
        else if ($count > 2)
        {
            throw new Exception\LogicException('Terminal count should not exceed 2');
        }
    }
}
