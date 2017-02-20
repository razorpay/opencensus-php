<?php

namespace RZP\Models\Admin\Org\FieldMap;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ENTITY              => 'required|string|max:255',
        Entity::ORG_ID              => 'required|string|max:20',
        Entity::FIELDS              => 'required',
    ];

    protected static $editRules = [
        Entity::FIELDS              => 'required|string',
    ];
}
