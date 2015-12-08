<?php

namespace Gateway\Wallet\Payzapp\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\Base;
use Gateway\Wallet\Payzapp;

class Gateway extends Payzapp\Gateway
{
    use Base\Mock\GatewayTrait;
}
