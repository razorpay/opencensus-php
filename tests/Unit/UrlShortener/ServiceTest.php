<?php

namespace RZP\Tests\Unit\UrlShortener;

use RZP\Tests\TestCase;
use RZP\Services\UrlShortener;
use RZP\Exception;

class ServiceTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $app = $this->createApplication();

        $config            = $app['config'];
        $trace             = $app['trace'];

        $this->service = $this->getMockBuilder(UrlShortener\Service::class)
                            ->setConstructorArgs([$config, $trace])
                            ->setMethods(['createUrlShortenerDriver'])
                            ->getMock();

        $gimliConfig = $config['applications.url_shortener.gimli'];
        $this->gimli = $this->getMockBuilder(UrlShortener\Impl\Gimli::class)
                            ->setConstructorArgs([$gimliConfig])
                            ->setMethods(['makeRequestAndValidateHeader'])
                            ->getMock();

        $bitlyConfig = $config['applications.url_shortener.bitly'];
        $this->bitly = $this->getMockBuilder(UrlShortener\Impl\Bitly::class)
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
                      ->method('createUrlShortenerDriver')
                      ->with('gimli')
                      ->willReturn($this->gimli);

        $this->gimli->expects($this->once())
                    ->method('makeRequestAndValidateHeader')
                    ->willReturn(
                        [
                            'id' => 'something',
                            'url' => $this->testUrl,
                            'hash' => 'http://dwarf.razorpay.dev/xyz',
                            'comment' => null,
                            'clicks' => 2,
                            'created_at' => time(),
                        ]
                    );

        $shortUrl = $this->service->shorten($this->testUrl);

        $this->assertEquals('http://dwarf.razorpay.dev/xyz', $shortUrl);
    }

    public function testShortenFallback()
    {
        //
        // Tests when fisrt of the service fails and second returns the short url.
        //

        $this->service->expects($this->exactly(2))
                      ->method('createUrlShortenerDriver')
                       ->withConsecutive(
                            ['gimli'],
                            ['bitly']
                        )
                       ->will(
                            $this->onConsecutiveCalls($this->gimli, $this->bitly)
                        );

        $exception = new Exception\RuntimeException(
            'Unexpected response code received from Gimli service.',
            [
                'status_code' => 500
            ]
        );

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

    /**
     * @expectedException        \RZP\Exception\RuntimeException
     * @expectedExceptionMessage Unexpected response code received from Gimli service.
     */
    public function testShortenFail()
    {
        //
        // Tests when first of the service fails, but must not try another service
        // as it's client side error.
        //

        $this->service->expects($this->once())
                       ->method('createUrlShortenerDriver')
                       ->with('gimli')
                       ->willReturn($this->gimli);

        $exception = new Exception\RuntimeException(
            'Unexpected response code received from Gimli service.',
            [
                'status_code' => 400,
                'res_body'    => [
                    'status_code' => 400,
                    'message'     => 'Invalid url.'
                ],
            ]
        );

        $this->gimli->expects($this->once())
                            ->method('makeRequestAndValidateHeader')
                            ->will($this->throwException($exception));

        $shortUrl = $this->service->shorten($this->testUrl);
    }

    public function testShortenFailSilent()
    {
        //
        // Same as above, but returns original url and stays silent
        //

        $this->service->expects($this->once())
                       ->method('createUrlShortenerDriver')
                       ->with('gimli')
                       ->willReturn($this->gimli);

        $exception = new Exception\RuntimeException(
            'Unexpected response code received from Gimli service.',
            [
                'status_code' => 400,
                'res_body'    => [
                    'status_code' => 400,
                    'message'     => 'Invalid url.'
                ],
            ]
        );

        $this->gimli->expects($this->once())
                            ->method('makeRequestAndValidateHeader')
                            ->will($this->throwException($exception));

        $shortUrl = $this->service->shorten($this->testUrl, false);

        $this->assertEquals($this->testUrl, $shortUrl);
    }

    /**
     * @expectedException        \RZP\Exception\RuntimeException
     * @expectedExceptionMessage Unexpected response code received from Gimli/Bitly service.
     */
    public function testShortenFailAll()
    {
        //
        // Tests when all implementations fail
        //

        $this->service->expects($this->exactly(2))
                      ->method('createUrlShortenerDriver')
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
    }

    public function testShortenFailAllSilent()
    {
        //
        // Same as above, but returns original url and stays silent
        //

        $this->service->expects($this->exactly(2))
                      ->method('createUrlShortenerDriver')
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

        $shortUrl = $this->service->shorten($this->testUrl, false);

        $this->assertEquals($this->testUrl, $shortUrl);
    }
}
