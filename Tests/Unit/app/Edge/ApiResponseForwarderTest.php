<?php

namespace Tests\Unit\app\Edge;

use App\Edge\ApiResponseForwarder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

class ApiResponseForwarderTest extends BaseTestCase
{

    private ApiResponseForwarder $edgeResponseForwarder;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function createApplication()
    {
        $testEnvironment = 'testing';

        putenv("APP_ENV=$testEnvironment");

        $app = require __DIR__ . '/../../../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // added to test facade construction, not used anywhere else
        $this->edgeResponseForwarder = $app['edgeResponseForwarder'];

        return $app;
    }

    public function testResponseHeaders()
    {
        foreach (ApiResponseForwarder::RESPONSE_HEADERS as $apiHeaders => $headers) {
            $this->assertTrue(is_string($apiHeaders));
            $this->assertNotEmpty($headers);
        }
    }

    public function testSetHeadersWhitelistedRouteEmptyHeaders()
    {
        $edgeResponseForwarder = new ApiResponseForwarder();
        $edgeResponseForwarder->setHeaders('users/login', 'post', []);
        $this->assertEmpty($edgeResponseForwarder->getHeaders());
    }

    public function testSetHeadersWhitelistedRouteAndHeaders()
    {
        $edgeResponseForwarder = new ApiResponseForwarder();
        $edgeResponseForwarder->setHeaders('users/login', 'post', [
            'set-cookie' => ['value'],
            'other' => ['other_value']
        ]);
        $this->assertSame([], $edgeResponseForwarder->getHeaders());
    }

    public function testSetHeadersNonWhitelistedRouteAndWhitelistedHeaders()
    {
        $edgeResponseForwarder = new ApiResponseForwarder();
        $edgeResponseForwarder->setHeaders('invalid/path', 'get', [
            'set-cookie' => ['value'],
            'other' => ['other_value']
        ]);
        $this->assertSame([], $edgeResponseForwarder->getHeaders());
    }

    public function testSetHeadersNonWhitelistedRouteAndEmptyHeaders()
    {
        $edgeResponseForwarder = new ApiResponseForwarder();
        $edgeResponseForwarder->setHeaders('invalid/path', 'get', []);
        $this->assertEmpty($edgeResponseForwarder->getHeaders());
    }

    public function testSetHeadersSetsCookiesForCookieHeaders()
    {
        $edgeResponseForwarder = new ApiResponseForwarder();
        $edgeResponseForwarder->setHeaders('users/login', 'post', [
            'set-cookie' => 'rzp_access_token=token_value',
            'Content-Type' => 'application/json'
        ]);
        $cookies = $edgeResponseForwarder->getCookies();
        $this->assertCount(1, $cookies);
        $this->assertEquals('rzp_access_token', $cookies[0]->getName());
        $this->assertEquals('token_value', $cookies[0]->getValue());
        $this->assertEquals(null, $cookies[0]->getDomain());

    }

    public function testSetHeadersDoesntSetsCookiesHeaderValues()
    {
        $edgeResponseForwarder = new ApiResponseForwarder();
        $edgeResponseForwarder->setHeaders('users/login', 'post', [
            'Content-Type' => 'application/json'
        ]);
        $cookies = $edgeResponseForwarder->getCookies();
        $this->assertCount(0, $cookies);

    }

    public function testSetHeadersSetsCookiesForMultipleCookieHeaders()
    {
        $edgeResponseForwarder = new ApiResponseForwarder();
        $edgeResponseForwarder->setHeaders('users/login', 'post', [
            'set-cookie' => ['rzp_access_token=token_value', 'rzp_refresh_token=refresh_token_value'],
            'Content-Type' => 'application/json'
        ]);
        $cookies = $edgeResponseForwarder->getCookies();
        $this->assertCount(2, $cookies);
        $this->assertEquals('rzp_access_token', $cookies[0]->getName());
        $this->assertEquals('token_value', $cookies[0]->getValue());
        $this->assertEquals('rzp_refresh_token', $cookies[1]->getName());
        $this->assertEquals('refresh_token_value', $cookies[1]->getValue());
    }
}
