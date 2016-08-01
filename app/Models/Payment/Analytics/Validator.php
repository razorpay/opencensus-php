<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Models\Base;

class Validator extends Base\Validator
{

    protected static $createRules = array(
        Entity::PAYMENT_ID      => 'required|alpha_num|size:14',
        Entity::TERMINAL_ID     => 'required|alpha_num|size:14',
        Entity::STATUS          => 'sometimes|boolean',
        Entity::RESPONSE_TIME   => 'required|numeric',
        Entity::STATUS_CODE     => 'sometimes|integer',
        Entity::STATUS_MSG      => 'sometimes|string',
        Entity::PAYMENT_TYPE    => 'required|integer|in:0,1'
    );
}
