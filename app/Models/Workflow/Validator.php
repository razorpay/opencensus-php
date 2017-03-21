<?php

namespace RZP\Models\Workflow;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        'name'      => 'required|string',
        'org_id'    => 'required|string',
    ];
}
