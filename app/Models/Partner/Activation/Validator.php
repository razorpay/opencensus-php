<?php

namespace RZP\Models\Partner\Activation;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $editRules = [
        Entity::ACTIVATED_AT      => 'sometimes|int',
        Entity::ACTIVATION_STATUS => 'sometimes|max:30',
        Entity::HOLD_FUNDS        => 'sometimes|boolean',
        Entity::SUBMITTED         => 'sometimes|boolean',
        Entity::SUBMITTED_AT      => 'sometimes|int',
        Entity::LOCKED            => 'sometimes|boolean',
    ];

    protected static $createRules = [
        Entity::ACTIVATED_AT      => 'sometimes|int|nullable',
        Entity::ACTIVATION_STATUS => 'sometimes|max:30',
        Entity::HOLD_FUNDS        => 'sometimes|boolean',
        Entity::SUBMITTED         => 'sometimes|boolean',
        Entity::SUBMITTED_AT      => 'sometimes|int|nullable',
        Entity::LOCKED            => 'sometimes|boolean',
    ];
}
