<?php

namespace Gateway\Base\Mock;

use Constants\Mode;
use Requests_Response;

class Server
{
    protected function generateHash($content)
    {
        return $this->getGatewayInstance()->generateHash($content);
    }

    protected function getGatewayInstance()
    {
        $class = get_class($this);
        $parts = explode('\\', $class);
        $gateway = 'Gateway\\'.$parts[1].'\\Gateway';

        $gateway = new $gateway;
        $gateway->setMode(Mode::TEST);

        return $gateway;
    }
}
