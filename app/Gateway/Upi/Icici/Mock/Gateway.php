<?php

namespace RZP\Gateway\Upi\Icici\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Upi\Icici;

class Gateway extends Icici\Gateway
{
    use Base\Mock\GatewayTrait;

    public function authorize(array $input)
    {
        return $this->authorizeMock($input);
    }

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

    /**
     * We use a tiny 128 bit key for mock
     * testing which is committed as well
     */
    protected function getPublicKey(): string
    {
        return file_get_contents(__DIR__ . '/keys/mockserver.pub');
    }

    /**
     * This is the privateKey for the Gateway Client
     */
    protected function getPrivateKey(): string
    {
        return file_get_contents(__DIR__ . '/keys/mockclient.key');
    }

    protected function getUrl($type = 'authorize'): string
    {
        $url = $this->route->getUrlWithPublicAuth(
                        'mock_upi_payment', ['bank' => 'icici']);
        return $url;
    }
}
