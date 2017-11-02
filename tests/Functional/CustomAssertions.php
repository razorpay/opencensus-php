<?php

namespace RZP\Tests\Functional;

use Validator;
use RZP\Gateway\Hdfc;

trait CustomAssertions
{
    public function assertExceptionClass($e, $class)
    {
        if (($e instanceof $class) === false)
        {
            echo PHP_EOL . 'Exception of class ' . $class . ' expected but not caught' . PHP_EOL;
            throw $e;
        }

        $this->assertInstanceOf($class, $e);
    }

    public function assertArraySelectiveEquals(array $expected, array $actual)
    {
        if ((isset($actual['entity'])) and (is_string($actual['entity'])))
        {
            $this->validateEntity($actual);
        }

        foreach ($expected as $key => $value)
        {
            if (is_array($value))
            {
                $this->assertArrayHasKey($key, $actual);

                $this->assertArraySelectiveEquals($expected[$key], $actual[$key]);
            }
            else
            {
                $this->assertArrayHasKey($key, $actual);

                $this->assertSame($value, $actual[$key], 'The key is: '.$key);
            }
        }
    }

    public function validateEntity($attributes)
    {
        $entity = $attributes['entity'];
        $class = 'RZP\Tests\Functional\Assertion\Validator\\'.ucfirst($entity);

        if (class_exists($class) === false)
        {
            return;
        }

        $validator = new $class;

        if (isset($attributes['admin']))
        {
            $validator->setStrictFalse();
        }

        $validator->validateInput('entity', $attributes);
    }

    public function assertErrorDataEquals(array $expected, array $actual)
    {
        $this->assertArrayHasKey('internal_error_code', $actual);

        $this->assertEquals($expected['internal_error_code'], $actual['internal_error_code']);

        if (isset($expected['gateway_error_code']))
        {
            $this->assertEquals($expected['gateway_error_code'], $actual['gateway_error_code']);

            $gatewayErrorDesc = $this->getGatewayErrorDescription($actual);

            $this->assertEquals($gatewayErrorDesc, $actual['gateway_error_desc'], 'key: gateway_error_desc');
        }

        if (isset($expected['field']))
        {
            $this->assertEquals($expected['field'], $actual['field']);
        }
    }

    public function assertTestResponse($content, $key = null)
    {
        if ($key === null)
        {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $key = $trace[1]['function'];
        }

        $this->assertArraySelectiveEquals($this->testData[$key], $content);
    }

    public function assertContentTypeForResponse($type, $response)
    {
        $headers = $this->response->headers->all();

        $this->assertArrayHasKey('content-type', $headers);

        $contentType = $headers['content-type'][0];

        $this->assertEquals($type, $contentType);
    }

    public function assertResponseOk($response)
    {
        $this->assertEquals(200, $response->getStatusCode());
    }

    protected function getGatewayErrorDescription(array $actual)
    {
        return Hdfc\ErrorCode::$errorMessages[$actual['gateway_error_code']] ?? $actual['gateway_error_desc'];
    }

    protected function assertItemsCount(array $content, $totalCount, $deletedCount = null)
    {
        $this->assertCount($totalCount, $content['items']);

        if ($deletedCount !== null)
        {
            $deletedLineItems = array_filter($content['items'],
                function($item)
                {
                    return $item['deleted_at'];
                });

            $this->assertCount($deletedCount, $deletedLineItems);
        }
    }
}
