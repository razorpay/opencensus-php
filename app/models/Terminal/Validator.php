<?php

namespace Models\Terminal;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Payment;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::MERCHANT_ID                 => 'required|alpha_num|size:14',
        Entity::GATEWAY                     => 'required',
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes',
        Entity::GATEWAY_TERMINAL_ID         => 'sometimes',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'sometimes',
        Entity::GATEWAY_ACCESS_CODE         => 'sometimes',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes',
        Entity::CARD                        => 'sometimes|boolean',
        Entity::NETBANKING                  => 'sometimes|boolean',
        Entity::SHARED                      => 'sometimes|boolean',
    );

    protected static $createValidators = array(
        Entity::GATEWAY);

    protected function validateGateway($input)
    {
        if (Payment\Gateway::isValidGateway($input['gateway']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid gateway: ' . $input['gateway'],
                Entity::GATEWAY);
        }
    }

    public function validateExistingTerminalsCount($existingTerminals)
    {
        $count = $existingTerminals->count();

        // Check count does not exceed max terminals count
        if ($count > Entity::MAX_TERMINALS_COUNT)
        {
            throw new Exception\LogicException(
                'Terminal count should not exceed 4');
        }
        else if ($count === Entity::MAX_TERMINALS_COUNT)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_GATEWAY_TERMINAL_MAX_LIMIT_REACHED);
        }
        else if ($count >= 1)
        {
            foreach ($existingTerminals as $existing)
            {
                $this->matchGatewayForNewTerminal($this->entity, $existing);
            }
        }
    }

    protected function matchGatewayForNewTerminal($new, $existing)
    {
        // If 1 exists, then another should not be added for the same gateway
        if ($new->getGateway() === $existing->getGateway())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_TERMINAL_EXISTS_FOR_GATEWAY);
        }
    }
}
