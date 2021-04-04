<?php

namespace RZP\Tests\Unit\Elfin;

use RZP\Tests\TestCase;
use RZP\Services\Elfin;

class GimliTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $app = $this->createApplication();

        $config             = $app['config'];
        $gimliConfig        = $config['applications.elfin.gimli'];

        $this->gimli = $this->getMockBuilder(Elfin\Impl\Gimli::class)
                            ->setConstructorArgs([$gimliConfig])
                            ->setMethods(['makeRequestAndValidateHeader'])
                            ->getMock();
    }

    public function testShorten()
    {
        $url = 'https://www.duckduckgo.com';

        $expectedUrl = 'http://gimli.razorpay.in/v1/shorten';
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
                            'hash' => 'http://dwarf.razorpay.in/xyz',
                            'comment' => null,
                            'clicks' => 2,
                            'created_at' => time(),
                        ]
                    );

        $shortUrl = $this->gimli->shorten($url);

        $this->assertStringContainsString('http://dwarf.razorpay.in/', $shortUrl);
    }
}
