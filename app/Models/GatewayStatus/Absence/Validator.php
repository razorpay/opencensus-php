<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\Base;
use RZP\Models\Payment\Gateway;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = array (
        Entity::GATEWAY         => 'required|string|max:255',
        Entity::FROM            => 'required|integer',
        Entity::TO              => 'sometimes|integer',
        Entity::REASON          => 'sometimes|string|max:500',
        Entity::BANK            => 'sometimes|string|max:255',
        Entity::SCHEDULED       => 'sometimes|bool'
    );

    protected static $editRules = array(
        Entity::FROM            => 'required|integer',
        Entity::TO              => 'sometimes|integer',
    );

    protected static $createValidators = array(
        'gateway',
    );

    public function validateGateway($input)
    {
        $gateway = $input[Entity::GATEWAY];

        $valid = Gateway::isValidGateway($gateway);

        if ($valid === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Gateway [' . $gateway . '] does not exist');
        }
    }
}
