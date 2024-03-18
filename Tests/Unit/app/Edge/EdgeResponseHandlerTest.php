<?php

namespace Tests\Unit\app\Edge;

use App\Edge\Middleware\EdgeResponseHandler;
use App\Edge\ApiResponseForwarder;
use App\Edge\SessionMismatchRecorder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;

class EdgeResponseHandlerTest extends BaseTestCase
{
    private EdgeResponseHandler $edgeResponseHandler;

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

        return $app;

    }

    public function testMiddlewareHasFunctionHandle()
    {
        $edgeMiddleware = new EdgeResponseHandler();

        $this->assertTrue(method_exists($edgeMiddleware, 'handle'));
    }

    public function testMiddlewareHandleReturnsResponse()
    {
        $edgeMiddleware = new EdgeResponseHandler();

        $request = new Request();

        $response = $edgeMiddleware->handle($request, function ($request) {
            return response('test');
        });

        $this->assertEquals('test', $response->getContent());
    }


    public function testMiddlewareExpectedMethodsAreCalled()
    {

        $edgeMiddleware = new EdgeResponseHandler();

        $request = new Request();

        $request->headers->set(SessionMismatchRecorder::HEADER_KEY_EDGE_VERIFIED_USER_ID, 'user_001');
        $request->headers->set(SessionMismatchRecorder::HEADER_KEY_EDGE_VERIFIED_MERCHANT_ID, 'merchant_001');

        $edgeMismatchRecorderMock = $this->getMockBuilder(SessionMismatchRecorder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setEdgeVerifiedData', 'recordMismatches'])
            ->getMock();

        $this->app->singleton('edgeMismatchRecorder', function ($app) use ($edgeMismatchRecorderMock) {
            return $edgeMismatchRecorderMock;
        });

        $edgeResponseForwarderMock = $this->getMockBuilder(ApiResponseForwarder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCookies'])
            ->getMock();

        $this->app->singleton('edgeResponseForwarder', function ($app) use ($edgeResponseForwarderMock) {
            return $edgeResponseForwarderMock;
        });

        $edgeMismatchRecorderMock->expects($this->once())
            ->method('setEdgeVerifiedData')
            ->with($request);

        $edgeMismatchRecorderMock->expects($this->once())
            ->method('recordMismatches')
            ->with($request, "login");

        $edgeResponseForwarderMock->expects($this->once())
            ->method('getCookies')
            ->willReturn([]);

        $edgeMiddleware->handle($request, function ($request) {
            return response() ;
        });


    }

    public function testMiddlewareCookiesAreSetWhenReturned()
    {
        $edgeMiddleware = new EdgeResponseHandler();

        $request = new Request();

        $edgeResponseForwarderMock = $this->getMockBuilder(ApiResponseForwarder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCookies'])
            ->getMock();

        $this->app->singleton('edgeResponseForwarder', function ($app) use ($edgeResponseForwarderMock) {
            return $edgeResponseForwarderMock;
        });

        $edgeResponseForwarderMock->expects($this->once())
            ->method('getCookies')
            ->willReturn([cookie('rzp_access_token', 'solid_access_token')]);

        $response = $edgeMiddleware->handle($request, function ($request) {
            return response("") ;
        });

        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);

        $this->assertEquals('rzp_access_token', $response->headers->getCookies()[0]->getName());

        $this->assertEquals('solid_access_token', $response->headers->getCookies()[0]->getValue());

    }




}
