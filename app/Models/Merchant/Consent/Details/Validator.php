<?php

namespace RZP\Models\Merchant\Consent\Details;

use RZP\Base;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::URL => 'sometimes|custom:active_url|max:255|nullable',
    ];

    protected static $editRules   = [];

}
