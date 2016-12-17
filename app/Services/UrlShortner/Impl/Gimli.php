<?php

namespace RZP\Services\UrlShortner\Impl;

use Requests;

class Gimli extends Base
{
    const API = 'http://gimli.razorpay.dev/v1/shorten';

    private $secret;

    public function __construct(array $config)
    {
        $this->secret = $config['secret'];
    }

    public function shorten(string $url)
    {
        $payload = [
            'url' => $url,
        ];

        $payload = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $headers = [
            'Content-Type' => 'application/json',
            'x-signature'  => $this->getSignature($payload),
        ];

        $res = Requests::post(self::API, $headers, $payload);

        $this->validateResponseHeader($res);

        $resBody = json_decode($res->body, true);

        return $resBody['hash'];
    }

    protected function getSignature(string $payload)
    {
        return hash_hmac('sha1', $payload, $this->secret);
    }
}
