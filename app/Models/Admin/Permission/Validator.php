<?php

namespace RZP\Models\Admin\Permission;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME             => 'required|string|max:255',
        Entity::DESCRIPTION      => 'required|string|min:5|max:255',
        Entity::CATEGORY         => 'required|string|max:255',
        Entity::ASSIGNABLE       => 'sometimes|bool',
        Entity::ORGS             => 'sometimes|array',
        Entity::WORKFLOW_ORGS    => 'sometimes|array',
    ];

    protected static $editRules = [
        Entity::NAME             => 'sometimes|string|max:255',
        Entity::DESCRIPTION      => 'sometimes|string|min:5|max:255',
        Entity::CATEGORY         => 'sometimes|string|max:255',
        Entity::ASSIGNABLE       => 'sometimes|bool',
        Entity::ORGS             => 'sometimes|array',
        Entity::WORKFLOW_ORGS    => 'sometimes|array',
    ];
}
