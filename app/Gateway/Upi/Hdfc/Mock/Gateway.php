<?php

namespace RZP\Gateway\Upi\Hdfc\Mock;

use EE\Exception;
use EE\Error\ErrorCode;
use RZP\Http\Route;
use RZP\Gateway\Base;
use RZP\Gateway\Upi\Hdfc;

class Gateway extends Hdfc\Gateway
{
    use Base\Mock\GatewayTrait;

    protected function putMockPaymentGatewayUrl(array & $request)
    {
        $route = 'mock_upi_payment';

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

    protected function getUrl($type = 'authorize')
    {
        $url = parent::getUrl($type);

        $url = $this->route->getUrlWithPublicAuth(
                        'mock_upi_payment', ['bank' => 'hdfc']);
        return $url;
    }
}
