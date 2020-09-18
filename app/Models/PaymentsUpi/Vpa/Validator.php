<?php

namespace RZP\Models\PaymentsUpi\Vpa;

use App;
use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::USERNAME    => 'required|string|max:200',
        Entity::HANDLE      => 'required|string|max:50',
        Entity::NAME        => 'sometimes|nullable|string|max:100',
        Entity::RECEIVED_AT => 'sometimes|epoch',
        Entity::STATUS      => 'sometimes|string|max:40',
    ];

    protected static $editRules = [
        Entity::NAME        => 'sometimes|nullable|string|max:100',
        Entity::RECEIVED_AT => 'sometimes|epoch',
        Entity::STATUS      => 'sometimes|string|max:40',
    ];
}

