<?php

namespace RZP\Models\Schedule;

use RZP\Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::NAME     => 'sometimes|string',
        Entity::OWNER_ID => 'required|string',
        Entity::TYPE     => 'required|string',
        Entity::PERIOD   => 'required|alpha',
        Entity::INTERVAL => 'sometimes|integer',
        Entity::ANCHOR   => 'sometimes|integer',
        Entity::DELAY    => 'required|integer',
        Entity::NEXT_RUN => 'sometimes|integer',
    );

    protected static $editRules = array(
        Entity::NAME     => 'sometimes|string',
        Entity::INTERVAL => 'sometimes|integer',
        Entity::ANCHOR   => 'sometimes|integer',
        Entity::DELAY    => 'required|integer',
        Entity::NEXT_RUN => 'sometimes|integer',
    );

    protected static $createValidators = array(
    );
}
