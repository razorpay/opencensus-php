<?php

namespace RZP\Tests\Unit\Models\Merchant\Methods;

use Mockery;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Tests\TestCase;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RZP\Models\Merchant\Methods\Entity as MethodsEntity;
use RZP\Models\Merchant\Methods\PaymentMethodsService;

class PaymentMethodsServiceTest extends TestCase
{
    /**
     * @var \Mockery\MockInterface
     */
    protected $traceMock;

    /**
     * @var \Mockery\MockInterface
     */
    protected $configMock;

    /**
     * @var \Mockery\MockInterface
     */
    protected $appMock;

    /**
     * @var PaymentMethodsService|\Mockery\MockInterface
     */
    protected $servicePartialMock;

    /**
     * @var \Mockery\MockInterface
     */
    protected $splitzServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->traceMock = Mockery::mock('Razorpay\Trace\Logger')->shouldIgnoreMissing();
        $this->configMock = Mockery::mock('Illuminate\Config\Repository');
        $this->splitzServiceMock = Mockery::mock('RZP\Services\SplitzService');

        $this->appMock = Mockery::mock('Illuminate\Foundation\Application')->shouldIgnoreMissing();
        $this->appMock->shouldReceive('make')->with('trace')->andReturn($this->traceMock);
        $this->appMock->shouldReceive('offsetGet')->with('trace')->andReturn($this->traceMock);
        $this->appMock->shouldReceive('offsetGet')->with('config')->andReturn($this->configMock);
        $this->appMock->shouldReceive('offsetGet')->with('request')->andReturn(Mockery::mock('Illuminate\Http\Request'));
        $this->appMock->shouldReceive('offsetGet')->with('basicauth')->andReturn(Mockery::mock('RZP\Http\BasicAuth\BasicAuth'));
        $this->appMock->shouldReceive('offsetGet')->with('splitzService')->andReturn($this->splitzServiceMock);


        $this->servicePartialMock = Mockery::mock(PaymentMethodsService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $reflection = new \ReflectionClass(PaymentMethodsService::class);

        $appProperty = $reflection->getProperty('app');
        $appProperty->setAccessible(true);
        $appProperty->setValue($this->servicePartialMock, $this->appMock);

        $traceProperty = $reflection->getProperty('trace');
        $traceProperty->setAccessible(true);
        $traceProperty->setValue($this->servicePartialMock, $this->traceMock);

        $requestProperty = $reflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $requestProperty->setValue($this->servicePartialMock, $this->appMock['request']);

        $authProperty = $reflection->getProperty('auth');
        $authProperty->setAccessible(true);
        $authProperty->setValue($this->servicePartialMock, $this->appMock['basicauth']);
    }

    public function testGetMethodsServiceUrlLiveMode()
    {
        $expectedUrl = 'http://payment-methods.live.service';
        $this->appMock->shouldReceive('offsetGet')->with('rzp.mode')->andReturn(Mode::LIVE);
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.live.url')->andReturn($expectedUrl);

        $url = $this->servicePartialMock->getMethodsServiceUrl();

        $this->assertEquals($expectedUrl, $url);
    }

    public function testProxyToMethodsServiceSuccess()
    {
        $path = '/test';
        $expectedBody = '{"success": true}';
        $mockHttpResponse = $this->createMockHttpResponse($expectedBody, 200);

        $this->appMock->shouldReceive('offsetGet')->with('rzp.mode')->andReturn(Mode::LIVE);
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.live.url')->andReturn('http://test.url');
        // Config for auth needed by getAuthHeader called within ProxyToMethodsService
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.live.user')->andReturn('user');
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.live.password')->andReturn('pass');
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.timeout')->andReturn(0.1);

        $this->servicePartialMock->shouldAllowMockingProtectedMethods()
            ->shouldReceive('makeRequest')
            ->once()
            ->with('http://test.url' . $path, Mockery::type('array'), [], 'GET', ['timeout' => 0.1, 'connect_timeout' => 0.1])
            ->andReturn($mockHttpResponse);

        $response = $this->servicePartialMock->ProxyToMethodsService([], 'GET', $path);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals($expectedBody, $response->body);
    }

