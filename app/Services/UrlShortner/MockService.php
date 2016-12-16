<?php

namespace RZP\Services\UrlShortner;

use RZP\Trace\TraceCode;

class MockService extends Impl\Base
{
    function __construct($app)
    {
        ;
    }

    public function shorten(string $url)
    {
        $randomUrl = 'http://bitly.dev/' . random_alphanum_string(7);

        return $randomUrl;
    }
}
