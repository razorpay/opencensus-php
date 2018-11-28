<?php

namespace RZP\Models\SubscriptionRegistration;

use App;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::EXPIRE_AT                       => 'sometimes|epoch',
        Entity::MAX_AMOUNT                      => 'sometimes|integer|nullable',
        Entity::AUTH_TYPE                       => 'sometimes|string|nullable|in:netbanking,aadhaar',
        Entity::METHOD                          => 'sometimes|string|nullable|in:emandate,card',
        Entity::NOTES                           => 'sometimes|notes',
    ];
}
