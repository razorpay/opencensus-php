<?php

namespace Tests\Functional;

trait CustomAssertions
{
    public function assertExceptionClass($e, $class)
    {
        $actual = get_class($e);

        if ($class !== $actual)
        {
            throw $e;
        }

        $this->assertInstanceOf($class, $e);
    }

    public function assertArraySelectiveEquals(array $expected, array $actual)
    {
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

                $this->assertEquals($value, $actual[$key]);
            }
        }
    }

    public function assertErrorDataEquals(array $expected, array $actual)
    {
        $this->assertArrayHasKey('code', $actual);

        $this->assertEquals($expected['code'], $actual['code']);

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