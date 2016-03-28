<?php

namespace Gateway\Wallet\Payumoney\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use Gateway\Base;
use Gateway\Wallet\Payumoney;

class Gateway extends Payumoney\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        parent::authorize($input);
    }
}
