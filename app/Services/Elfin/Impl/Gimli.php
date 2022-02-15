<?php

namespace RZP\Services\Elfin\Impl;

use RZP\Constants\HashAlgo;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Http\Request\Requests;

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
     *
     * Sample success JSON response:
     * {
     *    "id":"AUpw6M9h1CCK24",
     *    "url":"http://pages.razorpay.in/pl_AUpw6KzJrLSBki/view",
     *    "hash":"http://localhost:8081/i/vh3M8Xh",
     *    "comment":"",
     *    "clicks":0,
     *    "created_at":1530638371,
     *    "updated_at":1530638371,
     *    "url_aliases":[
     *       {
     *          "id":45,
     *          "url_id":"AUpw6M9h1CCK24",
     *          "hash":"vh3M8Xh",
     *          "metadata": {
     *              "id": "pl_AUpw6KzJrLSBki",
     *              "entity": "payment_link",
     *              "mode": "test"
     *          },
     *          "created_at":1530638411
     *       }
     *    ],
     *    "hash_key":"vh3M8Xh"
     * }
     *
     * Note - In case of edits being allowed on Gimli in future, there could be
     *        multiple url_aliases, in that case the top one would be the most recent.
     *
     * @param  string $hash
     * @return array|null
     */
    public function expand(string $hash)
    {
        $apiUrl   = "{$this->apiBaseUrl}/hashes/{$hash}";
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

    public function update(string $hash, string $input)
    {
        $apiUrl   = "{$this->apiBaseUrl}/hashes/{$hash}/url_alias";
        $headers  = $this->getHeaders($input);
        $response = Requests::patch($apiUrl, $headers, $input);
        $body     = json_decode($response->body, true);

        return $body['hash'];
    }

    /**
     * Returns gimli api url with query parameters applied
     * @param  array  $query
     * @return string
     */
    protected function getApiUrl(array $query): string
    {
        $query = http_build_query($query);

        $apiUrl = "{$this->apiBaseUrl}/shorten";

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