    public function testProxyToMethodsServiceNetworkError()
    {
        $path = '/test';
        $this->expectException(Exception\IntegrationException::class);
        $this->expectExceptionCode(ErrorCode::SERVER_ERROR);
        $this->expectExceptionMessage('Network error communicating with Payment Methods Service: Test network error');

        $this->appMock->shouldReceive('offsetGet')->with('rzp.mode')->andReturn(Mode::LIVE);
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.live.url')->andReturn('http://test.url');
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.live.user')->andReturn('user');
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.live.password')->andReturn('pass');
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.timeout')->andReturn(0.1);

        $this->servicePartialMock->shouldAllowMockingProtectedMethods()
            ->shouldReceive('makeRequest')
            ->once()
            ->andThrow(new \Exception('Test network error'));

        $this->servicePartialMock->ProxyToMethodsService([], 'GET', $path);
    }

    public function testProxyToMethodsServiceClientError()
    {
        $path = '/test';
        $errorBody = '{"error": "client_error"}';
        $mockHttpResponse = $this->createMockHttpResponse($errorBody, 400);

        $this->expectException(Exception\IntegrationException::class);
        $this->expectExceptionCode(ErrorCode::SERVER_ERROR_INVALID_RESPONSE); // Corrected based on SUT
        $this->expectExceptionMessage('Payment Methods Service returned an error.');

        $this->appMock->shouldReceive('offsetGet')->with('rzp.mode')->andReturn(Mode::LIVE);
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.live.url')->andReturn('http://test.url');
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.live.user')->andReturn('user');
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.live.password')->andReturn('pass');
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.timeout')->andReturn(0.1);

        $this->servicePartialMock->shouldAllowMockingProtectedMethods()
            ->shouldReceive('makeRequest')
            ->once()
            ->andReturn($mockHttpResponse);

        $this->servicePartialMock->ProxyToMethodsService([], 'GET', $path);
    }

    public function testProxyToMethodsServiceServerError()
    {
        $path = '/test';
        $errorBody = '{"error": "server_error"}';
        $mockHttpResponse = $this->createMockHttpResponse($errorBody, 503);

        $this->expectException(Exception\IntegrationException::class);
        $this->expectExceptionCode(ErrorCode::SERVER_ERROR_INVALID_RESPONSE);
        $this->expectExceptionMessage('Payment Methods Service returned an error.');

        $this->appMock->shouldReceive('offsetGet')->with('rzp.mode')->andReturn(Mode::LIVE);
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.live.url')->andReturn('http://test.url');
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.live.user')->andReturn('user');
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.live.password')->andReturn('pass');
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.timeout')->andReturn(0.1);

        $this->servicePartialMock->shouldAllowMockingProtectedMethods()
            ->shouldReceive('makeRequest')
            ->once()
            ->andReturn($mockHttpResponse);

        $this->servicePartialMock->ProxyToMethodsService([], 'GET', $path);
    }


    public function testFetchMethodsFromServiceSuccess()
    {
        $merchantId = 'testMerchant123';
        $path = "/merchants/{$merchantId}/methods";
        $responseData = ['card' => true, 'netbanking' => true, 'upi' => false, 'merchant_id' => $merchantId];
        $responseBody = json_encode($responseData);
        $mockHttpResponse = $this->createMockHttpResponse($responseBody, 200);

        // Mock isMethodServiceReadEnabled directly on the partial mock for this test flow
        $this->servicePartialMock->shouldReceive('isMethodServiceReadEnabled')->andReturn(true);

        // Config for auth and URL needed by ProxyToMethodsService called internally
        $this->appMock->shouldReceive('offsetGet')->with('rzp.mode')->andReturn(Mode::LIVE);
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.live.url')->andReturn('http://test.url');
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.live.user')->andReturn('user');
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.live.password')->andReturn('pass');
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.timeout')->andReturn(0.1);

        // ProxyToMethodsService will be called internally, we mock its underlying makeRequest
        $this->servicePartialMock->shouldAllowMockingProtectedMethods()
            ->shouldReceive('makeRequest')
            ->once()
            ->with('http://test.url' . $path, Mockery::type('array'), [], 'GET', ['timeout' => 0.1, 'connect_timeout' => 0.1])
            ->andReturn($mockHttpResponse);

        $fetchedEntity = $this->servicePartialMock->fetchMethodsFromService($merchantId, $path);

        $this->assertInstanceOf(MethodsEntity::class, $fetchedEntity);
        $this->assertEquals($merchantId, $fetchedEntity->getMerchantId());
        $this->assertTrue($fetchedEntity->isNetbankingEnabled());
        $this->assertFalse($fetchedEntity->isUpiEnabled());
    }

