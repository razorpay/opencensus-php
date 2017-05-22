<?php

namespace RZP\Gateway\Wallet\Mpesa;

use App;
use SoapClient as BaseSoapClient;

class SoapClient extends BaseSoapClient
{
    protected $app;

    protected $mode;

    protected $gateway = 'wallet_mpesa';

    public function __construct($wsdl, $options = array())
    {
        $this->app = App::getFacadeRoot();

        if ($this->isGatewayMocked() === false)
        {
            parent::__construct($wsdl, $options);
        }
    }

    public function __soapCall($function_name, $arguments, $options = null, $input_headers = null, & $output_headers = null)
    {
        if ($this->isGatewayMocked() === true)
        {
            return $this->callGatewayRequestInternally($function_name, $arguments);
        }

        return parent::__soapCall($function_name, $arguments);
    }

    protected function callGatewayRequestInternally(string $method, array $arguments)
    {
        $server = $this->app['gateway']->server($this->gateway);

        $server->setInput($arguments);

        $response = $server->$method($arguments);

        return $response;
    }

    protected function isGatewayMocked()
    {
        $gateway = $this->app['config']->get('gateway');

        if ($this->gateway === null)
            $this->gateway = 'hdfc';

        $var = 'mock_' . $this->gateway;

        if (isset($gateway[$var]))
        {
            return $gateway['mock_' . $this->gateway];
        }

        return false;
    }
}
