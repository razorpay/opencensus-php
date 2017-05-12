<?php

namespace RZP\Models\Promotion;

use Carbon\Carbon;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME                      => 'required|string|max:50',
        Entity::AMOUNT                    => 'required|integer|min:1',
        Entity::CREDIT_TYPE               => 'required|in:fee,amount',
        Entity::ITERATIONS                => 'sometimes|integer|min:1',
    ];

    protected static $editRules = [
        Entity::NAME                      => 'sometimes|string|max:50',
        Entity::AMOUNT                    => 'required|integer|min:1',
        Entity::CREDIT_TYPE               => 'required|in:fee,amount',
        Entity::ITERATIONS                => 'sometimes|integer|min:1',
    ];

    protected static $createValidators = [
        Entity::VALIDITY,
    ];

    protected static $editValidators = [
        Entity::VALIDITY,
    ];

    protected function validateValidity(array $input)
    {
        if (isset($input[Entity::ITERATIONS]) === false)
        {
            return;
        }

        if ($input[Entity::ITERATIONS] > 1 and
            isset($input[Entity::VALIDITY]) === false)
        {
            throw new Exception\BadRequestException("Validity Required");
        }
    }
}
