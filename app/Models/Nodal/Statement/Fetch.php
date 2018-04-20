<?php

namespace RZP\Models\Nodal\Statement;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            EsRepository::QUERY      => 'filled|string|max:200',
        ],
    ];

    const ACCESSES = [
        AuthType::ADMIN_AUTH => [
            EsRepository::QUERY,
        ]
    ];

    const ES_FIELDS = [
        EsRepository::QUERY,
    ];
}
