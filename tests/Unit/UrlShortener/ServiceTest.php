<?php

namespace RZP\Tests\Unit\UrlShortner;

use RZP\Tests\TestCase;

class ServiceTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->app = $this->createApplication();

        $this->service = $this->app['url_shortner'];
    }

    public function testShorten()
    {
        // TODO

        $url = 'http://www.duckduckgo.com';

        $shortUrl = $this->service->shorten($url);

        sd($shortUrl);
    }
}
