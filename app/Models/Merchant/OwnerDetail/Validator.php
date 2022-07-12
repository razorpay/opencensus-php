<?php

namespace RZP\Models\Merchant\OwnerDetail;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID            => 'required|string|size:14',
        Entity::GATEWAY                => 'sometimes|string',
        Entity::OWNER_DETAILS          => 'sometimes|array',
    ];

    protected static $editRules = [
        Entity::ID                     => 'required|string|size:14',
        Entity::MERCHANT_ID            => 'required|string|size:14',
        Entity::GATEWAY                => 'sometimes|string',
        Entity::OWNER_DETAILS          => 'sometimes|array',
    ];

    protected static $deleteByApiRules = [
        'owner_id'                     => 'required|string|size:14'
    ];

}
