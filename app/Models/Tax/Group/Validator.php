<?php

namespace RZP\Models\Tax\Group;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME           => 'required|string|max:512',
        Entity::TAX_IDS        => 'required|array|min:1|max:5',
        Entity::TAX_IDS . '.*' => 'required|public_id|size:18',
    ];

    protected static $editRules  = [
        Entity::NAME           => 'sometimes|string|max:512',
        Entity::TAX_IDS        => 'sometimes|array|min:1|max:5',
        Entity::TAX_IDS . '.*' => 'sometimes|public_id|size:18',
    ];
}
