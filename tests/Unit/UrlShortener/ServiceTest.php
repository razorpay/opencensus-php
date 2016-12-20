<?php

namespace RZP\Tests\Unit\UrlShortener;

use RZP\Tests\TestCase;

class ServiceTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->app = $this->createApplication();

        $this->service = $this->app['url_shortener'];
    }

    public function testShorten()
    {
        // TODO: Finish this

        $url = 'http://www.duckduckgo.com';

        $shortUrl = $this->service->shorten($url);
    }
}
