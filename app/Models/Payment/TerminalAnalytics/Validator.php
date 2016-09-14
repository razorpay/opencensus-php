<?php

namespace RZP\Models\Payment\TerminalAnalytics;

use RZP\Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::PAYMENT_ID              => 'required|alpha_num|size:14',
        Entity::TERMINAL_ID             => 'required|alpha_num|size:14',
        Entity::TERMINAL_STATUS         => 'sometimes|boolean',
        Entity::TERMINAL_RESPONSE_TIME  => 'sometimes|numeric',
        Entity::TERMINAL_STATUS_CODE    => 'sometimes|integer',
        Entity::TERMINAL_STATUS_MSG     => 'sometimes|string',
        Entity::PAYMENT_TYPE            => 'sometimes|integer|in:0,1',
     );
}