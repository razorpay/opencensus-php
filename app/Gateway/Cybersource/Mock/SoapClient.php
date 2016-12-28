<?php

namespace RZP\Gateway\Cybersource\Mock;

use App;
use DOMDocument;
use RZP\Exception;
use RZP\Gateway\Cybersource;
use SoapClient as BaseSoapClient;

class SoapClient extends BaseSoapClient
{
    public function __construct($wsdl, $options = array())
    {
        parent::__construct($wsdl, $options);

        $this->app = App::getFacadeRoot();
    }

    public function __doRequest($request, $location, $action, $version, $oneWay = 0)
    {
        $requestDOM = new DOMDocument('1.0');
        $requestDOM->loadXML($request);

        $requestDOM->formatOutput = true;

        $request = $requestDOM->saveXML();

        return $this->callGatewayRequestInternally($request, $location, $action);
    }

    protected function callGatewayRequestInternally($input, $location, $action)
    {
        $server = $this->app['gateway']->server('cybersource');

        $server->setInput($input);

        $response = $server->processSoap($input, $location, $action);

        return $response;
    }
}
