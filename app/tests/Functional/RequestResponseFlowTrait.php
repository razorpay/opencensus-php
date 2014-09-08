<?php

namespace Tests\Functional;

use EE\Exception\BaseException;
use Requests;

trait RequestResponseFlowTrait
{
    /**
     * Auths a transaction & tests it is corrrectly done
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

    protected function checkException($e, $data)
    {
        if (isset($data['exception']) === false)
            throw $e;
    }

    public function processAndAssertException($actual, $expected)
    {
        $class = (isset($expected['class'])) ? $expected['class'] : 'EE\Exceptions\RecoverableException';

        $this->assertExceptionClass($actual, $class);

        $internalError = $actual->getError()->getAttributes();

        $this->assertErrorDataEquals($expected, $internalError);
    }

    protected function processAndAssertResponseData($data, $response)
    {
        $content = $response->getContent();

        $this->assertJson($content);

        $actualContent = json_decode($content, true);
//s($actualContent);
        $expectedContent = $data['response']['content'];

        $this->assertArraySelectiveEquals($expectedContent, $actualContent);

        return $actualContent;
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
        $server = $this->auth;

        $request['url'] = '/v1' . $request['url'];

        if (isset($request['content']) === false)
        {
            $request['content'] = array();
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
//s($response->getContent());
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
}
