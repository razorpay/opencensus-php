<?php

namespace RZP\Tests\Unit\Gateway;

use RZP\Gateway\Hdfc;
use RZP\Tests\TestCase;

class BaseGatewayTest extends TestCase
{
    public function testGetMappedAttributes()
    {
        $gateway = new Hdfc\Gateway();

        $gateway->map = [
            'in_field1'     => 'outfield1',
            'in_field2'     => 'outfield2',
            'in_field3'     => 'outfield3',
            'in_field4'     => 'outfield4',
            'in_field5'     => 'outfield5',
            'in_field6'     => 'outfield6',
            'in_field7'     => 'outfield7',
            'in_field8'     => 'outfield8',
            'in_field9'     => 'outfield9',
            'in_field10'    => 'outfield10',
        ];

        $input = [
            'in_field1'     => '',
            'in_field2'     => null,
            'in_field3'     => ' ',
            'in_field4'     => 0,
            'in_field5'     => '0',
            'in_field6'     => 'ABC',
            'in_field7'     => '123',
            'in_field8'     => 123,
            'in_field9'     => true,
            'in_field10'    => false,
        ];

        $output = self::callMethod(
            $gateway,
            'getMappedAttributes',
            [$input]);

        // empty string ('') & null should not be mapped
        $expectedOutput = [
            'outfield3'     => ' ',
            'outfield4'     => 0,
            'outfield5'     => '0',
            'outfield6'     => 'ABC',
            'outfield7'     => '123',
            'outfield8'     => 123,
            'outfield9'     => true,
            'outfield10'    => false,
        ];

        $this->assertEquals($expectedOutput, $output);
    }

    public static function callMethod($obj, $name, array $args) {
        $class = new \ReflectionClass($obj);

        $method = $class->getMethod($name);

        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }
}
