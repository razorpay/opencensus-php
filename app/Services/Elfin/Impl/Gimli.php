<?php

namespace RZP\Services\Elfin\Impl;

use RZP\Constants\HashAlgo;

class Gimli extends Base
{
    private $secret;

    private $apiUrl;

    public function __construct(array $config)
    {
        $baseUrl = $config['base_url'];

        $this->apiUrl = $baseUrl . 'shorten';

        $this->secret = $config['secret'];
    }

    public function shorten(string $url, bool $fail = false)
    {
        $params = $this->getParams($url);

        $headers = $this->getHeaders($params);

        $res = $this->makeRequestAndValidateHeader($this->apiUrl, $headers, $params);

        return $res['hash'];
    }

    protected function getParams(string $url)
    {
        $params = [
            'url' => $url,
        ];

        $params = json_encode($params, JSON_UNESCAPED_UNICODE);

        return $params;
    }

    protected function getHeaders(string $params)
    {
        $signature = $this->getSignature($params);

        $headers = [
            'Content-Type' => 'application/json',
            'x-signature'  => $signature,
        ];

        return $headers;
    }

    protected function getSignature(string $payload)
    {
        return hash_hmac(HashAlgo::SHA1, $payload, $this->secret);
    }
}
