<?php

namespace RZP\Services\Elfin\Impl;

use Requests;
use RZP\Constants\HashAlgo;

class Gimli extends Base
{
    private $apiBaseUrl;

    private $secret;

    public function __construct(array $config)
    {
        $this->apiBaseUrl = $config['base_url'];
        $this->secret     = $config['secret'];
    }

    /**
     * {@inheritDoc}
     */
    public function shorten(string $url, array $input = [], bool $fail = false)
    {
        // Out of $input, only 'ptype' needs to be sent as query string, others go as body
        $apiUrl  = $this->getApiUrl(array_only($input, 'ptype'));
        $params  = $this->getParams($url, array_except($input, 'ptype'));
        $headers = $this->getHeaders($params);

        $res = $this->makeRequestAndValidateHeader($apiUrl, $headers, $params);

        return $res['hash'];
    }

    /**
     * Expands a given hash, Returns array if success else null
     * @param  string $hash
     * @return array|null
     */
    public function expand(string $hash)
    {
        $apiUrl   = "{$this->apiBaseUrl}hashes/{$hash}";
        $headers  = $this->getHeaders("");
        $response = Requests::get($apiUrl, $headers);
        $body     = $response->body;

        return (($response->status_code === 200) and (isJson($body) === true)) ? json_decode($body, true) : null;
    }

    /**
     * Expands a given hash and returns metadata saved with it in gimli, Returns array if success else null
     * @param  string $hash
     * @return array|null
     */
    public function expandAndGetMetadata(string $hash)
    {
        $details = $this->expand($hash);

        if ($details !== null)
        {
            return $details['url_aliases'][0]['metadata'];
        }
    }

    /**
     * Returns gimli api url with query parameters applied
     * @param  array  $query
     * @return string
     */
    protected function getApiUrl(array $query): string
    {
        $query = http_build_query($query);

        $apiUrl = "{$this->apiBaseUrl}shorten";

        if (empty($query) === false)
        {
            $apiUrl .= "?{$query}";
        }

        return $apiUrl;
    }

    protected function getParams(string $url, array $input)
    {
        $params = $input + ['url' => $url];

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
