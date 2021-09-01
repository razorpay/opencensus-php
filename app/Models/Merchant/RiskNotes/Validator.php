<?php

namespace RZP\Models\Merchant\RiskNotes;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NOTE            => 'required|string|min:1|max:1000',
    ];
}
