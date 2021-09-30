<?php

namespace RZP\Tests\Unit\Models\PaymentLink;

use RZP\Tests\Functional\TestCase;
use RZP\Models\PaymentLink\Status;
use RZP\Exception\BadRequestValidationFailureException;

class StatusTest extends TestCase
{
    /**
     * @group nocode_pp_status
     * @dataProvider isValidDataProvider
     * @param $status
     * @param $assertBool
     */
    public function testIsValid($status, $assertBool)
    {
        $this->assertTrue(Status::isValid($status) === $assertBool);
    }

    /**
     * @group nocode_pp_status
     * @dataProvider isValidDataProvider
     * @param $status
     * @param $assertBool
     */
    public function testCheckStatusReason($status, $assertBool)
    {
        if (! $assertBool)
        {
            $this->expectException(BadRequestValidationFailureException::class);
            $this->expectExceptionMessage('Not a valid status: ' . $status);
        }

        $this->assertNull(Status::checkStatus($status));
    }

    public function isValidDataProvider(): array
    {
        return [
            "Random Status should be invalid"       => ["RANDOM", false],
            "Valid Status test " . Status::ACTIVE   => [Status::ACTIVE, true],
            "Valid Status test " . Status::INACTIVE => [Status::INACTIVE, true],
            "Empty Status test is invalid"          => ["", false],
        ];
    }
}