    public function testFetchMethodsFromServiceInvalidJson()
    {
        $merchantId = 'testMerchant123';
        $path = "/merchants/{$merchantId}/methods";
        $invalidJson = '{"card": true, '; // Invalid JSON
        $mockHttpResponse = $this->createMockHttpResponse($invalidJson, 200);

        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Failed to decode JSON response from Payment Methods Service: Syntax error/');

        $this->servicePartialMock->shouldReceive('isMethodServiceReadEnabled')->andReturn(true);
        $this->appMock->shouldReceive('offsetGet')->with('rzp.mode')->andReturn(Mode::LIVE);
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.live.url')->andReturn('http://test.url');
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.live.user')->andReturn('user');
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.live.password')->andReturn('pass');
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.timeout')->andReturn(0.1);

        $this->servicePartialMock->shouldAllowMockingProtectedMethods()
            ->shouldReceive('makeRequest')
            ->once()
            ->andReturn($mockHttpResponse);

        $this->servicePartialMock->fetchMethodsFromService($merchantId, $path);
    }

    public function testFetchMethodsFromServiceProxyFailure()
    {
        $merchantId = 'testMerchant123';
        $path = "/merchants/{$merchantId}/methods";

        $this->expectException(Exception\IntegrationException::class);
        $this->expectExceptionCode(ErrorCode::SERVER_ERROR);

        $this->servicePartialMock->shouldReceive('isMethodServiceReadEnabled')->andReturn(true);
        $this->appMock->shouldReceive('offsetGet')->with('rzp.mode')->andReturn(Mode::LIVE);
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.live.url')->andReturn('http://test.url');
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.live.user')->andReturn('user');
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.live.password')->andReturn('pass');
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.timeout')->andReturn(0.1);

        $this->servicePartialMock->shouldAllowMockingProtectedMethods()
            ->shouldReceive('makeRequest')
            ->once()
            ->andThrow(new \Exception('Network failure'));

        $this->servicePartialMock->fetchMethodsFromService($merchantId, $path);
    }

    public function testAreMethodsDifferentIdentical()
    {
        $entity1 = new MethodsEntity(['card' => true, 'upi' => false]);
        $entity2 = new MethodsEntity(['card' => true, 'upi' => false]);

        $this->traceMock->shouldReceive('info')
            ->with(TraceCode::PAYMENT_METHODS_SERVICE_COMPARE_RESULT, ['are_different' => false])
            ->once();

        $result = $this->servicePartialMock->areMethodsDifferent($entity1, $entity2);

        $this->assertFalse($result);
    }

    public function testAreMethodsDifferentDifferent()
    {
        $entity1 = new MethodsEntity(['card' => true, 'upi' => false, 'netbanking' => 'yes']);
        $entity2 = new MethodsEntity(['card' => true, 'upi' => true,  'netbanking' => 'no']);

        $result = $this->servicePartialMock->areMethodsDifferent($entity1, $entity2);

        $this->assertTrue($result);
    }

    public function testAreMethodsDifferentKeyMissingInEntity2()
    {
        $entity1 = new MethodsEntity(['card' => true, 'upi' => false, MethodsEntity::MOBIKWIK => true]);
        $entity2 = new MethodsEntity(['card' => true, 'upi' => false]);

        $result = $this->servicePartialMock->areMethodsDifferent($entity1, $entity2);
        $this->assertTrue($result);
    }

