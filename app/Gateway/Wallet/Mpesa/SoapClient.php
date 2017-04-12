<?php

namespace RZP\Gateway\Wallet\Mpesa;

use App;
use SoapClient as BaseSoapClient;

class SoapClient extends BaseSoapClient
{
    protected $app;

    public function __construct($wsdl, $options = [])
    {
        parent::__construct($wsdl, $options);

        $this->app = App::getFacadeRoot();
    }

    public function __soapCall($function_name, $arguments, $options = null, $input_headers = null, & $output_headers = null)
    {
        if ($this->isMpesaGatewayMocked() === true)
        {
            return $this->callGatewayRequestInternally($function_name, $arguments);
        }

        return parent::__soapCall($function_name, $arguments);
    }

    protected function isMpesaGatewayMocked()
    {
        $gateway = $this->app['config']->get('gateway');

        $var = 'mock_wallet_mpesa';

        if (isset($gateway[$var]))
        {
            return $gateway[$var];
        }

        return false;
    }

    protected function callGatewayRequestInternally($method, $arguments)
    {
        $server = $this->app['gateway']->server('wallet_mpesa');

        $server->setInput($arguments);

        $response = $server->$method($arguments);

        return $response;
    }
}
