<?php

namespace RZP\Services\UrlShortner\Impl;

use Requests;

class Gimli extends Base
{

    private $secret;

    private $apiUrl;

    public function __construct(array $config)
    {
        $baseUrl = $config['base_url'];

        $this->apiUrl = $baseUrl + '/shorten';

        $this->secret = $config['secret'];
    }

    public function shorten(string $url)
    {
        $payload = [
            'url' => $url,
        ];

        $headers = [
            'content-type' => 'application/json',
            'x-signature'  => $this->getSignature($payload),
        ];

        $res = Requests::post($this->apiUrl, $headers, [], $payload);

        $this->validateResponseHeader($res);

        $resBody = json_decode($res->body, true);

        return $resBody['hash'];
    }

    protected function getSignature(array $payload)
    {
        $payloadString = json_encode($payload, JSON_UNESCAPED_SLASHES);

        return hash_hmac('sha1', $payloadString, $this->secret);
    }
}
