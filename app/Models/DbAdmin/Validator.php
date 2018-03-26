<?php

namespace RZP\Models\DbAdmin;

use RZP\Base;
use RZP\Constants\Mode;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const INVALID_MODE  = 'Invalid mode';
    const INVALID_QUERY = 'The query is invalid or is not allowed';

    protected static $explainQueryRules = [
        'mode'  => 'required|string|custom',
        'query' => 'required|string|custom',
    ];

    public function validateMode($attribute, $mode)
    {
        if (Mode::exists($mode) === false)
        {
            throw new BadRequestValidationFailureException(self::INVALID_MODE);
        }
    }

    public function validateQuery($attribute, $query)
    {
        $allowedQueryPrefixes = QueryPrefix::ALLOWED_QUERY_PREFIXES;

        $query = strtolower($query);

        foreach ($allowedQueryPrefixes as $queryPrefix)
        {
            if (starts_with($query, $queryPrefix) === true)
            {
                return;
            }
        }

        throw new BadRequestValidationFailureException(self::INVALID_QUERY);
    }

}