    public function testAreMethodsDifferentExtraKeyInEntity2IsIgnored()
    {
        $entity1 = new MethodsEntity(['card' => true, 'upi' => false]);
        $entity2 = new MethodsEntity(['card' => true, 'upi' => false, 'extra_key' => 'ignored']);

        $this->traceMock->shouldReceive('info')
            ->with(TraceCode::PAYMENT_METHODS_SERVICE_COMPARE_RESULT, ['are_different' => false])
            ->once();

        $result = $this->servicePartialMock->areMethodsDifferent($entity1, $entity2);
        $this->assertFalse($result);
    }

    public function testAreMethodsDifferentWithEmptyEntity1()
    {
        $entity1 = new MethodsEntity(); // Assumed to be empty
        $entity2 = new MethodsEntity();
        $entity2->setPaytm(true);

        $result = $this->servicePartialMock->areMethodsDifferent($entity1, $entity2);
        $this->assertFalse($result);
    }

    public function testAreMethodsDifferentWithEmptyEntity2()
    {
        $entity1 = new MethodsEntity(['paytm' => true]);
        $entity2 = new MethodsEntity(); // Assumed to be empty

        $result = $this->servicePartialMock->areMethodsDifferent($entity1, $entity2);
        $this->assertTrue($result);
    }

    public function testAreMethodsDifferentDifferentTimestampsExcluded()
    {
        $now = time();
        $entity1 = new MethodsEntity(['card' => true, 'upi' => false, 'created_at' => $now, 'updated_at' => $now]);
        $entity2 = new MethodsEntity(['card' => true, 'upi' => true, 'created_at' => $now - 100, 'updated_at' => $now - 50]);

        $result = $this->servicePartialMock->areMethodsDifferent($entity1, $entity2);
        $this->assertTrue($result);
    }

    public function testIsMethodServiceReadEnabledVariantOn()
    {
        $this->appMock->shouldReceive('offsetGet')->with('rzp.mode')->andReturn(Mode::LIVE);
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.read_experiment')->andReturn('exp_id_123');

        $this->splitzServiceMock->shouldReceive('evaluateRequest')
            ->once()
            ->with(Mockery::on(function ($arg) {
                return !empty($arg['id']) && $arg['experiment_id'] === 'exp_id_123';
            }))
            ->andReturn(['response' => ['variant' => ['name' => 'variant_on']]]);

        $this->assertTrue($this->servicePartialMock->isMethodServiceReadEnabled());
    }

    public function testIsMethodServiceReadEnabledVariantOff()
    {
        $this->appMock->shouldReceive('offsetGet')->with('rzp.mode')->andReturn(Mode::LIVE);
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.read_experiment')->andReturn('exp_id_123');

        $this->splitzServiceMock->shouldReceive('evaluateRequest')
            ->once()
            ->andReturn(['response' => ['variant' => ['name' => 'variant_off']]]);

        $this->assertFalse($this->servicePartialMock->isMethodServiceReadEnabled());
    }

    public function testIsMethodServiceReadEnabledSplitzException()
    {
        $this->appMock->shouldReceive('offsetGet')->with('rzp.mode')->andReturn(Mode::LIVE);
        $this->configMock->shouldReceive('get')->with('applications.payment_methods_service.read_experiment')->andReturn('exp_id_123');

        $exception = new \Exception('Splitz error');
        $this->splitzServiceMock->shouldReceive('evaluateRequest')
            ->once()
            ->andThrow($exception);

        $this->assertFalse($this->servicePartialMock->isMethodServiceReadEnabled());
    }


    /**
     * Helper to create a mock HTTP response object.
     */
    protected function createMockHttpResponse(string $body, int $statusCode): ResponseInterface
    {
        $streamMock = Mockery::mock(StreamInterface::class);
        $streamMock->shouldReceive('__toString')->andReturn($body);

        $responseMock = Mockery::mock(ResponseInterface::class);
        $responseMock->shouldReceive('getBody')->andReturn($streamMock);
        $responseMock->shouldReceive('getStatusCode')->andReturn($statusCode);
        // Add ->status_code property dynamically for direct access in service
        $responseMock->status_code = $statusCode;
        // Add ->body property dynamically for direct access in service
        $responseMock->body = $body;

        return $responseMock;
    }
}
