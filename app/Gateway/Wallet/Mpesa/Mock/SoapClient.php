<?php

namespace RZP\Gateway\Wallet\Mpesa\Mock;

use App;
use SoapClient as BaseSoapClient;

class SoapClient extends BaseSoapClient
{
    protected $app;

    public function __construct($wsdl, $options = array())
    {
        $this->app = App::getFacadeRoot();
    }

    public function __soapCall($function_name, $arguments, $options = null, $input_headers = null, & $output_headers = null)
    {
        return $this->callGatewayRequestInternally($function_name, $arguments);
    }

    protected function callGatewayRequestInternally(string $method, array $arguments)
    {
        $server = $this->app['gateway']->server('wallet_mpesa');

        $server->setInput($arguments);

        $response = $server->$method($arguments);

        return $response;
    }
}
