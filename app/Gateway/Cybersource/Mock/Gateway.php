<?php

namespace RZP\Gateway\Cybersource\Mock;

use App;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Cybersource;

class Gateway extends Cybersource\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        //
        // This is here for subscriptions tests, which use Cybersource for some reason.
        // When they're changed to use Sharp instead, this can be removed.
        //
        $this->failIfRequired($input);

        return $this->authorizeMock($input);
    }

    protected function getSoapClientObject($request)
    {
        $soapClient = new SoapClient($request['wsdl'], $request['options']);

        $headers = $this->getSoapHeader($request);
        $soapClient->__setSoapHeaders($headers);

        return $soapClient;
    }

    protected function putMockPaymentGatewayUrl(array & $request, $route)
    {
        $url = $this->route->getUrl('mock_acs', ['gateway' => 'mpi_blade']);

        if ($request['url'] === $url)
        {
            return;
        }

        $url = $this->route->getUrl('mock_acs', ['gateway' => 'cybersource']);

        if ($request['method'] === 'get')
        {
            // The key thing now is to replace the url from gateway to our mock one!
            $parts = parse_url($request['url']);

            $url = $url . '&' .$parts['query'];

            $request['url'] = $url;
        }

        $request['url'] = $url;
    }
}
