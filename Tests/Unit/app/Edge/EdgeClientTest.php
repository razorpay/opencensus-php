<?php

namespace Tests\Unit\app\Edge;

use Mockery;
use App\Edge\EdgeClient;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\Request;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

class EdgeClientTest extends BaseTestCase
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

    public function testSuccess()
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([[], [], [], [], [], [], null])
            ->setMethods(['header'])
            ->getMock();

        // Sets returns for metric dimensions
        $requestMock->expects($this->any())->method('header')->willReturn('4b554240-c3ea-42bd-b418-d54c11571c27');

        // Finally set the mocked request object as app instance
        $this->app->instance('request', $requestMock);

        $headers = ['Content-Type' => 'application/json'];
        $responseData = ['result' => 'on'];
        $body = Utils::streamFor(json_encode($responseData));

        $guzzleMock = Mockery::mock(Client::class);
        $guzzleMock->shouldReceive('PATCH')
            ->with(['form_params' => ['jti' => '4b554240-c3ea-42bd-b418-d54c11571c27']])
            ->andReturn(new Response(200, $headers, $body));

        $mock = Mockery::mock(EdgeClient::class, [$guzzleMock])->makePartial();
        $mock->shouldReceive('getRazorxExperimentResult')
            ->andReturn(true);

        $mock->revokeToken();
        $this->addToAssertionCount(1);
    }

    public function testRazorxFalse()
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([[], [], [], [], [], [], null])
            ->setMethods(['header'])
            ->getMock();

        // Sets returns for metric dimensions
        $requestMock->expects($this->any())->method('header')->willReturn('4b554240-c3ea-42bd-b418-d54c11571c27');

        // Finally set the mocked request object as app instance
        $this->app->instance('request', $requestMock);

        $guzzleMock = Mockery::mock(Client::class);

        $mock = Mockery::mock(EdgeClient::class, [$guzzleMock])->makePartial();
        $mock->shouldReceive('getRazorxExperimentResult')
            ->andReturn(false);

        $mock->revokeToken();
        $this->addToAssertionCount(1);
    }

    public function testEdgeException()
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([[], [], [], [], [], [], null])
            ->setMethods(['header'])
            ->getMock();

        // Sets returns for metric dimensions
        $requestMock->expects($this->any())->method('header')->willReturn('4b554240-c3ea-42bd-b418-d54c11571c27');

        // Finally set the mocked request object as app instance
        $this->app->instance('request', $requestMock);

        $guzzleMock = Mockery::mock(Client::class);
        $guzzleMock->shouldReceive('PATCH')
            ->withAnyArgs()
            ->andThrow(new \Exception());

        $mock = Mockery::mock(EdgeClient::class, [$guzzleMock])->makePartial();
        $mock->shouldReceive('getRazorxExperimentResult')
            ->andReturn(true);

        $mock->revokeToken();
        $this->addToAssertionCount(1);
    }

    public function testNoParams()
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([[], [], [], [], [], [], null])
            ->setMethods(['header'])
            ->getMock();

        // Sets returns for metric dimensions
        $requestMock->expects($this->any())->method('header')->willReturn(null);

        // Finally set the mocked request object as app instance
        $this->app->instance('request', $requestMock);

        $guzzleMock = Mockery::mock(Client::class);

        $mock = Mockery::mock(EdgeClient::class, [$guzzleMock])->makePartial();
        $mock->shouldReceive('getRazorxExperimentResult')
            ->andReturn(true);

        $mock->revokeToken();
        $this->addToAssertionCount(1);
    }

    public function testSuccessUserId()
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([[], [], [], [], [], [], null])
            ->setMethods(['header'])
            ->getMock();

        // Sets returns for metric dimensions
        $requestMock->expects($this->any())->method('header')->willReturn(null);

        // Finally set the mocked request object as app instance
        $this->app->instance('request', $requestMock);

        $headers = ['Content-Type' => 'application/json'];
        $responseData = ['result' => 'on'];
        $body = Utils::streamFor(json_encode($responseData));

        $guzzleMock = Mockery::mock(Client::class);
        $guzzleMock->shouldReceive('PATCH')
            ->with(['form_params' => ['user_ids' => ['12345678'], 'exclude_jti' => '4b554240-c3ea-42bd-b418-d54c11571c27']])
            ->andReturn(new Response(200, $headers, $body));

        $mock = Mockery::mock(EdgeClient::class, [$guzzleMock])->makePartial();
        $mock->shouldReceive('getRazorxExperimentResult')
            ->andReturn(true);

        $mock->revokeToken(['12345678'], '4b554240-c3ea-42bd-b418-d54c11571c27');
        $this->addToAssertionCount(1);
    }

}
