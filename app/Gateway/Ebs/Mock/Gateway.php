<?php

namespace RZP\Gateway\Ebs\Mock;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Gateway\Base;
use RZP\Gateway\Ebs;
use Requests_Response;

class Gateway extends Ebs\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }
}
