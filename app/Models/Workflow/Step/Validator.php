<?php

namespace RZP\Models\Workflow\Step;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity\ROLE_ID        => 'required|string|max:14',
        Entity\LEVEL          => 'required|integer',
        Entity\REVIEWER_COUNT => 'required|integer',
        Entity\WORKFLOW_ID    => 'required|string|max:14',
    ];
}
