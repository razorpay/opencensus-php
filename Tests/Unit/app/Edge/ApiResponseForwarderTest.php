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

    public function testRouteToResponseHeaders()
    {
        foreach (ApiResponseForwarder::ROUTE_TO_RESPONSE_HEADERS as $apiRoute => $headers) {
            $this->assertTrue(is_string($apiRoute));
            $this->assertTrue(is_array($headers));
            $this->assertFalse(isset($headers[0]));
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
        $this->assertSame([
            'set-cookie' => ['value']
        ], $edgeResponseForwarder->getHeaders());
    }

    public function testSetHeadersNonWhitelistedRouteAndWhitelistedHeaders()
    {
        $edgeResponseForwarder = new ApiResponseForwarder();
        $edgeResponseForwarder->setHeaders('invalid/path', 'get', [
            'set-cookie' => ['value'],
            'other' => ['other_value']
        ]);
        $this->assertEmpty($edgeResponseForwarder->getHeaders());
    }

    public function testSetHeadersNonWhitelistedRouteAndEmptyHeaders()
    {
        $edgeResponseForwarder = new ApiResponseForwarder();
        $edgeResponseForwarder->setHeaders('invalid/path', 'get', []);
        $this->assertEmpty($edgeResponseForwarder->getHeaders());
    }
}
