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

    /**
     * {@inheritDoc}
     */
    public function shorten(string $url, array $input = [], bool $fail = false)
    {
        $apiUrl  = $this->getApiUrl($input);
        $params  = $this->getParams($url);
        $headers = $this->getHeaders($params);

        $res = $this->makeRequestAndValidateHeader($apiUrl, $headers, $params);

        return $res['hash'];
    }

    /**
     * Returns gimli api url with query parameters applied
     * @param  array  $input
     * @return string
     */
    protected function getApiUrl(array $input): string
    {
        $query       = array_only($input, 'ptype');
        $queryString = http_build_query($query);

        $apiUrl = $this->apiUrl;

        if (empty($queryString) === false)
        {
            $apiUrl .= "?{$queryString}";
        }

        return $apiUrl;
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
