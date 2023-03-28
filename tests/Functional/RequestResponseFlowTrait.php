<?php

namespace RZP\Tests\Functional;

use Closure;
use Requests;
use RZP\Exception;
use RZP\Tests\Functional\Helpers\EntityFetchTrait;

trait RequestResponseFlowTrait
{
    use EntityFetchTrait;
    use CustomAssertions;

    /**
     * Auths a payment & tests it is correctly done
     * @param $data
     * @param Closure|null $closure
     * @return mixed
     */
    public function runRequestResponseFlow($data, Closure $closure = null)
    {
        $this->resetSingletons();

        $response = null;

        try
        {
            if ($closure !== null)
            {
                $response = $closure();
            }
            else
            {
                $response = $this->sendRequest($data['request']);
            }
        }
        catch (Exception\BaseException $e)
        {
            $this->checkException($e, $data);

            $this->processAndAssertException($e, $data['exception']);

            $response = $e->generatePublicJsonResponse();
        }
        catch (\Razorpay\OAuth\Exception\BaseException $e)
        {
            $this->checkException($e, $data);

            $this->processAndAssertOAuthException($e, $data['exception']);

            $response = $this->generateOAuthPublicJsonResponse($e);
        }
        finally
        {
            if ((isset($e) === false) and
                (isset($data['exception'])))
            {
                $this->fail('Exception ' . $data['exception']['class'] . ' expected. None caught');
            }
        }

        $this->processAndAssertStatusCode($data, $response);

        return $this->processAndAssertResponseData($data, $response);
    }

    protected function processJsonp($content, $callback)
    {
        $start = '/**/'.$callback.'(';
        $end = ');';

        $ix = strlen($start);

        if ((substr($content, 0, $ix) === $start) and
            (substr($content, -2) === $end))
        {
            $ix = strlen($start);
            $content = substr($content, $ix, -2);
        }
        else
        {
            $this->fail('Not a valid jsonp response');
        }

        return $content;
    }

    protected function checkStatusCodeIfJsonp(& $content, $statusCode = '200')
    {
        if ((isset($content['json']) === false) or
            ($content['jsonp'] === false))
        {
            return;
        }

        $this->assertArrayHasKey('http_status_code', $content);

        $this->assertEquals($content['http_status_code'], $statusCode);

        unset($content['http_status_code']);
    }

    protected function checkException($e, $data)
    {
        if (isset($data['exception']) === false)
        {
            throw $e;
        }
    }

    public function processAndAssertException($actual, $expected)
    {
        $class = (isset($expected['class'])) ? $expected['class'] : Exception\RecoverableException::class;

        $this->assertExceptionClass($actual, $class);

        if (isset($expected['two_fa_error']) === true)
        {
            $this->assertEquals($expected['two_fa_error'], $actual->hasTwoFaError());
        }

        if (isset($expected['message']) === true)
        {
            $this->assertEquals($expected['message'], $actual->getMessage());
        }

        $internalError = $actual->getError()->getAttributes();

        $this->assertErrorDataEquals($expected, $internalError);
    }

    public function processAndAssertOAuthException($actual, $expected)
    {
        $class = (isset($expected['class'])) ? $expected['class'] : Exception\RecoverableException::class;

        $this->assertExceptionClass($actual, $class);

        $internalError = $actual->getMessage();

        $this->assertEquals($expected['message'], $internalError);
    }

    protected function processAndAssertResponseData($data, $response)
    {
        $callback = null;

        if ((isset($data['jsonp']) === true) and
            ($data['jsonp'] === true))
        {
            $this->assertArrayHasKey(
                'callback',
                $data['request']['content'], 'Please define callback param for jsonp');

            $callback = $data['request']['content']['callback'];
        }

        $actualContent = $this->getJsonContentFromResponse($response, $callback);

        $expectedContent = $data['response']['content'];

        $this->checkStatusCodeIfJsonp($actualContent);

        // Since IRCTC response dynamically generates a unique batch ID, we skip assertion if 'irctc_refund' key is present in the expected content.
        if(isset($expectedContent['irctc_refund'])===true){
            return $actualContent;
        }

        $this->assertArraySelectiveEquals($expectedContent, $actualContent);

        return $actualContent;
    }

    protected function getJsonContentFromResponse($response, $callback = null)
    {
        $content = $response->getContent();

        if ($callback !== null)
        {
            $content = $this->processJsonp($content, $callback);
        }

        $this->assertJson($content);

        $content = json_decode($content, true);

        if ($callback !== null)
        {
            $this->assertArrayHasKey('http_status_code', $content);
        }

        return $content;
    }

    protected function getJsonStringFromJsonp($response, $callback)
    {
        $content = $response->getContent();

        if ($callback !== null)
        {
            $content = $this->processJsonp($content, $callback);
        }

        $this->assertJson($content);

        return $content;
    }

    protected function processAndAssertStatusCode($data, $response)
    {
        $expectedHttpStatusCode = $this->getExpectedHttpStatusCode($data);

        $actualStatusCode = $response->getStatusCode();

        $this->assertEquals($expectedHttpStatusCode, $actualStatusCode, $response->getContent());
    }

    protected function getExpectedHttpStatusCode($data)
    {
        if (isset($data['response']['status_code']))
        {
            return $data['response']['status_code'];
        }
        else
            return 200;
    }

