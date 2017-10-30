<?php

namespace RZP\Models\QrCode;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::AMOUNT   => 'sometimes|integer|nullable',
        Entity::PROVIDER => 'required|in:bharat_qr',
    ];
}
