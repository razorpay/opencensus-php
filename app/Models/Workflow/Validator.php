<?php

namespace RZP\Models\Workflow;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity\Name      => 'required|string|max:150',
        Entity\ORG_ID    => 'required|string|max:14',
    ];
}
