<?php

namespace RZP\Models\Workflow;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity\Name      => 'required|string|max:150',
        Entity\ORG_ID    => 'required|string|max:14',
        'permissions'    => 'required|array',
        'steps'          => 'required|array',
    ];

    protected static $editRules = [
        Entity\Name      => 'required|string|max:150',
        'permissions'    => 'required|array',
    ];
}
