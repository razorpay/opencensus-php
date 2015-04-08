<?php

namespace Models\Terminal;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Payment;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::MERCHANT_ID               => 'required|alpha_num',
        Entity::GATEWAY                   => 'required|in:hdfc,atom',
        Entity::GATEWAY_MERCHANT_ID       => 'required',
        Entity::GATEWAY_TERMINAL_ID       => 'required',
        Entity::GATEWAY_TERMINAL_PASSWORD => 'required',
        Entity::CARD                      => 'required_if:gateway,atom|boolean');

    protected static $createValidators = array(Entity::CARD);

    protected function validateCard($input)
    {
        if (($input[Entity::GATEWAY] === Payment\Gateway::HDFC) and
            (isset($input[Entity::CARD])) and
            ($input[Entity::CARD] !== '1'))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Card field should be 1 for hdfc gateway',
                Entity::CARD);
        }
    }

    public function validateExistingTerminalsCount($existingTerminals)
    {
        $count = $existingTerminals->count();

        // Right now, at max 3 terminals are allowed
        if ($count === 3)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_GATEWAY_TERMINAL_ONLY_THREE_ALLOWED);
        }
        else if (($count === 1) or
                 ($count === 2))
        {
            foreach ($existingTerminals as $existing)
            {
                $this->matchGatewayForNewTerminal($this->entity, $existing);
            }
        }
        else if ($count > 3)
        {
            throw new Exception\LogicException('Terminal count should not exceed 2');
        }
    }

    protected function matchGatewayForNewTerminal($new, $existing)
    {
        // If 1 exists, then another should not be added for the same gateway
        if ($this->new->getGateway() === $existing->getGateway())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_TERMINAL_EXISTS_FOR_GATEWAY);
        }
    }
}
