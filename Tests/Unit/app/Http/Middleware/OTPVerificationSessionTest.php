<?php

namespace Tests\Unit\app\Http\Middleware;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Session;
use App\Http\Middleware\OTPVerificationSession;

class OTPVerificationSessionTest extends BaseTestCase
{
    protected $cache;

    public function setUp(): void
    {
        parent::setUp();

        $this->app = \App::getFacadeRoot();

        $this->cache = $this->app['cache'];
        // clear the cache for an individual test case
        $this->cache->flush();
    }

    public function createApplication()
    {
        $testEnvironment = 'testing';

        putenv("APP_ENV=$testEnvironment");

        $app = require __DIR__ . '/../../../../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    public function testSetUrlArray()
    {
        foreach (OTPVerificationSession::$setUrls as $prefixedUrl => $data) {
            $this->assertTrue(is_array($data));
            $this->assertArrayHasKey('key', $data);
            $this->assertArrayHasKey('value', $data);
            $this->assertArrayHasKey('http_method', $data);
            $this->assertTrue(in_array($data['http_method'], ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']));
            $this->assertTrue(!Str::startsWith($prefixedUrl, "/"));
        }
    }

    public function testCheckUrlArray()
    {
        foreach (OTPVerificationSession::$checkUrls as $prefixedUrl => $data) {
            $this->assertTrue(is_array($data));
            $this->assertArrayHasKey('http_method', $data);
            $this->assertTrue(in_array($data['http_method'], ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']));
            $this->assertTrue(!Str::startsWith($prefixedUrl, "/"));
        }
    }

    public function testSetRouteNameArray()
    {
        foreach (OTPVerificationSession::$setRouteNames as $name => $data) {
            $this->assertTrue(is_array($data));
            $this->assertArrayHasKey('key', $data);
            $this->assertArrayHasKey('value', $data);
        }
    }

    public function testVerifyOtpSessionIfApplicable()
    {
        $req = Request::create("merchant/api/live/users/2fa", "PATCH");

        Session::shouldReceive('exists')->with(OTPVerificationSession::OTPVerificationSessionKey)->andReturn(true);
        Session::shouldReceive('get')->with(OTPVerificationSession::OTPVerificationSessionKey)->andReturn('1');

        $this->callMethod('verifyOtpSessionIfApplicable', [$req]);


        $req = Request::create("merchant/api/test/users/2fa", "PATCH");

        Session::shouldReceive('exists')->with(OTPVerificationSession::OTPVerificationSessionKey)->andReturn(true);
        Session::shouldReceive('get')->with(OTPVerificationSession::OTPVerificationSessionKey)->andReturn('1');

        $this->callMethod('verifyOtpSessionIfApplicable', [$req]);

        $req = Request::create("merchant/api/live/users/2fa", "POST");
        $this->callMethod('verifyOtpSessionIfApplicable', [$req]);

        $req = Request::create("merchant/api/live/users", "PATCH");
        $this->callMethod('verifyOtpSessionIfApplicable', [$req]);

        $req = Request::create("password", "POST");
        Session::shouldReceive('exists')->with(OTPVerificationSession::OTPVerificationSessionKey)->andReturn(true);
        Session::shouldReceive('get')->with(OTPVerificationSession::OTPVerificationSessionKey)->andReturn('1');
        $this->callMethod('verifyOtpSessionIfApplicable', [$req]);

        $req = Request::create("merchant/api/live/keys/key_123346227", "PUT");
        Session::shouldReceive('exists')->with(OTPVerificationSession::OTPVerificationSessionKey)->andReturn(true);
        Session::shouldReceive('get')->with(OTPVerificationSession::OTPVerificationSessionKey)->andReturn('1');
        $this->callMethod('verifyOtpSessionIfApplicable', [$req]);

        $req = Request::create("merchant/api/live/keys", "POST");
        Session::shouldReceive('exists')->with(OTPVerificationSession::OTPVerificationSessionKey)->andReturn(true);
        Session::shouldReceive('get')->with(OTPVerificationSession::OTPVerificationSessionKey)->andReturn('1');
        $this->callMethod('verifyOtpSessionIfApplicable', [$req]);

        $req = Request::create("merchant/api/live/batches", "POST", ["type" => "linked_account_create"]);
        Session::shouldReceive('exists')->with(OTPVerificationSession::OTPVerificationSessionKey)->andReturn(true);
        Session::shouldReceive('get')->with(OTPVerificationSession::OTPVerificationSessionKey)->andReturn('1');
        $this->callMethod('verifyOtpSessionIfApplicable', [$req]);

        $req = Request::create("merchant/api/live/merchant/activation", "POST", ["type" => "linked_account"]);
        Session::shouldReceive('exists')->with(OTPVerificationSession::OTPVerificationSessionKey)->andReturn(true);
        Session::shouldReceive('get')->with(OTPVerificationSession::OTPVerificationSessionKey)->andReturn('1');
        $this->callMethod('verifyOtpSessionIfApplicable', [$req]);
    }

    public function testShouldCheckUrl()
    {
        // For linked account batch upload
        $req = Request::create("merchant/api/live/batches", "POST", ["type" => "linked_account_create"]);
        [$shouldCheck, $pattern, $routeName] = $this->callMethod('shouldCheckUrlForOtpValidation', [$req]);
        $this->assertTrue($shouldCheck);
        $this->assertEquals("merchant/api/*/batches", $pattern);
        $this->assertEmpty($routeName);

        // For payment transfer batch upload
        $req = Request::create("merchant/api/live/batches", "POST", ["type" => "payment_transfer"]);
        [$shouldCheck, $pattern, $routeName] = $this->callMethod('shouldCheckUrlForOtpValidation', [$req]);
        $this->assertFalse($shouldCheck);
        $this->assertEmpty( $pattern);
        $this->assertEmpty($routeName);

        // For merchant/activation for linked accounts
        $req = Request::create("merchant/api/live/merchant/activation", "POST", ["type" => "linked_account"]);
        [$shouldCheck, $pattern, $routeName] = $this->callMethod('shouldCheckUrlForOtpValidation', [$req]);
        $this->assertTrue($shouldCheck);
        $this->assertEquals("merchant/api/*/merchant/activation", $pattern);
        $this->assertEmpty($routeName);
    }

    public function testSetOtpSessionIfApplicable()
    {
        Session::shouldReceive('put')->once()->with(OTPVerificationSession::OTPVerificationSessionKey, '1');
        Session::shouldReceive('put')->with(OTPVerificationSession::MaxRetryForOtpVerificationSkip, 10);
        $req = Request::create("user/verify_contact", "POST");
        $res = JsonResponse::fromJsonString(json_encode(["success" => true]));
        $this->callMethod('setOtpSessionIfApplicable', [$req, $res]);

        $req = Request::create("user/verify_contact", "PATCH");
        $res = JsonResponse::fromJsonString(json_encode(["success" => true]));
        $this->callMethod('setOtpSessionIfApplicable', [$req, $res]);

        $req = Request::create("user/verify_contact/some/other/route", "POST");
        $res = JsonResponse::fromJsonString(json_encode(["success" => true]));
        $this->callMethod('setOtpSessionIfApplicable', [$req, $res]);

        $req = Request::create("merchant/api/live/batches", "POST");
        $res = JsonResponse::fromJsonString(json_encode(["success" => true]));
        $this->callMethod('setOtpSessionIfApplicable', [$req, $res]);

        $req = Request::create("merchant/api/live/merchant/activation", "POST");
        $res = JsonResponse::fromJsonString(json_encode(["success" => true]));
        $this->callMethod('setOtpSessionIfApplicable', [$req, $res]);
    }

    private function callMethod($method, array $args)
    {
        $ins  = new OTPVerificationSession();
        $class  = new \ReflectionClass(get_class($ins));
        $method = $class->getMethod($method);

        $method->setAccessible(true);

        return $method->invokeArgs($ins, $args);
    }
}
