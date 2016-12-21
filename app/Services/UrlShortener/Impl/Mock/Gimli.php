<?php

namespace RZP\Services\UrlShortener\Impl\Mock;

use RZP\Services\UrlShortener\Impl\Base;

class Gimli extends Base
{

    private $secret;

    private $apiUrl;

    public function __construct(array $config)
    {
        $baseUrl = $config['base_url'];

        $this->apiUrl = $baseUrl . '/shorten';

        $this->secret = $config['secret'];
    }

    public function shorten(string $url)
    {
        $randomShortUrl = 'http://dwarf.razorpay.dev/' . random_alphanum_string(7);

        return $randomShortUrl;
    }
}
