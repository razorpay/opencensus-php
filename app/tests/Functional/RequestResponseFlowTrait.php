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
        }
        catch (BaseException $e)
        {
            $this->checkException($e, $data);

            $this->processAndAssertException($e, $data['exception']);

            $response = $e->generatePublicJsonResponse();
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
        $this->assertExceptionClass($actual, $expected['class']);

        $internalError = $actual->getErrorArray();

        $this->assertErrorDataEquals($expected, $internalError['error']);
    }

    protected function processAndAssertResponseData($data, $response)
    {
        $content = $response->getContent();

        $this->assertJson($content);

        $actualContent = json_decode($content, true);

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
        return (isset($data['response']['status_code'])) ?: 200;
    }

    protected function makeRequest($request)
    {
        $response = $this->call(
            $request['method'],
            $request['url'],
            $request['content']);

        return $response;
    }

    protected function replaceValuesRecursively(array & $data, array $toReplace)
    {
        foreach ($toReplace as $key => $value)
        {
            if (is_array($value))
            {
                $this->replaceValuesRecursively($data[$key], $value);
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
