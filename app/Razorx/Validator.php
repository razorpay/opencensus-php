<?php

namespace App\Razorx;

use App\Base;

class Validator extends Base\Validator
{
    protected static $razorxInvalidationRules = [
        'type'          => 'required|string|in:merchant,all',
        'merchants'     => 'sometimes|array|min:1|max:50',
        'merchants.*'   => 'string',
    ];
}
