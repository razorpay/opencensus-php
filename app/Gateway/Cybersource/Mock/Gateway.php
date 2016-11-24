<?php

namespace RZP\Gateway\Cybersource\Mock;

use App;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Cybersource;

class Gateway extends Cybersource\Gateway
{
    use Base\Mock\GatewayTrait;

    protected function getSoapClientObject($request)
    {
        $soapClient = new SoapClient($request['url'], $request['options']);

        $headers = $this->getSoapHeader($request);
        $soapClient->__setSoapHeaders($headers);

        return $soapClient;
    }
}
