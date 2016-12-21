<?php

namespace RZP\Services\UrlShortener\Impl\Mock;

use RZP\Services\UrlShortener\Impl\Base;

class Bitly extends Base
{

    private $accessToken;

    public function __construct(array $config)
    {
        $this->accessToken = $config['access_token'];
    }

    public function shorten(string $url)
    {
        $randomShortUrl = 'http://bitly.dev/' . random_alphanum_string(7);

        return $randomShortUrl;
    }
}