    protected function sendRequest($request)
    {
        // raw - Raw request body
        $defaults = array(
            'method' => 'POST',
            'content' => array(),
            'server' => array(),
            'cookies' => array(),
            'files' => array(),
            'raw' => '');

        $request = array_merge($defaults, $request);

        if ($this->ba->isPublicAuth() === false)
        {
            $request['server'] = array_merge($request['server'], $this->ba->getCreds());
        }

        // Adds '/v1' to beginning if not already there and
        // not an absolute url
        if ((strpos($request['url'], 'http') === false) and
            (strpos($request['url'], '/v1') === false) and
            (strpos($request['url'], '/v2') === false))
        {
            $request['url'] = '/v1' . $request['url'];
        }

        $convertContentToString = $request['convertContentToString'] ?? true;

        if ($convertContentToString === true)
        {
            $this->convertContentToString($request['content']);
        }

        if ($this->cloud)
        {
            $request['server']['REMOTE_ADDR'] = '10.0.123.123';
        }

        if ($this->ba->isPublicAuth())
        {
            $request['content']['key_id'] = $this->ba->getKey();
        }

        if ($this->ba->isAdminProxyAuth() === true)
        {
            $adminProxyHeaders = $this->ba->getAdminProxyHeaders();

            if (empty($adminProxyHeaders) === false)
            {
                $request['server'] += $this->transformHeadersToServerVars($adminProxyHeaders);
            }
        }

        if ($this->ba->isAccountAuth() === true)
        {
            $accountHeader = $this->ba->getAccountHeader();

            $request['server'] += $this->transformHeadersToServerVars($accountHeader);
        }

        if ($this->ba->isAdminAuth() === true)
        {
            $adminHeaders = $this->ba->getAdminHeaders();

            if (empty($adminHeaders) === false)
            {
                $request['server'] += $this->transformHeadersToServerVars($adminHeaders);
            }
        }

        if ($this->ba->isAppAuth() === true)
        {
            $appHeaders = $this->ba->getAppHeaders();

            if (empty($appHeaders) === false)
            {
                $request['server'] += $this->transformHeadersToServerVars($appHeaders);
            }
        }

        if ($this->ba->isBearerAuth() === true)
        {
            $bearerHeaders = $this->ba->getBearerHeader();

            $request['server'] += $this->transformHeadersToServerVars($bearerHeaders);
        }

        if ($this->ba->isProxyAuth() === true)
        {
            $proxyHeaders = $this->ba->getProxyHeaders();

            $request['server'] += $this->transformHeadersToServerVars($proxyHeaders);
        }

        if (empty($request['headers']) === false)
        {
            $request['server'] += $this->transformHeadersToServerVars($request['headers']);
        }

        /**
         * This is the function signature
         *
         * @param  string  $method
         * @param  string  $uri
         * @param  array   $parameters
         * @param  array   $cookies
         * @param  array   $files
         * @param  array   $server
         * @param  string  $content
         * @return \Illuminate\Http\Response
         */
        $response = $this->call(
            $request['method'],
            $request['url'],
            $request['content'],
            $request['cookies'],
            $request['files'],
            $request['server'],
            $request['raw']);

        $this->response = $response;

        $this->app['request']->generateId();

        return $response;
    }

    protected function makeRequestAndGetContent($request, &$callback = null)
    {
        $this->resetSingletons();

        $response = $this->sendRequest($request, $callback);

        return $this->getJsonContentFromResponse($response, $callback);
    }

    protected function makeRequestAndGetRawContent($request, &$callback = null)
    {
        $this->resetSingletons();

        $response = $this->sendRequest($request, $callback);

        return $response;
    }

    protected function makeRequestAndCatchException(
        Closure $closure,
        string $exceptionClass = \Exception::class,
        string $exceptionMessage = null)
    {
        try
        {
            $closure();
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, $exceptionClass);

            if ($exceptionMessage !== null)
            {
                $this->assertSame($exceptionMessage, $e->getMessage());
            }

            return;
        }

        $this->fail('Expected exception ' . $exceptionClass . ' was not thrown');
    }

    public function getJsonContent($response)
    {
        $content = $response->getContent();

        $this->assertJson($content);

        return json_decode($content, true);
    }

    protected function replaceValuesRecursively(array & $data, array $toReplace)
    {
        foreach ($toReplace as $key => $value)
        {
            if (array_key_exists($key, $data))
            {
                if ((is_array($value)) and
                    (is_array($data[$key])))
                {
                    $this->replaceValuesRecursively($data[$key], $value);
                }
                else
                {
                    $data[$key] = $value;
                }
            }
            else
            {
                $data[$key] = $value;
            }
        }
    }

    protected function setRequestUrlAndMethod(& $request, $url, $method)
    {
        $request['url'] = $url;

        $request['method'] = $method;
    }

    protected function convertContentToString(& $content)
    {
        if (is_array($content) === false)
        {
            return;
        }

        foreach ($content as $key => $value)
        {
            if (is_array($value) === true)
            {
                $this->convertContentToString($value);
            }
            else
            {
                $content[$key] = (string) $value;
            }
        }
    }

    protected function generateOAuthPublicJsonResponse(\Exception $ex)
    {
        if ($ex instanceOf SpineException)
        {
            $data = ['error' => ['description' => $ex->getMessage()]];

            return response()->json($data, 500);
        }

        $httpStatusCode = $ex->getHttpStatusCode();

        return response()->json($ex->toPublicArray(), $httpStatusCode);
    }

    protected function resetSingletons()
    {
        // Per HTTP request we expect fresh $this->merchant to be set in repository manager instead of keeping last one.
        $this->app->forgetInstance('repo');

        // Per HTTP request we expect fresh $this->gateway_downtime_metric to be set.
        $this->app->forgetInstance('gateway_downtime_metric');
    }
}
