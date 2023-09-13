<?php

//TestCase.php
namespace CircuitBreaker;

use Mockery;
use Cache;
use GuzzleHttp\Psr7\Utils;
use App\Constants\Constants;
use GuzzleHttp\Psr7\Response;
use Razorpay\Api\Errors\Error;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as IlluminateTestCase;
use GuzzleHttp\Client;
use App\Admin\ApiRequestAny;

class TestCase extends IlluminateTestCase
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

        $app = require __DIR__ . '/../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function sendMockRequestToApiGuzzleResponse($methodName, $path, $response): array
    {
        $guzzleMock = Mockery::mock(Client::class);
        $guzzleMock
            ->shouldReceive($methodName)
            ->withAnyArgs()
            ->andReturn($response);

        $request = new ApiRequestAny([Constants::HTTP_CLIENT => $guzzleMock]);

        return $request->processInput([])->send($path, $methodName);
    }

    protected function sendMockRequestToApiGuzzleException($methodName, $path, $response): array
    {
        $guzzleMock = Mockery::mock(Client::class);
        $guzzleMock
            ->shouldReceive($methodName)
            ->withAnyArgs()
            ->andThrow($response);

        $request = new ApiRequestAny([Constants::HTTP_CLIENT => $guzzleMock]);

        return $request->processInput([])->send($path, $methodName);
    }

    protected function createRequestHeaders($route, $path): array
    {
        return [
            'Content-Type'     => 'application/json',
            'Api-Route-Name'   => $route,
            'Api-Path-Pattern' => $path
        ];
    }

    protected function createResponseSuccess($headers)
    {
        $responseData = [
            'data' => 'test'
        ];

        $body = Utils::streamFor(json_encode($responseData));

        return new Response(200, $headers, $body);
    }

    protected function createResponseFailure(): Error
    {
        return new Error('Server error response', 500, 500);
    }

    /*
     * We receive high 5xx % above threshold and circuit breaks at a point
     * */
    public function testApiCircuitBreakerFlowAboveThreshold()
    {
        $route = 'merchant_features_fetch';

        $path = 'merchants/me/features';

        $methodName = 'get';

        $headers = $this->createRequestHeaders($route, $path);

        $response = $this->createResponseSuccess($headers);

        $exception = $this->createResponseFailure();

        $this->app['config']->set('app.is_api_circuit_breaker_enabled', true);

        /*
         * window = [success, sleep(1), failure * 19 with sleep(1), success, sleep(1), success, sleep(3), success]
         * */
        list($error, $data) = $this->sendMockRequestToApiGuzzleResponse($methodName, $path, $response);

        $this->assertEmpty($error);

        $circuitBreakerData = $this->cache->get('api_route_details');

        $this->assertArrayHasKey($headers['Api-Route-Name'], $circuitBreakerData[$methodName]);

        sleep(1);

        // taking divisible by 10 as success request
        for ($count = 2; $count <= 20; $count++)
        {
            if ($count % 10 === 0)
            {
                $this->sendMockRequestToApiGuzzleResponse($methodName, $path, $response);
            }
            else
            {
                $this->sendMockRequestToApiGuzzleException($methodName, $path, $exception);
            }

            sleep(1);
        }

        try
        {
            $this->sendMockRequestToApiGuzzleResponse($methodName, $path, $response);
        }
        catch(\Exception $e)
        {
            $msg = $e->getMessage();
            $this->assertSame('Service Unavailable : 503', $msg);
        }

        sleep(1);

        try
        {
            $this->sendMockRequestToApiGuzzleResponse($methodName, $path, $response);
        }
        catch(\Exception $e)
        {
            $msg = $e->getMessage();
            $this->assertSame('Service Unavailable : 503', $msg);
        }

        sleep(3);

        list($error, $data) = $this->sendMockRequestToApiGuzzleResponse($methodName, $path, $response);

        $this->assertEmpty($error);
    }

    /*
     * We receive high 5xx % but below threshold so circuit does not break
     * */
    public function testApiCircuitBreakerFlowBelowThreshold()
    {
        $route = 'merchant_features_fetch';

        $path = 'merchants/me/features';

        $methodName = 'get';

        $headers = $this->createRequestHeaders($route, $path);

        $response = $this->createResponseSuccess($headers);

        $exception = $this->createResponseFailure();

        $this->app['config']->set('app.is_api_circuit_breaker_enabled', true);

        /*
         * window = [success, sleep(2),  sleep(5), failure * 5 with   sleep(2), success]
         * */
        list($error, $data) = $this->sendMockRequestToApiGuzzleResponse($methodName, $path, $response);

        $this->assertEmpty($error);

        $circuitBreakerData = $this->cache->get('api_route_details');

        $this->assertArrayHasKey($headers['Api-Route-Name'], $circuitBreakerData[$methodName]);

        sleep(2);

        for ($count = 1; $count <= 5; $count++)
        {
            $this->sendMockRequestToApiGuzzleException($methodName, $path, $exception);

            sleep(2);
        }

        list($error, $data) = $this->sendMockRequestToApiGuzzleResponse($methodName, $path, $response);

        $this->assertEmpty($error);
    }

    /*
     * we ignore a case when we are not sure that it is a valid route or not,
     * we consider a route valid if it has occurred at-least one 200 in
     * last seven days else we keep getting 5xx on such routes.
     * */
    public function testApiCircuitBreakerFlowWithoutValidRoute()
    {
        $path = 'merchants/me/features';

        $methodName = 'get';

        $exception = $this->createResponseFailure();

        $this->app['config']->set('app.is_api_circuit_breaker_enabled', true);

        /*
         * window = [ failure * 20 with   sleep(1)]
         * */
        for ($count = 1; $count <= 20; $count++)
        {
            list($error, $data) = $this->sendMockRequestToApiGuzzleException($methodName, $path, $exception);

            $this->assertNotEmpty($error);

            $this->assertNotEquals('Service Unavailable : 503',$error[0]);

            sleep(1);
        }

        $circuitBreakerData = $this->cache->get('api_route_details');

        $this->assertNull($circuitBreakerData);
    }

    /*
 * We receive high 5xx % above threshold and circuit breaks at a point
 * */
    public function testApiCircuitBreakerFlowMultipleRoutesAboveThreshold()
    {
        // for route 1
        $route1 = 'merchant_features_fetch';

        $path1 = 'merchants/me/features';

        $methodName1 = 'get';

        $headers1 = $this->createRequestHeaders($route1, $path1);

        $response1 = $this->createResponseSuccess($headers1);

        // for route 2
        $route2 = 'user_opt_in_whatsapp';

        $path2 = 'users/whatsapp/opt_in';

        $methodName2 = 'post';

        $headers2 = $this->createRequestHeaders($route2, $path2);

        $response2 = $this->createResponseSuccess($headers2);

        $exception = $this->createResponseFailure();

        $this->app['config']->set('app.is_api_circuit_breaker_enabled', true);
        /*
         * window = [success1, success2 ,sleep(2), (failure1, failure2) * 19 with sleep(1), success1, sleep(1), success1, sleep(3), success1, sleep(1), success2]
         * */
        list($error, $data) = $this->sendMockRequestToApiGuzzleResponse($methodName1, $path1, $response1);

        $this->assertEmpty($error);

        list($error, $data) = $this->sendMockRequestToApiGuzzleResponse($methodName2, $path2, $response2);

        $this->assertEmpty($error);

        $circuitBreakerData = $this->cache->get('api_route_details');

        $this->assertArrayHasKey($headers1['Api-Route-Name'], $circuitBreakerData[$methodName1]);

        $this->assertArrayHasKey($headers2['Api-Route-Name'], $circuitBreakerData[$methodName2]);

        sleep(2);

        // taking divisible by 10 as success request
        for ($count = 2; $count <= 20; $count++)
        {
            if ($count % 10 === 0)
            {
                $this->sendMockRequestToApiGuzzleResponse($methodName1, $path1, $response1);
            }
            else
            {
                $this->sendMockRequestToApiGuzzleException($methodName1, $path1, $exception);
            }

            //different route with only failure
            $this->sendMockRequestToApiGuzzleException($methodName2, $path2, $exception);

            sleep(1);
        }

        try
        {
            $this->sendMockRequestToApiGuzzleResponse($methodName1, $path1, $response1);
        }
        catch(\Exception $e)
        {
            $msg = $e->getMessage();
            $this->assertSame('Service Unavailable : 503', $msg);
        }

        try
        {
            $this->sendMockRequestToApiGuzzleResponse($methodName2, $path2, $response2);
        }
        catch(\Exception $e)
        {
            $msg = $e->getMessage();
            $this->assertSame('Service Unavailable : 503', $msg);
        }

        sleep(1);

        try
        {
            $this->sendMockRequestToApiGuzzleResponse($methodName1, $path1, $response1);
        }
        catch(\Exception $e)
        {
            $msg = $e->getMessage();
            $this->assertSame('Service Unavailable : 503', $msg);
        }

        sleep(3);

        list($error, $data) = $this->sendMockRequestToApiGuzzleResponse($methodName1, $path1, $response1);

        $this->assertEmpty($error);

        sleep(1);

        list($error, $data) = $this->sendMockRequestToApiGuzzleResponse($methodName2, $path2, $response2);

        $this->assertEmpty($error);
    }


    /*
 * We receive high 5xx % above threshold and circuit breaks at a point
 * */
    public function testApiCircuitBreakerFlowMultipleRoutesAboveThresholdKeyValueInString()
    {
        // for route 1
        $route1 = 'merchant_features_fetch';

        $path1 = 'merchants/me/features';

        $methodName1 = 'get';

        $headers1 = $this->createRequestHeaders($route1, $path1);

        $response1 = $this->createResponseSuccess($headers1);

        // for route 2
        $route2 = 'user_opt_in_whatsapp';

        $path2 = 'users/whatsapp/opt_in';

        $methodName2 = 'post';

        $headers2 = $this->createRequestHeaders($route2, $path2);

        $response2 = $this->createResponseSuccess($headers2);

        $exception = $this->createResponseFailure();

        $this->app['config']->set('app.is_api_circuit_breaker_enabled', 'true');
        /*
         * window = [success1, success2 ,sleep(2), (failure1, failure2) * 19 with sleep(1), success1, sleep(1), success1, sleep(3), success1, sleep(1), success2]
         * */
        list($error, $data) = $this->sendMockRequestToApiGuzzleResponse($methodName1, $path1, $response1);

        $this->assertEmpty($error);

        list($error, $data) = $this->sendMockRequestToApiGuzzleResponse($methodName2, $path2, $response2);

        $this->assertEmpty($error);

        $circuitBreakerData = $this->cache->get('api_route_details');

        $this->assertArrayHasKey($headers1['Api-Route-Name'], $circuitBreakerData[$methodName1]);

        $this->assertArrayHasKey($headers2['Api-Route-Name'], $circuitBreakerData[$methodName2]);

        sleep(2);

        // taking divisible by 10 as success request
        for ($count = 2; $count <= 20; $count++)
        {
            if ($count % 10 === 0)
            {
                $this->sendMockRequestToApiGuzzleResponse($methodName1, $path1, $response1);
            }
            else
            {
                $this->sendMockRequestToApiGuzzleException($methodName1, $path1, $exception);
            }

            //different route with only failure
            $this->sendMockRequestToApiGuzzleException($methodName2, $path2, $exception);

            sleep(1);
        }

        try
        {
            $this->sendMockRequestToApiGuzzleResponse($methodName1, $path1, $response1);
        }
        catch(\Exception $e)
        {
            $msg = $e->getMessage();
            $this->assertSame('Service Unavailable : 503', $msg);
        }

        try
        {
            $this->sendMockRequestToApiGuzzleResponse($methodName2, $path2, $response2);
        }
        catch(\Exception $e)
        {
            $msg = $e->getMessage();
            $this->assertSame('Service Unavailable : 503', $msg);
        }

        sleep(1);

        try
        {
            $this->sendMockRequestToApiGuzzleResponse($methodName1, $path1, $response1);
        }
        catch(\Exception $e)
        {
            $msg = $e->getMessage();
            $this->assertSame('Service Unavailable : 503', $msg);
        }

        sleep(3);

        list($error, $data) = $this->sendMockRequestToApiGuzzleResponse($methodName1, $path1, $response1);

        $this->assertEmpty($error);

        sleep(1);

        list($error, $data) = $this->sendMockRequestToApiGuzzleResponse($methodName2, $path2, $response2);

        $this->assertEmpty($error);
    }

    /*
    * We receive high 5xx % above threshold and circuit does not break at a point because of
    * is_api_circuit_breaker_enabled is not enabled in config
    * */
    public function testApiCircuitBreakerFlowWhenIsApiCircuitBreakerEnabledIsFalse()
    {
        $route = 'merchant_features_fetch';

        $path = 'merchants/me/features';

        $methodName = 'get';

        $headers = $this->createRequestHeaders($route, $path);

        $response = $this->createResponseSuccess($headers);

        $exception = $this->createResponseFailure();

        $this->app['config']->set('app.is_api_circuit_breaker_enabled', false);

        /*
        * window = [success, sleep(1), failure * 19 with sleep(1), success, sleep(1), success]
        * */
        list($error, $data) = $this->sendMockRequestToApiGuzzleResponse($methodName, $path, $response);

        $this->assertEmpty($error);

        $circuitBreakerData = $this->cache->get('api_route_details');

        $this->assertArrayHasKey($headers['Api-Route-Name'], $circuitBreakerData[$methodName]);

        sleep(1);

        // taking divisible by 10 as success request
        for ($count = 2; $count <= 20; $count++)
        {
            if ($count % 10 === 0)
            {
                $this->sendMockRequestToApiGuzzleResponse($methodName, $path, $response);
            }
            else
            {
                $this->sendMockRequestToApiGuzzleException($methodName, $path, $exception);
            }
            sleep(1);
        }

        list($error, $data) = $this->sendMockRequestToApiGuzzleResponse($methodName, $path, $response);

        $this->assertEmpty($error);

        sleep(1);

        list($error, $data) = $this->sendMockRequestToApiGuzzleResponse($methodName, $path, $response);

        $this->assertEmpty($error);
    }

    /*
    * We receive high 5xx % above threshold and circuit does not break at a point because of
    * is_api_circuit_breaker_enabled is not defined in config
    * */
    public function testApiCircuitBreakerFlowWhenIsApiCircuitBreakerEnabledIsNotDefined()
    {
        $route = 'merchant_features_fetch';

        $path = 'merchants/me/features';

        $methodName = 'get';

        $headers = $this->createRequestHeaders($route, $path);

        $response = $this->createResponseSuccess($headers);

        $exception = $this->createResponseFailure();

        /*
        * window = [success, sleep(1), failure * 19 with sleep(1), success, sleep(1), success]
        * */
        list($error, $data) = $this->sendMockRequestToApiGuzzleResponse($methodName, $path, $response);

        $this->assertEmpty($error);

        $circuitBreakerData = $this->cache->get('api_route_details');

        $this->assertArrayHasKey($headers['Api-Route-Name'], $circuitBreakerData[$methodName]);

        sleep(1);

        // taking divisible by 10 as success request
        for ($count = 2; $count <= 20; $count++)
        {
            if ($count % 10 === 0)
            {
                $this->sendMockRequestToApiGuzzleResponse($methodName, $path, $response);
            }
            else
            {
                $this->sendMockRequestToApiGuzzleException($methodName, $path, $exception);
            }
            sleep(1);
        }

        list($error, $data) = $this->sendMockRequestToApiGuzzleResponse($methodName, $path, $response);

        $this->assertEmpty($error);

        sleep(1);

        list($error, $data) = $this->sendMockRequestToApiGuzzleResponse($methodName, $path, $response);

        $this->assertEmpty($error);
    }

}
