<?php

namespace RZP\Models\Partner\Commission;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $analyticsRules = [
        Constants::TO           => 'required|integer',
        Constants::FROM         => 'required|integer',
        Constants::QUERY_TYPE   => 'required|string|custom',
    ];

    protected static $createRules = [
        Entity::FEE         => 'required|integer',
        Entity::TAX         => 'required|integer',
        Entity::TYPE        => 'required|string|in:'.Type::IMPLICIT . ',' . Type::EXPLICIT,
        Entity::DEBIT       => 'required|integer',
        Entity::CREDIT      => 'required|integer',
        Entity::RECORD_ONLY => 'required|integer',
    ];

    public function validateQueryType($attribute, $value)
    {
        if (Constants::isValidQueryType($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid query type: ' . $value);
        }
    }
}
