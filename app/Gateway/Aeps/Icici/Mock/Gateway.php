<?php

namespace RZP\Gateway\Aeps\Icici\Mock;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Gateway\Aeps\Icici;

class Gateway extends Icici\Gateway
{
    use Base\Mock\GatewayTrait;

    const MOCK_CERT_PATH_PUBLIC  = 'certificates/cert.pem';
    const MOCK_CERT_PATH_PRIVATE = 'certificates/key.pem';

    protected function getEncryptor(): Icici\Encryptor
    {
        $encryptor = new Icici\Encryptor(2, $this->getIv(), true);

        $encryptor->setPublicKey($this->getPublicKey());
        $encryptor->setPrivateKey($this->getPrivateKey());

        return $encryptor;
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

    protected function getPublicKey()
    {
        return file_get_contents(__DIR__ . '/' . self::MOCK_CERT_PATH_PUBLIC);
    }

    protected function getPrivateKey()
    {
        return file_get_contents(__DIR__ . '/' . self::MOCK_CERT_PATH_PRIVATE);
    }
}
