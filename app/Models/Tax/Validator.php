<?php

namespace RZP\Models\Tax;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME      => 'required|string|max:512',
        Entity::RATE_TYPE => 'required|string|in:percentage,flat',
        Entity::RATE      => 'required|integer',
    ];

    protected static $editRules  = [
        Entity::NAME      => 'sometimes|string|max:512',
        Entity::RATE_TYPE => 'sometimes|string|in:percentage,flat',
        Entity::RATE      => 'sometimes|integer',
    ];

    // TODO: Add  validators for RATE values depending on RATE_TYPE
}
