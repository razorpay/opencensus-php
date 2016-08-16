<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array (
        Entity::GATEWAY         => 'required|string',
        Entity::FROM            => 'required|integer',
        Entity::TO              => 'required|integer',
        Entity::REASON          => 'sometimes|string',
        Entity::BANK            => 'sometimes|string',
    );

    protected static $editRules = array(
        Entity::FROM            => 'required|integer',
        Entity::TO              => 'required|integer',
        Entity::REASON          => 'sometimes|string',
        Entity::BANK            => 'sometimes|string',
    );
}
