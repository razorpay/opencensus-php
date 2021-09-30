<?php

namespace RZP\Tests\Unit\Models\PaymentLink;

use RZP\Models\PaymentLink\Type;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestValidationFailureException;

class TypeTest extends TestCase
{
    /**
     * @group nocode_pp_type
     * @dataProvider isValidDataProvider
     * @param $type
     * @param $assertBool
     */
    public function testIsValid($type, $assertBool)
    {
        $this->assertTrue(Type::isValid($type) === $assertBool);
    }

    /**
     * @group nocode_pp_type
     * @dataProvider isValidDataProvider
     * @param $type
     * @param $assertBool
     */
    public function testValidateType($type, $assertBool)
    {
        if (! $assertBool)
        {
            $this->expectException(BadRequestValidationFailureException::class);
            $this->expectExceptionMessage('Not a valid account type: ' . $type);
        }

        $this->assertNull(Type::validateType($type));
    }

    public function isValidDataProvider(): array
    {
        return [
            "Random Type should be invalid"         => ["RANDOM", false],
            "Valid Type test " . Type::PAYMENT      => [Type::PAYMENT, true],
            "Valid Type test " . Type::SUBSCRIPTION => [Type::SUBSCRIPTION, true],
            "Empty Type test is invalid"            => ["", false],
        ];
    }
}
