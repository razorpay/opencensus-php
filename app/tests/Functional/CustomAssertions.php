<?php

namespace Tests\Functional;

trait CustomAssertions
{
    public function assertExceptionClass($e, $class)
    {//sd(get_class($e));
        if (($e instanceof $class) === false)
        {
            throw $e;
        }

        $this->assertInstanceOf($class, $e);
    }

    public function assertArraySelectiveEquals(array $expected, array $actual)
    {//sd($expected, $actual);
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

    public function assertErrorDataEquals(array $expected, array $actual)
    {
        $this->assertArrayHasKey('internal_error_code', $actual);

        $this->assertEquals($expected['internal_error_code'], $actual['internal_error_code']);

        if (isset($expected['gateway_error_code']))
        {
            $this->assertEquals($expected['gateway_error_code'], $actual['gateway_error_code']);

            $gatewayErrorDesc = \Gateway\Hdfc\ErrorCode::$errorMessages[$actual['gateway_error_code']];

            $this->assertEquals($gatewayErrorDesc, $actual['gateway_error_desc']);
        }

        if (isset($expected['field']))
        {
            $this->assertEquals($expected['field'], $actual['field']);
        }
    }
}