<?php

namespace RZP\Models\Admin\Query;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $dbMetaDataQueryRules = [
        'query' => 'required|string|custom',
    ];

    public function validateQuery(string $attribute, string $query)
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

        throw new BadRequestValidationFailureException(
            ErrorCode::BAD_REQUEST_INVALID_QUERY);
    }
}
