<?php

namespace RZP\Models\Merchant\AccessMap;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        'entity_type' => 'sometimes|max:255',
        'entity_id'   => 'required_if:entity_type,application|alpha_num|size:14',
    ];

    protected static $addAppRules = [
        'application_id' => 'required|alpha_num|size:14',
    ];
}
