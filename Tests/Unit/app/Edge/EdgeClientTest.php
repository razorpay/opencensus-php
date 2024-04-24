<?php

namespace Tests\Unit\app\Edge;

use App\Providers\GenericUser;
use Illuminate\Support\Collection;
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

    public function getUser()
    {
        $merchantData = [
            'id' => 'rzptestmid1234',
            'role' => 'owner',
            'name' => 'John Doe',
            'banking_role' => 'owner',
            'logo_url' => null
        ];

        $userData =  [
            'id'     => 1,
            'name'   => 'John Doe',
            'email' => 'test@gmail.com',
            'contact_mobile_verified' => true,
            'confirmed' => true,
            'merchants' => new Collection([
                (object) $merchantData
            ])
        ];

        return new GenericUser($userData);
    }

    public function testRevokeTokenSuccess()
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([[], [], [], [], [], [], null])
            ->setMethods(['header'])
            ->getMock();

        $requestMock->expects($this->any())->method('header')->willReturn('4b554240-c3ea-42bd-b418-d54c11571c27');
        $this->app->instance('request', $requestMock);

        $headers = ['Content-Type' => 'application/json'];
        $responseData = [];
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

    public function testRevokeTokenOnRazorxFalse()
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([[], [], [], [], [], [], null])
            ->setMethods(['header'])
            ->getMock();

        $requestMock->expects($this->any())->method('header')->willReturn('4b554240-c3ea-42bd-b418-d54c11571c27');
        $this->app->instance('request', $requestMock);

        $guzzleMock = Mockery::mock(Client::class);

        $mock = Mockery::mock(EdgeClient::class, [$guzzleMock])->makePartial();
        $mock->shouldReceive('getRazorxExperimentResult')
            ->andReturn(false);

        $mock->revokeToken();
        $this->addToAssertionCount(1);
    }

    public function testRevokeTokenOnEdgeException()
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([[], [], [], [], [], [], null])
            ->setMethods(['header'])
            ->getMock();

        $requestMock->expects($this->any())->method('header')->willReturn('4b554240-c3ea-42bd-b418-d54c11571c27');
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

    public function testRevokeTokenWithNoParams()
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([[], [], [], [], [], [], null])
            ->setMethods(['header'])
            ->getMock();

        $requestMock->expects($this->any())->method('header')->willReturn(null);
        $this->app->instance('request', $requestMock);

        $headers = ['Content-Type' => 'application/json'];
        $responseData = [];
        $body = Utils::streamFor(json_encode($responseData));

        $guzzleMock = Mockery::mock(Client::class);
        $guzzleMock->shouldReceive('PATCH')
            ->with(['form_params' => []])
            ->andReturn(new Response(400, $headers, $body));

        $mock = Mockery::mock(EdgeClient::class, [$guzzleMock])->makePartial();
        $mock->shouldReceive('getRazorxExperimentResult')
            ->andReturn(true);

        $mock->revokeToken();
        $this->addToAssertionCount(1);
    }

    public function testRevokeTokenSuccessWithUserIdsAndExcludeJti()
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([[], [], [], [], [], [], null])
            ->setMethods(['header'])
            ->getMock();

        $requestMock->expects($this->any())->method('header')->willReturn(null);
        $this->app->instance('request', $requestMock);

        $headers = ['Content-Type' => 'application/json'];
        $responseData = [];
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

    public function testReissueTokenSuccess()
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([[], [], [], [], [], [], null])
            ->setMethods(['cookie'])
            ->getMock();

        $requestMock->expects($this->any())->method('cookie')->willReturn('qwerty');
        $this->app->instance('request', $requestMock);

        $headers = ['Content-Type' => 'application/json'];
        $responseData = ['token' => 'abcdefgh'];
        $body = Utils::streamFor(json_encode($responseData));

        $guzzleMock = Mockery::mock(Client::class);
        $guzzleMock->shouldReceive('POST')
            ->with(['form_params' => [
                'merchant_id' => 'rzptestmid1234',
                'token' => 'qwerty',
                'role' => 'owner',
                'is_verified' => true
            ]])
            ->andReturn(new Response(200, $headers, $body));

        $mock = Mockery::mock(EdgeClient::class, [$guzzleMock])->makePartial();
        $mock->shouldReceive('getRazorxExperimentResult')
            ->andReturn(true);

        $mock->reissueToken("rzptestmid1234", $this->getUser());
        $this->addToAssertionCount(1);
    }

    public function testReissueTokenOnRazorxFalse()
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([[], [], [], [], [], [], null])
            ->setMethods(['cookie'])
            ->getMock();

        $requestMock->expects($this->any())->method('cookie')->willReturn('qwerty');
        $this->app->instance('request', $requestMock);

        $guzzleMock = Mockery::mock(Client::class);

        $mock = Mockery::mock(EdgeClient::class, [$guzzleMock])->makePartial();
        $mock->shouldReceive('getRazorxExperimentResult')
            ->andReturn(false);

        $mock->reissueToken("rzptestmid1234", $this->getUser());
        $this->addToAssertionCount(1);
    }

    public function testReissueTokenOnEdgeException()
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([[], [], [], [], [], [], null])
            ->setMethods(['cookie'])
            ->getMock();

        $requestMock->expects($this->any())->method('cookie')->willReturn('qwerty');
        $this->app->instance('request', $requestMock);

        $guzzleMock = Mockery::mock(Client::class);
        $guzzleMock->shouldReceive('PATCH')
            ->withAnyArgs()
            ->andThrow(new \Exception());

        $mock = Mockery::mock(EdgeClient::class, [$guzzleMock])->makePartial();
        $mock->shouldReceive('getRazorxExperimentResult')
            ->andReturn(true);

        $mock->reissueToken("rzptestmid1234", $this->getUser());
        $this->addToAssertionCount(1);
    }

    public function testReissueTokenWithNoToken()
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([[], [], [], [], [], [], null])
            ->setMethods(['cookie'])
            ->getMock();

        $this->app->instance('request', $requestMock);

        $guzzleMock = Mockery::mock(Client::class);

        $mock = Mockery::mock(EdgeClient::class, [$guzzleMock])->makePartial();
        $mock->shouldReceive('getRazorxExperimentResult')
            ->andReturn(true);

        $mock->reissueToken("", $this->getUser());
        $this->addToAssertionCount(1);
    }

    public function testReissueTokenFailed()
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([[], [], [], [], [], [], null])
            ->setMethods(['cookie'])
            ->getMock();

        $requestMock->expects($this->any())->method('cookie')->willReturn('qwerty');
        $this->app->instance('request', $requestMock);

        $headers = ['Content-Type' => 'application/json'];
        $responseData = ['message' => 'unknown error'];
        $body = Utils::streamFor(json_encode($responseData));

        $guzzleMock = Mockery::mock(Client::class);
        $guzzleMock->shouldReceive('POST')
            ->with(['form_params' => [
                'merchant_id' => 'rzptestmid1234',
                'token' => 'qwerty',
                'role' => 'owner',
                'is_verified' => true
            ]])
            ->andReturn(new Response(500, $headers, $body));

        $mock = Mockery::mock(EdgeClient::class, [$guzzleMock])->makePartial();
        $mock->shouldReceive('getRazorxExperimentResult')
            ->andReturn(true);
        $mock->shouldReceive('setEdgeCookies')->times(0);

        $mock->reissueToken("rzptestmid1234", $this->getUser());
        $this->addToAssertionCount(1);
    }
}
