<?php

namespace RZP\Gateway\Upi\Npci\Mock;

use App;
use RZP\Gateway\Upi\Npci;

class Gateway extends Npci\Gateway
{
    protected function sendGatewayRequest($request)
    {
        return $this->callGatewayRequestInternally($request);
    }

    protected function callGatewayRequestInternally($request)
    {
        $method = explode('/', $request['url'])[4];

        $app = App::getFacadeRoot();

        $server = $app['gateway']->server('upi_npci');

        $server->setInput($request);

        $response = $server->$method($request);

        return $response;
    }
}
