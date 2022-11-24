<?php

namespace RZP\Gateway\Enach\Rbl\Mock;

use RZP\Gateway\Base;
use RZP\Models\Payment;
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
        $gateway = Payment\Gateway::ESIGNER_DIGIO;

        $gateway = $this->input['authenticate']['gateway'] ?? $gateway;

        $url = $this->route->getUrl($route, ['signer' => $gateway]);

        if ($request['method'] === 'get')
        {
            // The key thing now is to replace the url from gateway to our mock one!
            $parts = parse_url($request['url']);

            if (isset($parts['query']) === true && $parts['query'] !== "")
            {
                $url = $url . '?' .$parts['query'];
            }

            $request['url'] = $url;
        }

        $request['url'] = $url;
    }
}
