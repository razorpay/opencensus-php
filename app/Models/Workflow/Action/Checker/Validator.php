<?php

namespace RZP\Models\Workflow\Action\Checker;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Workflow\Action;
use RZP\Models\Workflow\Action\State;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ADMIN_ID  => 'required|string|max:14',
        Entity::ACTION_ID => 'required|string|max:14',
        Entity::STEP_ID   => 'required|string|max:14',
        Entity::APPROVED  => 'required|boolean',
    ];
}

