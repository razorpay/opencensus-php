<?php

namespace RZP\Tests\Unit\Trace;

use ReflectionClass;
use ReflectionMethod;
use RZP\Tests\TestCase;
use RZP\Gateway\Upi\Mindgate\Gateway;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

class TraceUpiDataTest extends TestCase
{
    use ArraySubsetAsserts;

    public function maskUpiDataForTracing()
    {
        $class = new ReflectionClass(Gateway::class);
        $method = $class->getMethod('maskUpiDataForTracing');
        $method->setAccessible(true);

        // $method, $input, $keys, $output
        $cases = [];

        $message = 'emptyKeysNoMasking';
        $input   = ['abc' => 123];
        $output  = $input;
        $cases[$message] = [$method, $input, [], $output];

        $message = 'emptyKeysEmptyInput';
        $input   = [];
        $output  = $input;
        $cases[$message] = [$method, $input, [], $output];

        $message = 'emptyKeysNestedInput';
        $input   = [ 'a' => ['b' => 'c'] ];
        $output  = $input;
        $cases[$message] = [$method, $input, [], $output];

        $message = 'DefaultKeysFlattenShortInputInteger';
        $input   = ['abc' => 123];
        $output  = ['abc' => '123'];
        $cases[$message] = [$method, $input, ['default' => 'abc'], $output];

        $message = 'DefaultKeysFlattenLongInputInteger';
        $input   = ['abc' => 123456789];
        $output  = ['abc' => '*****6789'];
        $cases[$message] = [$method, $input, ['default' => 'abc'], $output];

        $message = 'DefaultKeysFlattenNullInput';
        $input   = ['abc' => null];
        $output  = ['abc' => '[NULL]'];
        $cases[$message] = [$method, $input, ['default' => 'abc'], $output];

        $message = 'DefaultKeysFlattenFalseInput';
        $input   = ['abc' => false];
        $output  = ['abc' => ''];
        $cases[$message] = [$method, $input, ['default' => 'abc'], $output];

        $message = 'DefaultKeysFlattenTrueInput';
        $input   = ['abc' => true];
        $output  = ['abc' => '1'];
        $cases[$message] = [$method, $input, ['default' => 'abc'], $output];

        $message = 'DefaultKeysNestedStringInput';
        $input   = ['abc' => ['xyz' => 'abc@abc']];
        $output  = ['abc' => ['xyz' => '***@abc']];
        $cases[$message] = [$method, $input, ['default' => 'abc.xyz'], $output];

        // VPA TESTS
        $message = 'VpaKeysFlattenShortInputInteger';
        $input   = ['abc' => 123];
        $output  = ['abc' => '*******123@'];
        $cases[$message] = [$method, $input, ['vpa' => 'abc'], $output];

        $message = 'VpaKeysFlattenLongInputInteger';
        $input   = ['abc' => 123456789];
        $output  = ['abc' => '******6789@'];
        $cases[$message] = [$method, $input, ['vpa' => 'abc'], $output];

        $message = 'VpaKeysFlattenNullInput';
        $input   = ['abc' => null];
        $output  = ['abc' => '[NULL]'];
        $cases[$message] = [$method, $input, ['vpa' => 'abc'], $output];

        $message = 'VpaKeysFlattenFalseInput';
        $input   = ['abc' => false];
        $output  = ['abc' => null];
        $cases[$message] = [$method, $input, ['vpa' => 'abc'], $output];

        $message = 'VpaKeysFlattenTrueInput';
        $input   = ['abc' => true];
        $output  = ['abc' => '*********1@'];
        $cases[$message] = [$method, $input, ['vpa' => 'abc'], $output];

        $message = 'VpaKeysFlattenShortVpaIndexedInput';
        $input   = ['xyz', 'abc@abc'];
        $output  = ['xyz', '*******abc@abc'];
        $cases[$message] = [$method, $input, ['vpa' => 1], $output, true];

        $message = 'VpaKeysFlattenShortVpaInput';
        $input   = ['abc' => 'abc@abc'];
        $output  = ['abc' => '*******abc@abc'];
        $cases[$message] = [$method, $input, ['vpa' => 'abc'], $output];

        $message = 'VpaKeysFlattenLongVpaInput';
        $input   = ['abc' => 'abc123abc123@abc'];
        $output  = ['abc' => '********c123@abc'];
        $cases[$message] = [$method, $input, ['vpa' => 'abc'], $output];

        $message = 'VpaKeysNestedShortVpaInput';
        $input   = ['abc' => ['xyz' => 'abc@abc']];
        $output  = ['abc' => ['xyz' => '*******abc@abc']];
        $cases[$message] = [$method, $input, ['vpa' => 'abc.xyz'], $output];

        $message = 'VpaKeysFlattenMissingVpaInput';
        $input   = ['abc' => 'abc123abc123@abc'];
        $output  = ['abc' => 'abc123abc123@abc'];
        $cases[$message] = [$method, $input, ['vpa' => 'abcd'], $output];

        $message = 'VpaKeysNestedMissingVpaInput';
        $input   = ['abc' => ['xyz' => 'abc@abc']];
        $output  = ['abc' => ['xyz' => 'abc@abc']];
        $cases[$message] = [$method, $input, ['vpa' => 'abcd.xyz'], $output];

        // CONTACT TESTS
        $message = 'ContactKeysFlattenShortInputInteger';
        // Exception will thrown in this case
        $input   = ['abc' => 123];
        $output  = [];
        $cases[$message] = [$method, $input, ['contact' => 'abc'], $output, true];

        $message = 'ContactKeysFlattenLongInputInteger';
        $input   = ['abc' => 123456789];
        $output  = ['abc' => '12*****89'];
        $cases[$message] = [$method, $input, ['contact' => 'abc'], $output];

        $message = 'ContactKeysFlattenNullInput';
        $input   = ['abc' => null];
        $output  = ['abc' => '[NULL]'];
        $cases[$message] = [$method, $input, ['contact' => 'abc'], $output];

        $message = 'ContactKeysFlattenFalseInput';
        $input   = ['abc' => false];
        $output  = ['abc' => null];
        $cases[$message] = [$method, $input, ['contact' => 'abc'], $output];

        $message = 'ContactKeysFlattenTrueInput';
        // Exception will thrown in this case
        $input   = ['abc' => true];
        $output  = [];
        $cases[$message] = [$method, $input, ['contact' => 'abc'], $output, true];

        $message = 'ContactKeysFlattenShortInput';
        $input   = ['abc' => 'abc1abc'];
        $output  = ['abc' => 'ab***bc'];
        $cases[$message] = [$method, $input, ['contact' => 'abc'], $output];

        $message = 'ContactKeysFlattenLongVpaInput';
        $input   = ['abc' => 'abc123abc123abc'];
        $output  = ['abc' => 'ab***********bc'];
        $cases[$message] = [$method, $input, ['contact' => 'abc'], $output];

        $message = 'ContactKeysNestedShortInput';
        $input   = ['abc' => ['xyz' => 'abc@abc']];
        $output  = ['abc' => ['xyz' => 'ab***bc']];
        $cases[$message] = [$method, $input, ['contact' => 'abc.xyz'], $output];

        $message = 'ContactKeysFlattenMissingContactInput';
        $input   = ['abc' => 'abc123abc123@abc'];
        $output  = ['abc' => 'abc123abc123@abc'];
        $cases[$message] = [$method, $input, ['contact' => 'abcd'], $output];

        $message = 'ContactKeysNestedMissingContactInput';
        $input   = ['abc' => ['xyz' => 'abc@abc']];
        $output  = ['abc' => ['xyz' => 'abc@abc']];
        $cases[$message] = [$method, $input, ['contact' => 'abcd.xyz'], $output];

        // Multiple keys VPA and Contact
        $message = 'MultipleKeysFlattenInput';
        $input   = ['abc' => 'abc@abc', 'xyz' => '+910000000001'];
        $output  = ['abc' => '*******abc@abc', 'xyz' => '+9*********01'];
        $cases[$message] = [$method, $input, ['vpa' => 'abc', 'contact' => 'xyz'], $output, true];

        $message = 'MultipleKeysNestedInput';
        $input   = ['abc' => 'abc@abc', 'xyz' => ['+910000000001']];
        $output  = ['abc' => '*******abc@abc', 'xyz' => ['+9*********01']];
        $cases[$message] = [$method, $input, ['vpa' => 'abc', 'contact' => 'xyz.0'], $output, true];

        $message = 'MultipleKeysNestedInputWrongPhone';
        $input   = ['abc' => 'abc@abc', 'xyz' => ['+91'], 'mno' => 'only output'];
        $output  = ['xyz' => [], 'mno' => 'only output'];
        $cases[$message] = [$method, $input, ['vpa' => 'abc', 'contact' => 'xyz.0'], $output, true];

        return $cases;
    }

    /**
     * @see https://stackoverflow.com/questions/249664/best-practices-to-test-protected-methods-with-phpunit
     * @see https://stackoverflow.com/a/105209/3305978
     * @dataProvider maskUpiDataForTracing
     */
    public function testMaskUpiDataForTracing(
        ReflectionMethod $method,
        array $input,
        array $keys,
        array $expected,
        bool $exact = false)
    {
        $gateway = new Gateway();

        $output = $method->invoke($gateway, $input, $keys);

        //var_dump($output, $expected); die();
        if ($exact === false)
        {
            $this->assertArraySubset($expected, $output, true);
        }
        else
        {
            $this->assertSame($expected, $output);
        }
    }
}
