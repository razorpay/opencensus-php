<?php

namespace RZP\Gateway\Enach\Rbl\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Enach\Rbl;

class Gateway extends Rbl\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input, 'mock_esigner_payment');
    }

    protected function putMockPaymentGatewayUrl(array & $request, $route)
    {
        $gateway = $this->gateway;

        $route = 'mock_esigner_payment';

        $url = $this->route->getUrl($route, ['signer' => 'digio']);

        if ($request['method'] === 'get')
        {
            // The key thing now is to replace the url from gateway to our mock one!
            $parts = parse_url($request['url']);

            $url = $url . '?' .$parts['query'];

            $request['url'] = $url;
        }

        $request['url'] = $url;
    }
}
