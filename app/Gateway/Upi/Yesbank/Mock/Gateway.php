<?php

namespace RZP\Gateway\Upi\Yesbank\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use RZP\Http\Route;
use RZP\Gateway\Base;
use RZP\Gateway\Upi\Yesbank;

class Gateway extends Yesbank\Gateway
{
    use Base\Mock\GatewayTrait;
}
