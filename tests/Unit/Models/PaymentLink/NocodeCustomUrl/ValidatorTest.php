<?php

namespace Unit\Models\PaymentLink\NocodeCustomUrl;

use RZP\Tests\Unit\Models\PaymentLink\BaseTest;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\PaymentLink\NocodeCustomUrl\Validator;

class ValidatorTest extends BaseTest
{
    protected $datahelperPath   = '/NocodeCustomUrl/Helpers/ValidatorTestData.php';

    /**
     * @var \RZP\Models\PaymentLink\NocodeCustomUrl\Validator
     */
    protected $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new Validator();
    }

    /**
     * @dataProvider getData
     * @group nocode_ncu
     * @group nocode_ncu_validator
     *
     * @param string $product
     * @param bool   $isValid
     *
     * @return void
     */
    public function testValidateProduct(string $product, bool $isValid)
    {
        if ($isValid === false)
        {
            $this->expectException(BadRequestValidationFailureException::class);
        }

        $this->validator->validateProduct("product", $product);

        if ($isValid)
        {
            // validation ideally does not return anything, if the validation fails it throws exeption, which has been
            // asserted above. If we expect no error, we simply assert true
            $this->assertTrue(true);
        }
    }

    /**
     * @dataProvider getData
     * @group nocode_ncu
     * @group nocode_ncu_validator
     *
     * @param string $domain
     * @param bool   $isvalid
     *
     * @return void
     */
    public function testValidateDomain(string $domain, bool $isvalid)
    {
        if ($isvalid === false)
        {
            $this->expectException(BadRequestValidationFailureException::class);
        }

        $this->validator->validateDomain("domain", $domain);

        if ($isvalid)
        {
            // validation ideally does not return anything, if the validation fails it throws exeption, which has been
            // asserted above. If we expect no error, we simply assert true
            $this->assertTrue(true);
        }
    }
}
