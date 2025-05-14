<?php

namespace Tests\Unit\app\Http\Middleware;

use App\Http\Middleware\InternalAuth;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;

class InternalAuthTest extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function createApplication()
    {
        $testEnvironment = 'testing';

        putenv("APP_ENV=$testEnvironment");

        $app = require __DIR__ . '/../../../../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    public function testApiAuthSuccess()
    {
        // Mock request
        $request = new Request();

        // Set server variables
        $_SERVER['PHP_AUTH_USER'] = 'rzp_api';
        $_SERVER['PHP_AUTH_PW'] = 'RANDOM_DASH_PASSWORD';

        // Override configuration
        config(['api.auth_user' => 'rzp_api']);
        config(['api.auth_pass' => 'RANDOM_DASH_PASSWORD']);
        config(['idp.auth_user' => 'rzp_idp']);
        config(['idp.auth_pass' => 'RANDOM_DASH_PASSWORD_IDP']);

        // Create middleware
        $middleware = new InternalAuth();

        // Create next closure
        $next = function ($request) {
            return 'next was called';
        };

        // Execute middleware
        $result = $middleware->handle($request, $next);

        // Assert next was called
        $this->assertEquals('next was called', $result);
    }

    public function testIdpAuthSuccess()
    {
        // Mock request
        $request = new Request();

        // Set server variables
        $_SERVER['PHP_AUTH_USER'] = 'rzp_idp';
        $_SERVER['PHP_AUTH_PW'] = 'RANDOM_DASH_PASSWORD_IDP';

        // Override configuration
        config(['api.auth_user' => 'api_user']);
        config(['api.auth_pass' => 'api_pass']);
        config(['idp.auth_user' => 'rzp_idp']);
        config(['idp.auth_pass' => 'RANDOM_DASH_PASSWORD_IDP']);

        // Create middleware
        $middleware = new InternalAuth();

        // Create next closure
        $next = function ($request) {
            return 'next was called';
        };

        // Execute middleware
        $result = $middleware->handle($request, $next);

        // Assert next was called
        $this->assertEquals('next was called', $result);
    }

    public function testAuthFailure()
    {
        // Mock request
        $request = new Request();

        // Set server variables
        $_SERVER['PHP_AUTH_USER'] = 'wrong_user';
        $_SERVER['PHP_AUTH_PW'] = 'wrong_pass';

        // Override configuration directly
        config(['api.auth_user' => 'api_user']);
        config(['api.auth_pass' => 'api_pass']);
        config(['idp.auth_user' => 'idp_user']);
        config(['idp.auth_pass' => 'idp_pass']);

        // Create middleware
        $middleware = new InternalAuth();

        // Create next closure
        $next = function ($request) {
            return 'next was called';
        };

        // Execute middleware
        $response = $middleware->handle($request, $next);

        // Assert response is JSON with error
        $this->assertIsObject($response);
        $this->assertJson($response->getContent());
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertEquals(['Unauthorised'], $responseData['errors']);
    }
}
