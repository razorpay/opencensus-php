<?php

namespace RZP\Tests\Unit\UrlShortener;

use RZP\Tests\TestCase;
use RZP\Services\UrlShortener;

class GimliTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $app = $this->createApplication();

        $config             = $app['config'];
        $gimliConfig        = $config['applications.url_shortener.gimli'];

        $this->gimli = $this->getMockBuilder(UrlShortener\Impl\Gimli::class)
                            ->setConstructorArgs([$gimliConfig])
                            ->setMethods(['makeRequestAndValidateHeader'])
                            ->getMock();
    }

    public function testShorten()
    {
        $url = 'https://www.duckduckgo.com';

        $expectedUrl = 'http://gimli.razorpay.dev/v1/shorten';
        $expectedHeaders = [
            'Content-Type' => 'application/json',
            'x-signature'  => '27041f5973ceb1d6aea57fb4827865e8f56316e8',
        ];
        $expectedParams = '{"url":"https:\/\/www.duckduckgo.com"}';

        $this->gimli->expects($this->once())
                    ->method('makeRequestAndValidateHeader')
                    ->with(
                        $this->equalTo($expectedUrl),
                        $this->equalTo($expectedHeaders),
                        $this->equalTo($expectedParams)
                    )
                    ->willReturn(
                        [
                            'id' => 'something',
                            'url' => $url,
                            'hash' => 'http://dwarf.razorpay.dev/xyz',
                            'comment' => null,
                            'clicks' => 2,
                            'created_at' => time(),
                        ]
                    );

        $shortUrl = $this->gimli->shorten($url);

        $this->assertContains('http://dwarf.razorpay.dev/', $shortUrl);
    }
}
