<?php

namespace RZP\Services\Elfin\Impl;

use RZP\Exception;

class Bitly extends Base
{
    const API = 'https://api-ssl.bitly.com/v3/shorten';

    private $accessToken;

    public function __construct(array $config)
    {
        $this->accessToken = $config['secret'];
    }

    /**
     * {@inheritDoc}
     */
    public function shorten(string $url, array $input = [], bool $fail = false)
    {
        // Note: $input doesn't get used in bitly driver; only in gimli driver

        $params = $this->getParams($url);

        $res = $this->makeRequestAndValidateHeader(self::API, [], $params);

        $this->validateResponse($res);

        return $res['data']['url'];
    }

    protected function getParams(string $url)
    {
        $params = [
            'uri'          => $url,
            'format'       => 'json',
            'access_token' => $this->accessToken,
        ];

        return $params;
    }

    protected function validateResponse(array $res)
    {
        //
        // Bitly has response code as 200 always.
        // In the response body it sends error codes. So need to do this too.
        // This also does key existense check in resbody for safety.
        //

        if (($res['status_code'] !== 200) or
            (isset($res['data']['url']) === false))
        {
            throw new Exception\RuntimeException($res['status_txt'], $res);
        }
    }
}
