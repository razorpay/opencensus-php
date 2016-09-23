<?php

namespace RZP\Tests\Functional;

use Validator;

trait CustomAssertions
{
    public function assertExceptionClass($e, $class)
    {// sd(get_class($e), $class);
        if (($e instanceof $class) === false)
        {
            throw $e;
        }

        $this->assertInstanceOf($class, $e);
    }

    public function assertArraySelectiveEquals(array $expected, array $actual)
    {//sd($expected, $actual);
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

            $gatewayErrorDesc = \RZP\Gateway\Hdfc\ErrorCode::$errorMessages[$actual['gateway_error_code']];

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
}