<?php

namespace Tests\Functional;

use EE\Exception\BaseException;
use Requests;

trait RequestResponseFlowTrait
{
    /**
     * Auths a payment & tests it is corrrectly done
     */
    public function runRequestResponseFlow($data)
    {
        $response = null;

        try
        {
            $response = $this->makeRequest($data['request']);
            //s($response->getContent());
        }
        catch (BaseException $e)
        {
            $this->checkException($e, $data);

            $this->processAndAssertException($e, $data['exception']);

            $response = $e->generatePublicJsonResponse();
        }
        finally
        {//s($response->getContent());
            if ((isset($e) === false) and
                (isset($data['exception'])))
            {
                $this->fail('Exception ' . $data['exception']['class'] . ' expected. None caught');
            }
        }

        $this->processAndAssertStatusCode($data, $response);

        return $this->processAndAssertResponseData($data, $response);
    }

    protected function processJsonIfJsonp($data, & $content)
    {
        if ((isset($data['jsonp']) === false) or
            ($data['jsonp'] === false))
        {
            return;
        }

        $this->assertArrayHasKey('callback', $data['request']['content'], 'Please define callback param for jsonp');

        $callback = $data['request']['content']['callback'];

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

    protected function  checkStatusCodeIfJsonp(& $content, $statusCode = '200')
    {
        if ((isset($data['json']) === false) or
            ($data['jsonp'] === false))
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
    {//sd($actual->getTraceAsString());
        $class = (isset($expected['class'])) ? $expected['class'] : 'EE\Exceptions\RecoverableException';

        $this->assertExceptionClass($actual, $class);

        $internalError = $actual->getError()->getAttributes();

        $this->assertErrorDataEquals($expected, $internalError);
    }

    protected function processAndAssertResponseData($data, $response)
    {
        $actualContent = $this->getContentFromResponse($data, $response);

        $expectedContent = $data['response']['content'];
//s($actualContent);
//s($actualContent, $expectedContent);
        $this->checkStatusCodeIfJsonp($actualContent);

        $this->assertArraySelectiveEquals($expectedContent, $actualContent);

        return $actualContent;
    }

    protected function getContentFromResponse($data, $response)
    {
        $content = $response->getContent();

        $this->processJsonIfJsonp($data, $content);

        $this->assertJson($content);

        return json_decode($content, true);
    }

    protected function processAndAssertStatusCode($data, $response)
    {
        $expectedHttpStatusCode = $this->getExpectedHttpStatusCode($data);

        $actualStatusCode = $response->getStatusCode();

        $this->assertEquals($expectedHttpStatusCode, $actualStatusCode);
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

    protected function makeRequest($request)
    {
        $server = $this->ba->getCreds();

        // Adds '/v1' to beginning if not already there and
        // not an absolute url
        if ((strpos($request['url'], 'http') === false) and
            (strpos($request['url'], '/v1') === false))
        {
            $request['url'] = '/v1' . $request['url'];
        }

        if (isset($request['content']) === false)
        {
            $request['content'] = array();
        }
        else
        {
            $this->convertContentToString($request['content']);
        }

        if (isset($request['server']) === false)
        {
            $request['server'] = $server;
        }

        if (isset($request['files']) === false)
        {
            $request['files'] = array();
        }

        if ($this->cloud)
        {
            $request['server']['REMOTE_ADDR'] = '10.0.123.123';
        }

        $response = $this->call(
            $request['method'],
            $request['url'],
            $request['content'],
            $request['files'],
            $request['server']);

        return $response;
    }

    protected function makeRequestAndGetContent($request)
    {
        $response = $this->makeRequest($request);

        return $this->getJsonContent($response);
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
}
