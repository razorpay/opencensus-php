<?php

namespace RZP\Gateway\AxisMigs\Mock;

use RZP\Exception;
use RZP\Gateway\AxisMigs;
use RZP\Gateway\Base;

class Gateway extends AxisMigs\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }

    protected function putMockPaymentGatewayUrl(array & $request, $route)
    {
        $url = $this->route->getUrl('mock_acs', ['gateway' => 'mpi_blade']);

        if ($request['url'] === $url)
        {
            return;
        }

        $gateway = $this->gateway;

        if (is_null($route))
        {
            $route = 'mock_' . $gateway . '_payment';
        }

        $url = $this->route->getUrlWithPublicAuth($route);

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
