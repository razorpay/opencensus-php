<?php

namespace RZP\Gateway\Esigner\Digio\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Esigner\Digio;

class Gateway extends Digio\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input, 'mock_esigner_payment');
    }

    protected function putMockPaymentGatewayUrl(array & $request, $route)
    {
        $route = 'mock_esigner_payment';

        $url = $this->route->getUrl($route, ['signer' => 'esigner_digio']);

        // The key thing now is to replace the url from gateway to our mock one!
        $parts = parse_url($request['url']);

        // To handle the URL fragment
        if (isset($parts['fragment']) === true && $parts['fragment'] !== "")
        {
            $fragmentParts = explode('?', $parts['fragment']);

            $url .= '?' . $fragmentParts['1'];
        }

        $request['url'] = $url;
    }
}
