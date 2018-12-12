<?php

namespace RZP\Gateway\Paysecure\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Paysecure;

class Gateway extends Paysecure\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        $request = $this->authorizeMock($input);

        if ($request['method'] === 'direct')
        {
            // For Iframe flow, we use post redirect instead of passing a view
            // thereby, treating it just like redirect flow for mocks
            $request['method'] = 'post';

            // Setting the return URL in the request.
            $request['content'] = [
                Paysecure\Fields::ACCU_RETURN_URL => $input['callbackUrl'],
            ];
        }

        return $request;
    }

    protected function getSoapClientObject($request)
    {
        $soapClient = new SoapClient($request['wsdl'], $request['options']);

        $headers = $this->getRequestHeaders();

        $soapClient->__setSoapHeaders($headers);

        return $soapClient;
    }

    protected function convertToArray($response)
    {
        $response = $response->CallPaySecureResult;

        $xmlResponse = simplexml_load_string(preg_replace('/(<\?xml[^?]+?)utf-16/i', '$1utf-8', $response));

        $xmlResponseArray = Paysecure\XmlSerializer::xmlToArray($xmlResponse);

        return $xmlResponseArray;
    }
}
