<?php

namespace RZP\Tests\Unit\Elfin;

use RZP\Tests\TestCase;
use RZP\Services\Elfin;
use RZP\Exception;

class ServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $app = $this->createApplication();

        $config            = $app['config'];
        $trace             = $app['trace'];

        $this->service = $this->getMockBuilder(Elfin\Service::class)
                            ->setConstructorArgs([$config, $trace])
                            ->setMethods(['createDriver'])
                            ->getMock();

        $gimliConfig = $config['applications.elfin.gimli'];
        $this->gimli = $this->getMockBuilder(Elfin\Impl\Gimli::class)
                            ->setConstructorArgs([$gimliConfig])
                            ->setMethods(['makeRequestAndValidateHeader'])
                            ->getMock();

        $bitlyConfig = $config['applications.elfin.bitly'];
        $this->bitly = $this->getMockBuilder(Elfin\Impl\Bitly::class)
                            ->setConstructorArgs([$bitlyConfig])
                            ->setMethods(['makeRequestAndValidateHeader'])
                            ->getMock();

        $this->testUrl = 'https://www.duckduckgo.com';
    }

    public function testShorten()
    {
        //
        // Tests when first of the service succeeds
        //

        $this->service->expects($this->once())
                      ->method('createDriver')
                      ->with('gimli')
                      ->willReturn($this->gimli);

        $this->gimli->expects($this->once())
                    ->method('makeRequestAndValidateHeader')
                    ->willReturn(
                        [
                            'id' => 'something',
                            'url' => $this->testUrl,
                            'hash' => 'http://dwarf.razorpay.in/xyz',
                            'comment' => null,
                            'clicks' => 2,
                            'created_at' => time(),
                        ]
                    );

        $shortUrl = $this->service->shorten($this->testUrl);

        $this->assertEquals('http://dwarf.razorpay.in/xyz', $shortUrl);
    }

    public function testShortenFallback()
    {
        //
        // Tests when fisrt of the service fails and second returns the short url.
        //

        $this->service->expects($this->exactly(2))
                      ->method('createDriver')
                       ->withConsecutive(
                            ['gimli'],
                            ['bitly']
                        )
                       ->will(
                            $this->onConsecutiveCalls($this->gimli, $this->bitly)
                        );

        /**
         * Using instance of \Exception to cover for cases: when an unknown type
         * error gets thrown (e.g. Operation timed out etc).
         *
         * From code validations (of status code and response) we throw
         * \RZP\Exception\RuntimeException. This gets covered in other tests.
         */
        $exception = new \Exception('Some random timeout error..');

        $this->gimli->expects($this->once())
                    ->method('makeRequestAndValidateHeader')
                    ->will($this->throwException($exception));

        $this->bitly->expects($this->once())
                    ->method('makeRequestAndValidateHeader')
                    ->willReturn(
                        [
                            'status_code' => 200,
                            'data'        => [
                                'url' => 'https://bitly.dev/xyz',
                            ],
                        ]
                    );

        $shortUrl = $this->service->shorten($this->testUrl);

        $this->assertEquals('https://bitly.dev/xyz', $shortUrl);
    }

    public function testShortenFailWithSingleService()
    {
        //
        // Tests when fisrt of the service fails and second returns the short url.
        //
        $this->expectException('\RZP\Exception\RuntimeException');
        $this->expectExceptionMessage('url not valid.');

        $this->service->setServices(['bitly']);

        $this->service->expects($this->once())
                      ->method('createDriver')
                       ->with('bitly')
                       ->willReturn($this->bitly);

        $this->bitly->expects($this->once())
                    ->method('makeRequestAndValidateHeader')
                    ->willReturn(
                        [
                            'status_code' => 400,
                            'status_txt'  => 'url not valid.',
                        ]
                    );

        $shortUrl = $this->service->shorten($this->testUrl, [], true);

        $this->assertEquals('https://bitly.dev/xyz', $shortUrl);
    }

    public function testShortenFailAll()
    {
        //
        // Tests when all implementations fail
        //
        //
        $this->expectException('\RZP\Exception\RuntimeException');
        $this->expectExceptionMessage('Unexpected response code received from Gimli/Bitly service.');

        $this->service->expects($this->exactly(2))
                      ->method('createDriver')
                       ->withConsecutive(
                            ['gimli'],
                            ['bitly']
                        )
                       ->will(
                            $this->onConsecutiveCalls($this->gimli, $this->bitly)
                        );

        $exception = new Exception\RuntimeException(
            'Unexpected response code received from Gimli/Bitly service.',
            [
                'status_code' => 500
            ]
        );

        $this->gimli->expects($this->once())
                            ->method('makeRequestAndValidateHeader')
                            ->will($this->throwException($exception));

        $this->bitly->expects($this->once())
                            ->method('makeRequestAndValidateHeader')
                            ->will($this->throwException($exception));

        $shortUrl = $this->service->shorten($this->testUrl, [], true);
    }

    public function testShortenFailAllSilent()
    {
        //
        // Same as above, but returns original url and stays silent
        //

        $this->service->expects($this->exactly(2))
                      ->method('createDriver')
                       ->withConsecutive(
                            ['gimli'],
                            ['bitly']
                        )
                       ->will(
                            $this->onConsecutiveCalls($this->gimli, $this->bitly)
                        );

        $exception = new Exception\RuntimeException(
            'Unexpected response code received from Gimli/Bitly service.',
            [
                'status_code' => 500
            ]
        );

        $this->gimli->expects($this->once())
                            ->method('makeRequestAndValidateHeader')
                            ->will($this->throwException($exception));

        $this->bitly->expects($this->once())
                            ->method('makeRequestAndValidateHeader')
                            ->will($this->throwException($exception));

        $shortUrl = $this->service->shorten($this->testUrl);

        $this->assertEquals($this->testUrl, $shortUrl);
    }
}
