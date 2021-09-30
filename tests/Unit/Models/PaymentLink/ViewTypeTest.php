<?php

namespace RZP\Tests\Unit\Models\PaymentLink;

use RZP\Tests\TestCase;
use RZP\Models\PaymentLink\ViewType;
use RZP\Exception\BadRequestValidationFailureException;

class ViewTypeTest extends TestCase
{
    /**
     * @dataProvider provideForIsValid
     * @group nocode_view_types
     */
    public function testIsValid($str, $bool)
    {
        $this->assertTrue(ViewType::isValid($str) === $bool);
    }

    /**
     * @dataProvider provideForCheckViewType
     * @group nocode_view_types
     */
    public function testCheckViewTypeExceptionIsThrown($str, $isInvalid)
    {
        if ($isInvalid) {
            $this->expectException(BadRequestValidationFailureException::class);
        }

        $this->assertNull(ViewType::checkViewType($str));
    }

    public function provideForIsValid(): array
    {
        return [
            [ViewType::BUTTON, true],
            [ViewType::PAGE, true],
            [ViewType::SUBSCRIPTION_BUTTON, true],
            ["some_button", false],
            ["", false],
        ];
    }

    public function provideForCheckViewType(): array
    {
        return [
            ["some_button", true],
            ["", true],
            [ViewType::SUBSCRIPTION_BUTTON, false],
        ];
    }
}
