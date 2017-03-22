<?php

namespace RZP\Models\Workflow\Step;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        'role_id'        => 'required|string',
        'level'          => 'required|integer',
        'reviewer_count' => 'required|integer',
    ];
}
