<?php

namespace Gateway\Wallet\Olamoney\Mock;

use Http\Route;
use EE\Exception;
use Gateway\Base;
use EE\Error\ErrorCode;
use Gateway\Wallet\Olamoney;

class Gateway extends Olamoney\Gateway
{
    use Base\Mock\GatewayTrait;

}
