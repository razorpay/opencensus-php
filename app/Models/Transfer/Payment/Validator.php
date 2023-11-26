<?php

namespace RZP\Models\Transfer\Payment;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::PAYMENT_ID              => 'required',
        Entity::AMOUNT                  => 'required',
        Entity::AMOUNT_TRANSFERRED      => 'sometimes',
    ];
}
