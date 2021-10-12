<?php

namespace RZP\Tests\Unit\Models\PaymentLink;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\PaymentLink\Version;
use RZP\Models\PaymentLink\Validator;
use RZP\Tests\Functional\TestCase;
use RZP\Models\PaymentLink\Entity as E;
use RZP\Models\Payment\Entity as PE;
use RZP\Exception\BadRequestException;
use RZP\Tests\Traits\PaymentLinkTestTrait;
use RZP\Exception\BadRequestValidationFailureException;

class ValidatorTest extends TestCase
{
    use PaymentLinkTestTrait;

    const TEST_PL_ID    = '100000000000pl';

    /**
     * @var \RZP\Models\PaymentLink\Validator
     */
    protected $paymentLinkvalidator;

    protected $data = [];

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentLinkvalidator = new Validator();
    }

    public function getData()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 5);
        $name = $trace[4]['args'][1];
        if (empty($this->data)) {
            $this->data = require(__DIR__ . '/Helpers/ValidatorTestData.php');
        }
        return $this->data[$name];
    }

    /**
     * @dataProvider getData
     * @group nocode_pp_validator
     */
    public function testValidateGoalTracker($data)
    {
        if (isset($data['exception_class']))
        {
            $this->expectException($data['exception_class']);
        }

        if (isset($data['exception_message']))
        {
            $this->expectExceptionMessage($data['exception_message']);
        }

        $this->assertNull($this->paymentLinkvalidator->validateGoalTracker($data['item']));
    }

    /**
     * @group        nocode_pp_validator
     *
     * @param $method
     * @param $value
     * @param $isInvalid
     *
     * @dataProvider generalValidatorDataProvider
     *
     */
    public function testGeneralValidateMethods($method, $value, $isInvalid)
    {
        if ($isInvalid == true)
        {
            $this->expectException(BadRequestValidationFailureException::class);
        }
        $this->assertNull($this->paymentLinkvalidator->$method("name", $value));
    }

    public function generalValidatorDataProvider(): array
    {
        return [
            'Empty slug in validateSlug'                => ['validateSlug', " ", true],
            'Dollar sign slug in validateSlug'          => ['validateSlug', '$slug', true],
            'Normal Valid slug in validateSlug'         => ['validateSlug', 'slug', false],
            'Invalid slug with space in validateSlug'   => ['validateSlug', 'slug slug', true],
            'Valid slug with _ in validateSlug'         => ['validateSlug', 'slug_slug', false],
            'Valid slug with - in validateSlug'         => ['validateSlug', 'slug-slug', false],
            'Invalid slug with a dot in validateSlug'   => ['validateSlug', 'slug.slug', true],
            'Valid slug with - and int in validateSlug' => ['validateSlug', 'slug-slug111', false],
            'Valid slug with only int in validateSlug'  => ['validateSlug', '12312313131', false],

            'Valid slug with only int and _ in validateSlug'    => ['validateSlug', '12312_13131', false],
            'Valid slug with only int and - in validateSlug'    => ['validateSlug', '12312-13131', false],
            'Invalid slug with only int and . in validateSlug'  => ['validateSlug', '12312.13131', true],

            'Timestamp less than 15 minutes in validateExpireBy'    => ['validateExpireBy', Carbon::now(Timezone::IST)->getTimestamp(), true],
            'Timestamp more than 15 minutes in validateExpireBy'    => ['validateExpireBy', Carbon::now(Timezone::IST)->addMinutes(30)->getTimestamp(), false],
        ];
    }

    /**
     * @dataProvider validateTimesPayableInternalDataProvider
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
     * @dataProvider validateAmountInternalDataProvider
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

    /**
     * @dataProvider validateSettingsDataProvider
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
     * @dataProvider validateTimesPayableForActivationDataProvider
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
     * @dataProvider validateMinAmountDataProvider
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

    public function validateMinAmountDataProvider(): array
    {
        return [
            "Min Amount of negative value throws error" => [-1, true],
            "Min Amount of 50 paise throws error"       => [50, true],
            "Min Amount of 99 paise throws error"       => [99, true],
            "Min Amount of 100 paise no error thrown"   => [100, false],
            "Min Amount of 200 paise no error thrown"   => [200, false],
        ];
    }

    public function validateTimesPayableForActivationDataProvider(): array
    {
        return [
            "Times payable less than a value should throw exception"        =>  [1, true],
            "Times payable equal to a value should throw exception"         =>  [3, true],
            "Times payable greater than a value should not throw exception" =>  [10, false],
            "Times payable with null value should not throw exception"      =>  [null, false],
        ];
    }

    public function validateAmountInternalDataProvider(): array
    {
        return [
            "Validate Amount with larger than limit should throw exception"     => [50000001, true],
            "Validate Amount with less than limit should not throw exception"   => [1, false],
            "Validate Amount with null should not throw exception"              => [null, false],
        ];
    }

    public function validateTimesPayableInternalDataProvider(): array
    {
        return [
            "Times Payble less then the actual value should throw exception"        => [1, true],
            "Times Payble greater then the actual value should not throw exception" => [10, false],
            "Times Payble with null value should not throw exception"               => [10, false],
        ];
    }

    public function validateSettingsDataProvider(): array
    {
        return [
            "Validate Settings With Empty AllowMultipleUnits Should Throw Exception"    => [
                [
                    E::AMOUNT   => 0,
                    E::SETTINGS => [
                        E::ALLOW_MULTIPLE_UNITS  => true
                    ],
                ],
                "amount is required with settings.allow_multiple_units."
            ],
            "Validate Settings With Empty Extra Settings Should Throw Exception"    => [
                [
                    E::SETTINGS => [
                        "some_setting"  => false
                    ]
                ],
                'Extra settings keys must not be sent - ' . implode(', ', ["some_setting"]) . '.'
            ]
        ];
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
