<?php

namespace RZP\Models\EntityOrigin;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createFromInternalAppRules = [
        'origin_id'   => 'required|string|size:14',
        'origin_type' => 'required|string|in:'. Constants::APPLICATION . ','. Constants::MERCHANT,
        'entity_id'   => 'required|string|min:14',
        'entity_type' => 'required|string',
    ];

    protected static $createRules = [
        'entity_id'   => 'required|string',
        'entity_type' => 'required|string',
    ];
}
