<?php

namespace Models\Terminal;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Payment\Gateway;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::MERCHANT_ID               => 'required|alpha_num',
        Entity::GATEWAY                   => 'required|in:hdfc,atom',
        Entity::GATEWAY_MERCHANT_ID       => 'required',
        Entity::GATEWAY_TERMINAL_ID       => 'required',
        Entity::GATEWAY_TERMINAL_PASSWORD => 'required');
}
