<?php

namespace Tests\Unit\app\Edge;

use App\Edge\Middleware\EdgeRequestHandler;
use App\Edge\ValidateEdgeToken;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;

class EdgeRequestHandlerTest extends BaseTestCase
{
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

    public function testRevokedTrue()
    {
        $edgeMiddleware = new EdgeRequestHandler();

        $request = new Request();

        $edgeTokenValidatorMock = $this->getMockBuilder(ValidateEdgeToken::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['verifyRevoked'])
            ->getMock();

        $this->app->singleton('edgeTokenValidator', function ($app) use ($edgeTokenValidatorMock) {
            return $edgeTokenValidatorMock;
        });

        $edgeTokenValidatorMock->expects($this->once())
            ->method('verifyRevoked')
            ->willReturn(true);

        $edgeMiddleware->handle($request, function ($request) {
            return response('', 401);
        });
    }

    public function testRevokedFalse()
    {

        $edgeMiddleware = new EdgeRequestHandler();

        $request = new Request();

        $edgeTokenValidatorMock = $this->getMockBuilder(ValidateEdgeToken::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['verifyRevoked'])
            ->getMock();

        $this->app->singleton('edgeTokenValidator', function ($app) use ($edgeTokenValidatorMock) {
            return $edgeTokenValidatorMock;
        });

        $edgeTokenValidatorMock->expects($this->once())
            ->method('verifyRevoked')
            ->willReturn(false);

        $edgeMiddleware->handle($request, function ($request) {
            return response();
        });
    }
}
