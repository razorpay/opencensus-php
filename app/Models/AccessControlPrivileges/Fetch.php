<?php
namespace RZP\Models\AccessControlPrivileges;

use RZP\Constants;
use RZP\Base\Fetch as BaseFetch;

class Fetch extends BaseFetch
{

    const RULES = [
        self::DEFAULTS => [
            self::EXPAND_EACH             => 'filled|string|in:actions',
            Entity::VISIBILITY            => 'sometimes|int',
        ],
    ];

    const ACCESSES = [
        self::DEFAULTS => [
            self::EXPAND_EACH,
            Entity::VISIBILITY,
        ],
    ];
}
