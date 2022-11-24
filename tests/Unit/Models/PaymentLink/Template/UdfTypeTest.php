<?php

namespace RZP\Tests\Unit\Models\PaymentLink\Template;

use RZP\Models\PaymentLink\Template\UdfType;
use RZP\Tests\Unit\Models\PaymentLink\BaseTest;
use RZP\Exception\BadRequestValidationFailureException;

class UdfTypeTest extends BaseTest
{
    protected $datahelperPath   = '/Template/Helpers/UdfTypeTestData.php';

    /**
     * @dataProvider getData
     * @group nocode_pp_udf
     * @group nocode_pp_udf_udf_type
     */
    public function testIsValid($str, $bool)
    {
        $this->assertTrue(UdfType::isValid($str) === $bool);
    }

    /**
     * @dataProvider getData
     * @group nocode_pp_udf
     * @group nocode_pp_udf_udf_type
     */
    public function testValidate($str, $isValid)
    {
        if (! $isValid) {
            $this->expectException(BadRequestValidationFailureException::class);
        }

        UdfType::validate($str);

        // validation ideally does not return anything, if the validation fails it throws exeption, which has been
        // asserted above. If we expect no error, we simply assert true
        $this->assertTrue(true);
    }
}
