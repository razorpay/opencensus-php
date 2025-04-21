<?php

namespace Tests\Unit\app\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Http\Headers;
use \App\Http\Middleware\Cors;

class CorsTest extends BaseTestCase
{

    public function setUp(): void
    {
        parent::setUp();
    }

    public function createApplication()
    {
        $testEnvironment = 'testing';

        putenv("APP_ENV=$testEnvironment");

        $app = require __DIR__ . '/../../../../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Test that CORS middleware applies the correct headers
     * for a valid OPTIONS preflight request with a whitelisted origin and route.
     */
    public function test_cors_options_request()
    {
        $origin = 'https://accounts.np.razorpay.in';
        $request = Request::create(
            '/user/exists',
            'OPTIONS',
            [],
            [],
            [],
            ['HTTP_ORIGIN' => $origin]
        );

        // Inject this request into Laravel's container so Request::server() works
        $this->app->instance('request', $request);

        $middleware = new Cors();
        $response = $middleware->handle($request, fn() => response('', 200));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($origin, $response->headers->get('Access-Control-Allow-Origin'));

        $allowHeaders = $response->headers->get('Access-Control-Allow-Headers');
        $exposeHeaders = $response->headers->get('Access-Control-Expose-Headers');
        $allowMethods = $response->headers->get('Access-Control-Allow-Methods');
        $allowCredentials = $response->headers->get('Access-Control-Allow-Credentials');

        // verify allow headers
        $this->assertStringContainsString(Headers::CSRF_TOKEN_V2, $allowHeaders);
        $this->assertStringContainsString(Headers::CSRF_TOKEN, $allowHeaders);

        // verify expose headers
        $this->assertStringContainsString(Headers::CSRF_TOKEN_V2, $exposeHeaders);
        $this->assertStringContainsString(Headers::CSRF_TOKEN, $exposeHeaders);

        // verify allow methods
        $this->assertStringContainsString('POST, GET, OPTIONS, PATCH, PUT, DELETE', $allowMethods);

        // verify allow credentials
        $this->assertEquals('true', $allowCredentials);
    }

    public function test_cors_stage_env()
    {
        $this->app['env'] = 'stage';

        $request = Request::create('/any/path', 'OPTIONS', [], [], [], [
            'HTTP_ORIGIN' => 'https://random.site'
        ]);

        $this->app->instance('request', $request);

        $response = (new Cors())->handle($request, fn () => response('', 200));

        $this->assertEquals('https://random.site', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_cors_invalid_origin()
    {
        $this->app['env'] = 'example';

        $request = Request::create('/user/session', 'OPTIONS', [], [], [], [
            'HTTP_ORIGIN' => 'https://unauthorized.com'
        ]);

        $this->app->instance('request', $request);

        $response = (new Cors())->handle($request, fn () => response('', 200));

        // No Access-Control-Allow-Origin header should be present
        $this->assertNull($response->headers->get('Access-Control-Allow-Origin'));
    }

}
