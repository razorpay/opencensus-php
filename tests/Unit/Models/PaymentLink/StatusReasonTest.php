<?php

namespace RZP\Tests\Unit\Models\PaymentLink;

use RZP\Tests\Functional\TestCase;
use RZP\Models\PaymentLink\StatusReason;
use RZP\Exception\BadRequestValidationFailureException;

class StatusReasonTest extends TestCase
{
    /**
     * @group nocode_pp_status_reason
     * @dataProvider isValidDataProvider
     * @param $statusReason
     * @param $assertBool
     */
    public function testIsValid($statusReason, $assertBool)
    {
        $this->assertTrue(StatusReason::isValid($statusReason) === $assertBool);
    }

    /**
     * @group nocode_pp_status_reason
     * @dataProvider isValidDataProvider
     * @param $statusReason
     * @param $assertBool
     */
    public function testCheckStatusReason($statusReason, $assertBool)
    {
        if (! $assertBool)
        {
            $this->expectException(BadRequestValidationFailureException::class);
            $this->expectExceptionMessage('Not a valid status reason: ' . $statusReason);
        }

        $this->assertNull(StatusReason::checkStatusReason($statusReason));
    }

    public function isValidDataProvider(): array
    {
        return [
            "Random Status Reason should be invalid"                => ["RANDOM", false],
            "Valid Status Reason test " . StatusReason::EXPIRED     => [StatusReason::EXPIRED, true],
            "Valid Status Reason test " . StatusReason::COMPLETED   => [StatusReason::COMPLETED, true],
            "Empty Status Reason test is invalid"                   => ["", false],
        ];
    }
}
