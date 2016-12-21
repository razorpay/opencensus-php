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

        //
        // Mocks implementations provider for testing
        //

        $this->provider = $this->createMock(UrlShortener\ImplProvider::class);

        $config             = $app['config'];
        $UrlShortenerConfig = $config['applications.url_shortener'];
        $trace              = $app['trace'];

        $this->service = new UrlShortener\Service($config, $trace, $this->provider);

        $this->gimli   = new UrlShortener\Impl\Mock\Gimli($UrlShortenerConfig['gimli']);
        $this->bitly   = new UrlShortener\Impl\Mock\Bitly($UrlShortenerConfig['bitly']);

        //
        // Followings are test stubs for testing the service logic by emulating
        // requried behaviour.
        //

        $this->gimliTestMock   = $this->createMock(UrlShortener\Impl\Gimli::class);
        $this->bitlyTestMock   = $this->createMock(UrlShortener\Impl\Bitly::class);
    }

    public function testShorten()
    {
        //
        // Tests when first of the service succeeds
        //

        $this->provider->expects($this->once())
                       ->method('get')
                       ->with($this->equalTo('gimli'))
                       ->willReturn($this->gimli);

        $url = 'https://www.duckduckgo.com';

        $shortUrl = $this->service->shorten($url);

        $this->assertContains('http://dwarf.razorpay.dev/', $shortUrl);
    }

    public function testShortenFallback()
    {
        //
        // Tests when fisrt of the service fails and second returns the short url.
        //

        $this->provider->expects($this->exactly(2))
                       ->method('get')
                       ->withConsecutive(
                            [$this->equalTo('gimli')],
                            [$this->equalTo('bitly')]
                        )
                       ->will(
                            $this->onConsecutiveCalls($this->gimliTestMock, $this->bitly)
                        );

        $exception = new Exception\RuntimeException(
            'Unexpected response code received from Gimli service.',
            [
                'status_code' => 500
            ]
        );

        $this->gimliTestMock->expects($this->once())
                            ->method('shorten')
                            ->will($this->throwException($exception));

        $url = 'https://www.duckduckgo.com';

        $shortUrl = $this->service->shorten($url);

        $this->assertContains('http://bitly.dev/', $shortUrl);
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

        $this->provider->expects($this->once())
                       ->method('get')
                       ->with($this->equalTo('gimli'))
                       ->willReturn($this->gimliTestMock);

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

        $this->gimliTestMock->expects($this->once())
                            ->method('shorten')
                            ->will($this->throwException($exception));

        $url = 'https://www.duckduckgo.com';

        $shortUrl = $this->service->shorten($url);
    }

    public function testShortenFailSilent()
    {
        //
        // Same as above, but returns original url and stays silent
        //

        $this->provider->expects($this->once())
                       ->method('get')
                       ->with($this->equalTo('gimli'))
                       ->willReturn($this->gimliTestMock);

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

        $this->gimliTestMock->expects($this->once())
                            ->method('shorten')
                            ->will($this->throwException($exception));

        $url = 'https://www.duckduckgo.com';

        $shortUrl = $this->service->shorten($url, false);

        $this->assertEquals($url, $shortUrl);
    }

    /**
     * @expectedException        \RZP\Exception\RuntimeException
     * @expectedExceptionMessage Failed to get short url.
     */
    public function testShortenFailAll()
    {
        //
        // Tests when all implementations fail
        //

        $this->provider->expects($this->exactly(2))
                       ->method('get')
                       ->withConsecutive(
                            [$this->equalTo('gimli')],
                            [$this->equalTo('bitly')]
                        )
                       ->will(
                            $this->onConsecutiveCalls($this->gimliTestMock, $this->bitlyTestMock)
                        );

        $exception = new Exception\RuntimeException(
            'Unexpected response code received from Gimli/Bitly service.',
            [
                'status_code' => 500
            ]
        );

        $this->gimliTestMock->expects($this->once())
                            ->method('shorten')
                            ->will($this->throwException($exception));

        $this->bitlyTestMock->expects($this->once())
                            ->method('shorten')
                            ->will($this->throwException($exception));

        $url = 'https://www.duckduckgo.com';

        $shortUrl = $this->service->shorten($url);
    }

    public function testShortenFailAllSilent()
    {
        //
        // Same as above, but returns original url and stays silent
        //

        $this->provider->expects($this->exactly(2))
                       ->method('get')
                       ->withConsecutive(
                            [$this->equalTo('gimli')],
                            [$this->equalTo('bitly')]
                        )
                       ->will(
                            $this->onConsecutiveCalls($this->gimliTestMock, $this->bitlyTestMock)
                        );

        $exception = new Exception\RuntimeException(
            'Unexpected response code received from Gimli/Bitly service.',
            [
                'status_code' => 500
            ]
        );

        $this->gimliTestMock->expects($this->once())
                            ->method('shorten')
                            ->will($this->throwException($exception));

        $this->bitlyTestMock->expects($this->once())
                            ->method('shorten')
                            ->will($this->throwException($exception));

        $url = 'https://www.duckduckgo.com';

        $shortUrl = $this->service->shorten($url, false);

        $this->assertEquals($url, $shortUrl);
    }
}
