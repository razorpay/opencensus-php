<?php

namespace RZP\Models\Admin\Query;

use RZP\Base;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const INVALID_QUERY = 'The query is invalid or is not allowed';

    protected static $dbQueryRules = [
        'query' => 'required|string|custom',
    ];

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
