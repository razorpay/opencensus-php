<?php

namespace RZP\Tests\Unit\Elfin;

use RZP\Tests\TestCase;
use RZP\Services\Elfin;

class BitlyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $app = $this->createApplication();

        $config             = $app['config'];
        $bitlyConfig        = $config['applications.elfin.bitly'];

        $this->accessToken  = $bitlyConfig['secret'];

        $this->bitly = $this->getMockBuilder(Elfin\Impl\Bitly::class)
                            ->setConstructorArgs([$bitlyConfig])
                            ->setMethods(['makeRequestAndValidateHeader'])
                            ->getMock();
    }

    public function testShorten()
    {
        $url = 'https://www.duckduckgo.com';

        $expectedUrl = 'https://api-ssl.bitly.com/v3/shorten';
        $expectedHeaders = [];
        $expectedParams = [
            'uri'          => $url,
            'format'       => 'json',
            'access_token' => $this->accessToken,
        ];

        $this->bitly->expects($this->once())
                    ->method('makeRequestAndValidateHeader')
                    ->with(
                        $this->equalTo($expectedUrl),
                        $this->equalTo($expectedHeaders),
                        $this->equalTo($expectedParams)
                    )
                    ->willReturn(
                        [
                            'status_code' => 200,
                            'data'        => [
                                'url' => 'https://bitly.dev/xyz',
                            ],
                        ]
                    );

        $shortUrl = $this->bitly->shorten($url);

        $this->assertEquals('https://bitly.dev/xyz', $shortUrl);
    }
}
