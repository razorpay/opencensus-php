<?php

namespace RZP\Tests\Unit\Models\PaymentLink\PaymentPageItem;

use RZP\Models\Item;
use RZP\Models\Currency\Currency;
use RZP\Exception\BadRequestException;
use RZP\Tests\Traits\PaymentLinkTestTrait;
use RZP\Models\PaymentLink\PaymentPageItem;
use RZP\Tests\Unit\Models\PaymentLink\BaseTest;
use RZP\Exception\BadRequestValidationFailureException;

class ValidatorTest extends BaseTest
{
    use PaymentLinkTestTrait;

    const TEST_PL_ID    = '100000000000pl';
    const TEST_PL_ID_2  = '100000000001pl';
    const TEST_PPI_ID   = '10000000000ppi';
    const TEST_PPI_ID_2 = '10000000001ppi';
    const TEST_ORDER_ID = '10000000000ord';

    protected $datahelperPath   = '/PaymentPageItem/Helpers/ValidatorTestData.php';

    /**
     * @var \RZP\Models\PaymentLink\PaymentPageItem\Validator
     */
    protected $validator;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new PaymentPageItem\Validator();
    }

    /**
     * @group nocode_ppi_validator
     * @dataProvider getData
     *
     * @param int         $amount
     * @param string      $currency
     * @param string|null $exceptionClass
     * @param string|null $exceptionMessage
     */
    public function testValidateAmount(
        int    $amount,
        string $currency=Currency::INR,
        string $exceptionClass=null,
        string $exceptionMessage=null
    )
    {
        $this->createAndAssignEntity();

        $this->assertException($exceptionClass, $exceptionMessage);

        $this->validator->validateAmount("amount", $amount, $currency);

        // validation ideally does not return anything, if the validation fails it throws exeption, which has been
        // asserted above. If we expect no error, we simply assert true
        $this->assertTrue(true);
    }

    /**
     * @group nocode_ppi_validator
     */
    public function testValidateUpdatePaymentPageItemsSameIdShouldThrowException()
    {
        $input = [
            [PaymentPageItem\Entity::ID => self::TEST_PPI_ID],
            [PaymentPageItem\Entity::ID => self::TEST_PPI_ID],
        ];

        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionMessage('multiple payment page with same id not allowed');
        $this->validator->validateUpdatePaymentPageItems($input);
    }

    /**
     * @group nocode_ppi_validator
     * @dataProvider getData
     * @param array       $config
     * @param string|null $exceptionClass
     * @param string|null $exceptionMessage
     */
    public function testValidateProductConfig(array $config, string $exceptionClass=null, string $exceptionMessage=null)
    {
        $this->assertException($exceptionClass, $exceptionMessage);

        $this->validator->validateProductConfig("config", $config);
    }

    /**
     * @group nocode_ppi_validator
     */
    public function testValidateItemPresentWithPlanExistsAndItemPassedShouldThrowException()
    {
        [$_, $ppi] = $this->createAndAssignEntity();

        $mock = \Mockery::mock($ppi)->makePartial();

        $mock->shouldReceive('doesPlanExists')->once()->andReturn(true);

        $this->assertException(BadRequestValidationFailureException::class, 'item must not be sent when plan exists');

        $this->validator->validateItemPresent([PaymentPageItem\Entity::ITEM => []], $mock);
    }

    /**
     * @group nocode_ppi_validator
     */
    public function testValidateItemPresentWithPlanExistsAndItemNotPassedShouldNotThrowException()
    {
        [$_, $ppi] = $this->createAndAssignEntity();

        $mock = \Mockery::mock($ppi)->makePartial();

        $mock->shouldReceive('doesPlanExists')->once()->andReturn(true);

        $this->validator->validateItemPresent([], $mock);
    }

    /**
     * @group nocode_ppi_validator
     */
    public function testValidateItemPresentWithPlanDoesNotExistsAndItemNotPassedShouldThrowException()
    {
        [$_, $ppi] = $this->createAndAssignEntity();

        $mock = \Mockery::mock($ppi)->makePartial();

        $mock->shouldReceive('doesPlanExists')->once()->andReturn(false);

        $this->assertException(BadRequestValidationFailureException::class, 'item must be sent');

        $this->validator->validateItemPresent([], $mock);
    }

    /**
     * @group nocode_ppi_validator
     */
    public function testValidateItemPresentWithPlanDoesNotExistsAndItemPassedShouldNotThrowException()
    {
        [$_, $ppi] = $this->createAndAssignEntity();

        $mock = \Mockery::mock($ppi)->makePartial();

        $mock->shouldReceive('doesPlanExists')->once()->andReturn(false);

        $this->validator->validateItemPresent([PaymentPageItem\Entity::ITEM => []], $mock);
    }

    /**
     * @dataProvider getData
     * @group nocode_ppi_validator
     * @param array       $input
     * @param string|null $exceptionClass
     * @param string|null $exceptionMessage
     */
    public function testValidateMaxAmount(array $input,string $exceptionClass=null, string $exceptionMessage=null)
    {
        $this->createAndAssignEntity();

        $this->assertException($exceptionClass, $exceptionMessage);

        $this->validator->validateMaxAmount($input);
    }

    /**
     * @group nocode_ppi_validator
     */
    public function testValidateMinMaxAmountWithMinMaxAmountShouldThrowException()
    {
        $this->createAndAssignEntity(self::TEST_PPI_ID, self::TEST_PL_ID, [
            PaymentPageItem\Entity::ITEM => [
                Item\Entity::ID     => self::TEST_PPI_ID,
                Item\Entity::TYPE   => Item\Type::PAYMENT_PAGE,
                Item\Entity::NAME   => 'amount',
                Item\Entity::AMOUNT => 1000
            ]
        ]);

        $this->assertException(
            BadRequestValidationFailureException::class,
            'max amount or min amount is not required if amount is set'
        );

        $this->validator->validateMinMaxAmount([
            PaymentPageItem\Entity::MAX_AMOUNT  => 1000,
            PaymentPageItem\Entity::MIN_AMOUNT  => 100,
        ]);
    }

    /**
     * @group nocode_ppi_validator
     */
    public function testValidateCurrencyNonInternationalUserWithUsdCurrency()
    {
        $this->createPaymentLink();

        $ppi        = $this->createPaymentPageItem();
        $merchant   = $ppi->merchant;

        $mockMerchant = \Mockery::mock($merchant)->makePartial();
        $mockMerchant->shouldReceive('isInternational')->once()->andReturn(false);

        $ppi->merchant = $mockMerchant;

        $this->assignEntityValueThroughReflection($ppi);

        $this->assertException(BadRequestException::class);

        $this->validator->validateCurrency("currency", Currency::USD);
    }

    /**
     * @group nocode_ppi_validator
     */
    public function testValidateCurrencyWithCurrencyNotEqualToItemCurrency()
    {
        $this->createAndAssignEntity();

        $this->assertException(
            BadRequestValidationFailureException::class,
            'currency of payment page item should be equal to payment page'
        );

        $this->validator->validateCurrency("currency", Currency::USD);
    }

    /**
     * @group nocode_ppi_validator
     */
    public function testValidateCurrencyWithValidCurrency()
    {
        $this->createAndAssignEntity();

        $this->validator->validateCurrency("currency", Currency::INR);

        // validation ideally does not return anything, if the validation fails it throws exeption,since we expect no
        // error, we simply assert true
        $this->assertTrue(true);
    }

    /**
     * @group nocode_ppi_validator
     */
    public function testValidateMinPurchaseMinPurchaseGreaterThanStockShouldThrowException()
    {
        $this->assertException(
            BadRequestValidationFailureException::class,
            'min purchase should not be greater than stock'
        );

        $this->validator->validateMinPurchase([
            PaymentPageItem\Entity::MIN_PURCHASE    => 100,
            PaymentPageItem\Entity::STOCK           => 10,
        ]);
    }

    /**
     * @dataProvider getData
     * @group nocode_ppi_validator
     *
     * @param int         $stock
     * @param string|null $exceptionClass
     * @param string|null $exceptionMessage
     */
    public function testValidateStock(int $stock, string $exceptionClass=null, string $exceptionMessage=null)
    {
        $mockPpi    = \Mockery::mock(new PaymentPageItem\Entity())->makePartial();

        $mockPpi->shouldReceive('getQuantitySold')->andReturn(10);

        $this->assignEntityValueThroughReflection($mockPpi);

        $this->assertException($exceptionClass, $exceptionMessage);

        $this->validator->validateStock("stock", $stock);
    }

    /**
     * @group nocode_ppi_validator
     */
    public function testValidateEmptyStringForInteger()
    {
        $this->assertException(BadRequestValidationFailureException::class, "attr should be null or valid integer");
        $this->validator->validateEmptyStringForInteger("attr", "");
    }

    /**
     * @group nocode_ppi_validator
     */
    public function testValidateInputForUpdate()
    {
        $mockPpi    = \Mockery::mock(new PaymentPageItem\Entity())->makePartial();

        $mockPpi->shouldReceive('getQuantitySold')->andReturn(10);

        $this->assignEntityValueThroughReflection($mockPpi);

        $this->assertException(BadRequestValidationFailureException::class, 'stock cannot be lesser than the quantity sold');

        $this->validator->validateInputForUpdate([
            PaymentPageItem\Entity::STOCK   => 9
        ]);
    }

    // =============================== private methods ===================== //

    /**
     * @param string|null $exceptionClass
     * @param string|null $exceptionMessage
     */
    private function assertException(string $exceptionClass=null, string $exceptionMessage=null)
    {
        if (empty($exceptionClass) === false)
        {
            $this->expectException($exceptionClass);
        }

        if (empty($exceptionMessage) === false)
        {
            $this->expectExceptionMessage($exceptionMessage);
        }

        if (empty($exceptionClass) === true && empty($exceptionMessage) === true)
        {
            // validation ideally does not return anything, if the validation fails it throws exeption, which has been
            // asserted above. If we expect no error, we simply assert true
            $this->assertTrue(true);
        }
    }

    private function createAndAssignEntity(
        string $id = self::TEST_PPI_ID,
        string $paymentLinkId = self::TEST_PL_ID,
        array $attributes = []
    ): array
    {
        $pl     = $this->createPaymentLink();
        $ppi    = $this->createPaymentPageItem($id, $paymentLinkId, $attributes);

        $this->assignEntityValueThroughReflection($ppi);

        return [$pl, $ppi];
    }

    private function assignEntityValueThroughReflection(PaymentPageItem\Entity $entity): void
    {
        $reflector  = new \ReflectionClass($this->validator);
        $property   = $reflector->getProperty('entity');

        $property->setAccessible( true );
        $property->setValue($this->validator, $entity);
    }
}
