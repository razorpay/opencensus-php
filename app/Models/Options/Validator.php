<?php

namespace RZP\Models\Options;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::OPTIONS          => 'required|array',
        Entity::REFERENCE_ID_KEY => 'sometimes|string',
        Entity::NAMESPACE_KEY    => 'sometimes|string',
        Entity::SERVICE_TYPE_KEY => 'sometimes|string'
    ];
}
