<?php

namespace RZP\Models\PaymentsUpi\Vpa;

use App;
use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::USERNAME => 'required|string|max:200',
        Entity::HANDLE   => 'required|string|max:50',
    ];
}

