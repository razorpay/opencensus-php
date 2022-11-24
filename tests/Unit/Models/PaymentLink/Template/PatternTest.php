<?php

namespace RZP\Tests\Unit\Models\PaymentLink\Template;

use RZP\Models\PaymentLink\Template\Pattern;
use RZP\Tests\Unit\Models\PaymentLink\BaseTest;
use RZP\Exception\BadRequestValidationFailureException;

class PatternTest extends BaseTest
{
    protected $datahelperPath   = '/Template/Helpers/PatternTestData.php';

    /**
     * @dataProvider getData
     * @group nocode_pp_udf
     * @group nocode_pp_udf_pattern
     */
    public function testIsValid($str, $bool)
    {
        $this->assertTrue(Pattern::isValid($str) === $bool);
    }

    /**
     * @dataProvider getData
     * @group nocode_pp_udf
     * @group nocode_pp_udf_pattern
     */
    public function testValidate($str, $isValid)
    {
        if (! $isValid) {
            $this->expectException(BadRequestValidationFailureException::class);
        }

        Pattern::validate($str);

        if ($isValid)
        {
            // validation ideally does not return anything, if the validation fails it throws exeption, which has been
            // asserted above. If we expect no error, we simply assert true
            $this->assertTrue(true);
        }
    }
}
