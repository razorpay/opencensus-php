<?php

namespace RZP\Gateway\Upi\Base\Mock;

use RZP\Exception\LogicException;

trait GatewayTrait
{
    public function authorize(array $input)
    {
        return $this->authorizeMock($input, self::MOCK_ROUTE);
    }

    protected function getUrl($type = 'authorize'): string
    {
        return $this->route->getUrlWithPublicAuth(
                        self::MOCK_ROUTE, ['bank' => self::ACQUIRER]);
    }

    protected function sendProxyRequestToDark($url, $content, $headers)
    {
        if (starts_with($url, 'https://api-dark.razorpay.com') === false)
        {
            throw new LogicException(
                'URL for Dark Requests should start with https://api-dark.razorpay.com',
                null,
                ['URL' => $url]
            );
        }

        $this->action = 'redirect_to_dark';

        $request = [
            'url'       => $url,
            'content'   => $content,
            'headers'   => $headers,
            'method'    => 'POST',
            'options'   => [
                'timeout'               => 10,
                'connection_timeout'    => 40,
            ],
        ];

        $serverResponse = $this->callGatewayRequestFunctionInternally($request);

        return $this->prepareInternalResponse($serverResponse);
    }
}
