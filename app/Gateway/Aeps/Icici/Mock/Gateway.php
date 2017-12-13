<?php

namespace RZP\Gateway\Aeps\Icici\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Aeps\Icici;

class Gateway extends Icici\Gateway
{
    use Base\Mock\GatewayTrait;

    protected function getEncryptor(): Icici\Encryptor
    {
        return new Icici\Encryptor(2, $this->getIv(), true);
    }

    /**
     * In the main Gateway, we initiate a socket connection
     * and send the data across to the API via sockets.
     * For mock, we do not need this and can override it to use
     * the usual flow we use for other gateways and replace the server
     * URL with the mock server's URL
     */
    protected function sendRequest($requestXmlData)
    {
        $request = [
            'url'     => $this->route->getUrlWithPublicAuth('mock_aeps_payment',['bank' => 'icici']),
            'content' => $requestXmlData,
            'method'  => 'POST'
        ];

        $response = $this->sendGatewayRequest($request);

        return $response->body;
    }
}
