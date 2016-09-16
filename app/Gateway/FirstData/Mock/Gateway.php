<?php

namespace RZP\Gateway\FirstData\Mock;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Gateway\FirstData;
use RZP\Gateway\Base;

class Gateway extends FirstData\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }

    protected function getServer()
    {
        $app = App::getFacadeRoot();

        return $app['gateway']->server($this->gateway);
    }
}
