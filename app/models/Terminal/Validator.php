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
        Entity::GATEWAY                     => 'required|in:hdfc,atom,axis_migs,axis_genius',
        Entity::GATEWAY_MERCHANT_ID         => 'sometimes',
        Entity::GATEWAY_TERMINAL_ID         => 'sometimes',
        Entity::GATEWAY_TERMINAL_PASSWORD   => 'sometimes',
        Entity::GATEWAY_ACCESS_CODE         => 'sometimes',
        Entity::GATEWAY_SECURE_SECRET       => 'sometimes',
        Entity::CARD                        => 'required_if:gateway,atom|boolean',
    );

    protected static $createValidators = array(Entity::CARD);

    protected function validateCard($input)
    {
        if (($input[Entity::GATEWAY] !== Payment\Gateway::ATOM) and
            (isset($input[Entity::CARD])) and
            ($input[Entity::CARD] !== '1'))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Card field should be 1 for all gateways except atom',
                Entity::CARD);
        }
    }

    public function validateExistingTerminalsCount($existingTerminals)
    {
        $count = $existingTerminals->count();

        // Right now, at max 4 terminals are allowed
        if ($count > 4)
        {
            throw new Exception\LogicException('Terminal count should not exceed 4');
        }
        else if ($count === 4)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_GATEWAY_TERMINAL_ONLY_FOUR_ALLOWED);
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
