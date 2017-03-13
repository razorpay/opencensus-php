<?php

namespace RZP\Models\Workflow\Action;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Workflow\Action;
use RZP\Models\Workflow\Action\State;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ADMIN_ID     => 'required|string|max:14',
        Entity::WORKFLOW_ID  => 'required|string|max:14',
    ];

    protected static $editRules = [
        Entity::APPROVED  => 'required|boolean',
    ];
}

