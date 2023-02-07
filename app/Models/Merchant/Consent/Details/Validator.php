<?php

namespace RZP\Models\Merchant\Consent\Details;

use RZP\Base;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::URL         => 'sometimes|custom:active_url|max:255|nullable',
        Entity::ID          => 'sometimes|string',
        Entity::CREATED_AT  => 'sometimes'
    ];

    protected static $editRules   = [];

}
