<?php

namespace RZP\Gateway\Paysecure\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Paysecure;

class Gateway extends Paysecure\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }

    protected function getSoapClientObject($request)
    {
        $soapClient = new SoapClient($request['wsdl'], $request['options']);

        $headers = $this->getRequestHeaders();

        $soapClient->__setSoapHeaders($headers);

        return $soapClient;
    }
}
