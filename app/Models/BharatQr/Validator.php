<?php

namespace RZP\Models\BharatQr;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::AMOUNT                  => 'sometimes|integer|nullable',
        Entity::METHOD                  => 'required|max:30',
        Entity::QR_STRING               => 'required|string|max:255',
    ];
}
