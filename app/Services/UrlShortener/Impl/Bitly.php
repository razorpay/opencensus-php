<?php

namespace RZP\Services\UrlShortener\Impl;

use Requests;

use RZP\Exception;

class Bitly extends Base
{
    const API = 'https://api-ssl.bitly.com/v3/shorten';

    private $accessToken;

    public function __construct(array $config)
    {
        $this->accessToken = $config['access_token'];
    }

    public function shorten(string $url)
    {
        $params = [
            'uri'          => $url,
            'format'       => 'json',
            'access_token' => $this->accessToken,
        ];

        $res = Requests::post(self::API, [], $params);

        $this->validateResponseHeader($res);

        $resBody = json_decode($res->body, true);

        $this->validateResponse($resBody);

        return $resBody['data']['url'];
    }

    protected function validateResponse(array $resBody)
    {
        //
        // Bitly has response code as 200 always.
        // In the response body it sends error codes. So need to do this too.
        //

        if ($resBody['status_code'] === 200)
        {
            return;
        }

        throw new Exception\RuntimeException($resBody['status_txt'], $resBody);
    }
}
