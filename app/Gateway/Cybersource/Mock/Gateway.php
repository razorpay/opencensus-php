<?php

namespace RZP\Gateway\Cybersource\Mock;

use App;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Error\ErrorCode;
use RZP\Gateway\Cybersource;

class Gateway extends Cybersource\Gateway
{
    use Base\Mock\GatewayTrait;

    protected function getSoapClientObject($request)
    {
        $options = array_merge(['encoding' => 'UTF-8', 'soap_version' => SOAP_1_1], $request['options']);

        $soapClient = new SoapClient($request['url'], $request['options']);

        $headers = $this->getSoapHeader($request);
        $soapClient->__setSoapHeaders($headers);

        return $soapClient;
    }
}