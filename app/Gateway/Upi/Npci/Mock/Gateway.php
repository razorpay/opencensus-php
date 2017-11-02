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
        $method = explode('/', $request['url'])[5];

        $app = App::getFacadeRoot();

        $server = $app['gateway']->server('upi_npci');

        $server->setInput($request);

        $response = $server->upiRequest($method, $request);

        return $response;
    }

    protected function makeUrl(string $method, string $txnId)
    {
        return "http://api.razorpay.in/v1/upi_npci/$method/1.0/urn:txnid:$txnId";
    }
}
