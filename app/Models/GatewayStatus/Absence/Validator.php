<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\Base;
use RZP\Models\Payment\Gateway;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::GATEWAY         => 'required|string|max:255|custom',
        Entity::FROM            => 'required|integer',
        Entity::TO              => 'sometimes|integer',
        Entity::REASON          => 'sometimes|string|max:500',
        Entity::BANK            => 'sometimes|string|max:255',
        Entity::SCHEDULED       => 'sometimes|bool'
    ];

    protected static $editRules = [
        Entity::FROM            => 'required|integer',
        Entity::TO              => 'sometimes|integer',
    ];

    protected static $createValidators = [
        'to'
    ];

    protected static $editValidators = [
        'to'
    ];

    public function validateGateway($attribute, $gateway)
    {
        $valid = Gateway::isValidGateway($gateway);

        if ($valid === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Gateway [' . $gateway . '] does not exist');
        }
    }

    public function validateTo($input)
    {
        if (empty($input[Entity::TO]) === true)
        {
            return;
        }

        $to = $input[Entity::TO];

        $from = $input[Entity::FROM];

        if ($to < $from)
        {
            throw new Exception\BadRequestValidationFailureException(
                'From : ' . $from . ' less than To :' . $to
            );
        }

        if ($from > Entity::END_OF_TIME)
        {
            throw new Exception\BadRequestValidationFailureException(
                'From: '. $from. ' is greater than End of Time:' .Entity::END_OF_TIME
            );
        }

        if ($to > Entity::END_OF_TIME)
        {
            throw new Exception\BadRequestValidationFailureException(
                'To: '. $to. ' is greater than End of Time:' .Entity::END_OF_TIME
            );
        }
    }
}
