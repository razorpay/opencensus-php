<?php

namespace RZP\Models\Payment\TerminalAnalytics;

use RZP\Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::PAYMENT_ID              => 'required|alpha_num|size:14',
        Entity::TERMINAL_ID             => 'required|alpha_num|size:14',
        Entity::TERMINAL_STATUS         => 'sometimes|boolean',
        Entity::TERMINAL_RESPONSE_TIME  => 'sometimes|integer',
        Entity::TERMINAL_STATUS_CODE    => 'sometimes|integer',
        Entity::TERMINAL_STATUS_MSG     => 'sometimes|string',
        // For default payments, payment_type is 1. In case of
        // payment used for checking terminal_uptime, the
        // payment_type will be 2. We will then use these
        // payments for refund from the bank, if needed
        // This field is for future references only
        Entity::PAYMENT_TYPE            => 'sometimes|integer|in:1,2',
     );
}