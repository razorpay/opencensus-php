<?php

namespace RZP\Tests\Unit\Models\PaymentLink;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\PaymentLink\Version;
use RZP\Models\PaymentLink\Validator;
use RZP\Models\PaymentLink\Entity as E;
use RZP\Models\Payment\Entity as PE;
use RZP\Exception\BadRequestException;
use RZP\Tests\Traits\PaymentLinkTestTrait;
use RZP\Exception\BadRequestValidationFailureException;

class ValidatorTest extends BaseTest
{
    use PaymentLinkTestTrait;

    const TEST_PL_ID    = '100000000000pl';

    protected $datahelperPath   = '/Helpers/ValidatorTestData.php';

    /**
     * @var \RZP\Models\PaymentLink\Validator
     */
    protected $paymentLinkvalidator;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentLinkvalidator = new Validator();
    }

    /**
     * @dataProvider getData
     * @group nocode_pp_validator
     */
    public function testValidateGoalTracker($data, $exceptionClass=null, $exceptionMessage=null)
    {
        if (empty($exceptionClass) === false)
        {
            $this->expectException($exceptionClass);
        }

        if (empty($exceptionMessage) === false)
        {
            $this->expectExceptionMessage($exceptionMessage);
        }

        $this->assertNull($this->paymentLinkvalidator->validateGoalTracker($data));
    }

    /**
     * @group        nocode_pp_validator
     *
     * @param $method
     * @param $value
     * @param bool $isInvalid
     *
     * @dataProvider getData
     *
     */
    public function testGeneralValidateMethods($method, $value, bool $isInvalid=false)
    {
        if ($isInvalid == true)
        {
            $this->expectException(BadRequestValidationFailureException::class);
        }
        $this->assertNull($this->paymentLinkvalidator->$method("name", $value));
    }

    /**
     * @dataProvider getData
     * @group nocode_pp_validator
     */
    public function testValidateTimesPayable($value, $isInvalid)
    {
        $this->buildTimesPaidCases();

        if ($isInvalid == true)
        {
            $this->expectException(BadRequestValidationFailureException::class);
        }

        $this->assertNull($this->paymentLinkvalidator->validateTimesPayable("name", $value));
    }

    /**
     * @dataProvider getData
     * @group nocode_pp_validator
     */
    public function testValidateAmount($value, $isInvalid)
    {
        $pl = $this->createPaymentLink();

        $this->assignEntityValueThroughReflection($pl);

        if ($isInvalid == true)
        {
            $this->expectException(BadRequestValidationFailureException::class);
        }

        $this->assertNull($this->paymentLinkvalidator->validateAmount("amount", $value));
    }

    public function testValidateMaxAllowedAmountExceedsForCurrency()
    {
        $pl = $this->createPaymentLink();

        $this->assignEntityValueThroughReflection($pl);
        $pl['currency'] = 'IDR';

        $data = '';
        $this->expectException(BadRequestValidationFailureException::class);
        try {
            $this->paymentLinkvalidator->validateAmount("amount", '2000000000');
        }catch (Exception\BadRequestValidationFailureException $ex){
            $data = $ex ->getMessage();
        }

        $this->assertEquals("amount exceeds maximum payment amount allowed",$data);

    }

    public function testValidateMaxAllowedAmountForCurrency()
    {
        $pl = $this->createPaymentLink();

        $this->assignEntityValueThroughReflection($pl);
        $pl['currency'] = 'IDR';
        $this->expectException(BadRequestValidationFailureException::class);

        $this->assertNull($this->paymentLinkvalidator->validateAmount("amount", '51000000'));

    }

    /**
     * @dataProvider getData
     * @group nocode_pp_validator
     */
    public function testValidateSettings($inputArr, $msg)
    {
        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionMessage($msg);
        $this->paymentLinkvalidator->validateSettings($inputArr);
    }

    /**
     * @group nocode_pp_validator
     */
    public function testValidatePaymentPageItemWithNoitemsShowNotThrowException()
    {
        $this->assertNull($this->paymentLinkvalidator->validatePaymentPageItems([]));
    }

    /**
     * @dataProvider getData
     * @group nocode_pp_validator
     */
    public function testValidateTimesPayableForActivation($value, $isInvalid)
    {
        $this->buildTimesPaidCases();

        if ($isInvalid == true)
        {
            $this->expectException(BadRequestValidationFailureException::class);
        }

        $this->assertNull($this->paymentLinkvalidator->validateTimesPayableForActivation($value));
    }

    /**
     * @group nocode_pp_validator
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function testValidateShouldActivationBeAllowedValidValueNoErrorThrown()
    {
        $attr = [
            E::TIMES_PAID       => 3,
            E::TIMES_PAYABLE    => 4,
            E::EXPIRE_BY        => Carbon::now(Timezone::IST)->addDays(100)->getTimestamp(),
        ];
        $pl = $this->createPaymentLink(self::TEST_PL_ID, $attr);
        $this->assignEntityValueThroughReflection($pl);
        $this->assertNull($this->paymentLinkvalidator->validateShouldActivationBeAllowed());
    }

    /**
     * @group nocode_pp_validator
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function testValidateShouldActivationBeAllowedV1PayableExaustedThrowError()
    {
        $attr = [
            E::TIMES_PAID       => 3,
            E::TIMES_PAYABLE    => 4,
            E::EXPIRE_BY        => Carbon::now(Timezone::IST)->addDays(100)->getTimestamp(),
        ];
        $pl = $this->createPaymentLink(self::TEST_PL_ID, $attr);
        $mockPl = \Mockery::mock($pl)->makePartial();
        $mockPl->shouldReceive("getVersion")->andReturn(Version::V2);
        $this->assignEntityValueThroughReflection($mockPl);

        $this->expectException(BadRequestValidationFailureException::class);
        $msg = "at least one of the payment page item's stock should be left to activate payment page";
        $this->expectExceptionMessage($msg);

        $this->assertNull($this->paymentLinkvalidator->validateShouldActivationBeAllowed());
    }

    /**
     * @group nocode_pp_validator
     * @throws \RZP\Exception\BadRequestException
     */
    public function testValidateCurrency()
    {
        $pl = $this->createPaymentLink();
        $this->assignEntityValueThroughReflection($pl);
        $this->expectException(BadRequestException::class);
        $this->paymentLinkvalidator->validateCurrency("currency", "YEN");
    }

    /**
     * @group nocode_pp_validator
     * @throws \RZP\Exception\BadRequestException
     */
    public function testValidatePaymentCurrency()
    {
        $pl = $this->createPaymentLink();
        $this->assignEntityValueThroughReflection($pl);
        $paymntEntity = \Mockery::mock(PE::class)->makePartial();
        $paymntEntity->shouldReceive("getCurrency")->once()->andReturn("YEN");
        $this->expectException(BadRequestException::class);
        $this->paymentLinkvalidator->validatePaymentCurrency($paymntEntity);
    }

    /**
     * @dataProvider getData
     * @group nocode_pp_validator
     */
    public function testValidateMinAmount($paise, $isinvalid)
    {
        $pl = $this->createPaymentLink();
        $this->assignEntityValueThroughReflection($pl);
        if ($isinvalid)
        {
            $this->expectException(BadRequestValidationFailureException::class);
        }

        $this->assertNull($this->paymentLinkvalidator->validateMinAmount([E::AMOUNT => $paise]));
    }

    /**
     * @group nocode_pp_validator
     */
    public function testValidateSelectedUdfFieldThrowErrorForInvalidValue()
    {
        $pl = $this->createPaymentLink();
        $settings = [
            E::UDF_SCHEMA => '[{"name":"email","required":true,"title":"Email","type":"string","pattern":"email","settings":{"position":1}}]'
        ];
        $pl->getSettingsAccessor()->upsert($settings)->save();

        $this->assignEntityValueThroughReflection($pl);

        $this->expectException(BadRequestException::class);

        $this->paymentLinkvalidator->validateSelectedUdfField("name", "NO_NAME");
    }

    /**
     * @dataProvider getData
     * @group nocode_pp_validator
     *
     * @param $url
     * @param $isValid
     * @return void
     * @throws BadRequestValidationFailureException
     */
    public function testValidateDescription($url, $isValid)
    {
        $description = "{\"value\":[{\"insert\":{\"video\":\"" . $url . "\"}},{\"insert\":\"\\n\"}],\"metaText\":\". \"}";

        if($isValid === false)
        {
            $this->expectException(BadRequestValidationFailureException::class);

            $this->expectExceptionMessage('Only Youtube and Vimeo videos allowed');
        }

        $this->paymentLinkvalidator->validateDescription(E::DESCRIPTION, $description);

        // validation ideally does not return anything, if the validation fails it throws exeption, which has been
        // asserted above. If we expect no error, we simply assert true
        $this->assertTrue(true);
    }

    protected function assignEntityValueThroughReflection(E $entity): void
    {
        $reflector = new \ReflectionClass($this->paymentLinkvalidator);
        $property = $reflector->getProperty('entity');
        $property->setAccessible( true );
        $property->setValue($this->paymentLinkvalidator, $entity);
    }

    protected function buildTimesPaidCases()
    {
        $attr = [
            E::TIMES_PAID   => 3
        ];
        $pl = $this->createPaymentLink(self::TEST_PL_ID, $attr);

        $this->assignEntityValueThroughReflection($pl);
    }
}
