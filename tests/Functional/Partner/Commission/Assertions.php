<?php

namespace RZP\Tests\Functional\Partner\Commission;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Partner\Commission;
use RZP\Models\Partner\Commission\Calculator;
use RZP\Tests\Functional\Helpers\PrivateMethodTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class Assertions extends TestCase
{
    use CommissionTrait;
    use DbEntityFetchTrait;
    use PrivateMethodTrait;

    public function testImplicitVariable(array $data)
    {
        $postAction = $data['post_action'];

        $calculator = $postAction['calculator'];

        $this->assertImplicitPlanType($calculator, 'implicit_variable');

        $this->assertBasicCalculatorRules($calculator);

        $this->assertNonZeroFeeTaxes($calculator);

        $commissions = $calculator->getCommissions();

        $amount          = 400000; // INR 4000
        $merchantPricing = 2; // 2% pricing

        $this->assertEquals($this->getFee($amount, $merchantPricing), $calculator->getMerchantFee());
        $this->assertEquals($this->getTax($amount, $merchantPricing), $calculator->getMerchantTax());

        $this->assertEquals(1, count($commissions));
        $this->assertEquals(944, $commissions[0]->fee);
        $this->assertEquals(144, $commissions[0]->tax);
    }

    public function testImplicitFixed(array $data)
    {
        $calculator = $data['post_action']['calculator'];

        $this->assertImplicitPlanType($calculator, 'implicit_fixed');

        $this->assertBasicCalculatorRules($calculator);

        $commissions = $calculator->getCommissions();

        $amount          = 400000; // INR 4000
        $merchantPricing = 2; // 2% pricing

        $this->assertEquals($this->getFee($amount, $merchantPricing), $calculator->getMerchantFee());
        $this->assertEquals($this->getTax($amount, $merchantPricing), $calculator->getMerchantTax());

        $this->assertEquals(1, count($commissions));

        $commissionPricing = 0.3;

        $this->assertEquals($this->getFee($amount, $commissionPricing), $commissions[0]->fee);
        $this->assertEquals($this->getTax($amount, $commissionPricing), $commissions[0]->tax);
    }

    public function testImplicitFixedCommissionGreaterThanMerchantFees(array $data)
    {
        $calculator = $data['post_action']['calculator'];

        $this->assertImplicitPlanType($calculator, 'implicit_fixed');

        $commissions = $calculator->getCommissions();

        $this->assertEmpty($commissions);
    }

    public function testImplicitFixedCommissionIsZero(array $data)
    {
        $calculator = $data['post_action']['calculator'];

        $this->assertImplicitPlanType($calculator, 'implicit_fixed');

        $commissions = $calculator->getCommissions();

        $this->assertEmpty($commissions);
    }

    public function testImplicitVariableMultiplePricingRules(array $data)
    {
        $postAction = $data['post_action'];

        $calculator = $postAction['calculator'];

        $this->assertBasicCalculatorRules($calculator);

        $this->assertNonZeroFeeTaxes($calculator);

        $commissions = $calculator->getCommissions();

        $amount          = 400000; // INR 4000
        $merchantPricing = 4; // 2% base pricing + 2% recurring payment pricing

        $this->assertEquals($this->getFee($amount, $merchantPricing), $calculator->getMerchantFee());
        $this->assertEquals($this->getTax($amount, $merchantPricing), $calculator->getMerchantTax());

        $this->assertEquals(1, count($commissions));
        $this->assertEquals(944, $commissions[0]->fee);
        $this->assertEquals(144, $commissions[0]->tax);
    }

    public function testPartnerDoesNotExist(array $data)
    {
        $this->assertShouldNotCreateCommission($data);
    }

    public function testPartnerConfigDoesNotExist(array $data)
    {
        $this->assertShouldNotCreateCommission($data);
    }

    public function testImplicitExplicitPricingDoesNotExist(array $data)
    {
        $this->assertShouldNotCreateCommission($data);
    }

    public function testCustomerFeeBearer(array $data)
    {
        $this->assertShouldNotCreateCommission($data);
    }

    public function testPostpaidFeeModel(array $data)
    {
        $this->assertShouldNotCreateCommission($data);
    }

    public function testCommissionDisabled(array $data)
    {
        $this->assertShouldNotCreateCommission($data);
    }

    public function testImplicitPricingExpiredNoExplicitDefined(array $data)
    {
        $this->assertShouldNotCreateCommission($data);
    }

    public function testImplicitPricingExpiredExplicitExists(array $data)
    {
        $this->assertShouldCreateCommission($data);
    }

    public function testPublicAuthPaymentForReseller(array $data)
    {
        $this->assertShouldCreateCommission($data);
    }

    public function testPublicAuthPaymentForAggregator(array $data)
    {
        $this->assertShouldNotCreateCommission($data);
    }

    public function testMissingPartnerPricingRule(array $data)
    {
        $postAction = $data['post_action'];

        $calculator = $postAction['calculator'];

        $this->assertZeroCommission($calculator);
    }

    public function testGSTOnCommissionForPaymentWithNoGST(array $data)
    {
        $postAction = $data['post_action'];

        $calculator = $postAction['calculator'];

        $this->assertBasicCalculatorRules($calculator);

        $commissions = $calculator->getCommissions();

        $amount          = 100000; // INR 1000
        $merchantPricing = 2; // 2% base pricing

        $this->assertEquals($this->getFee($amount, $merchantPricing), $calculator->getMerchantFee());
        $this->assertEquals($this->getTax($amount, $merchantPricing), $calculator->getMerchantTax());

        $this->assertEquals(1, count($commissions));
        $this->assertEquals(236, $commissions[0]->fee);
        $this->assertEquals(36, $commissions[0]->tax);
    }

    protected function assertBasicCalculatorRules(Calculator $calculator)
    {
        $shouldCreateCommission = $this->invokePrivateMethod(
                                    $calculator,
                                    Calculator::class,
                                    'shouldCreateCommission');

        $this->assertTrue($shouldCreateCommission);

        $commissions   = $calculator->getCommissions();

        $this->assertNotEmpty($commissions);
        $commission    = $commissions[0];

        $this->assertEquals($commission->getType(), Commission\Type::IMPLICIT);

        $commissionFee = $commission->getFee();
        $merchantFee   = $calculator->getMerchantFee();

        $isVariableCommission = $this->invokePrivateMethod(
            $calculator,
            Calculator::class,
            'isImplicitCommissionVariable');

        $this->assertNotNull($calculator->getPartnerConfig());

        $this->assertNotEquals(0, $merchantFee);
        $this->assertNotEquals(0, $commissionFee);

        if ($isVariableCommission === true)
        {
            $partnerFee    = $calculator->getPartnerFee();

            $this->assertNotEquals(0, $partnerFee);
            $this->assertNotEquals($partnerFee, $merchantFee);
        }

        $this->assertTrue($commissionFee < $merchantFee);
    }

    protected function assertNonZeroFeeTaxes(Calculator $calculator)
    {
        $merchantTax   = $calculator->getMerchantTax();

        $commissions   = $calculator->getCommissions();

        $this->assertNotEmpty($commissions);
        $commission    = $commissions[0];

        $commissionTax = $commission->getTax();

        $isVariableCommission = $this->invokePrivateMethod(
            $calculator,
            Calculator::class,
            'isImplicitCommissionVariable');

        if ($isVariableCommission === true)
        {
            $partnerTax    = $calculator->getPartnerTax();

            $this->assertNotEquals(0, $partnerTax);
            $this->assertNotEquals($partnerTax, $merchantTax);
        }

        $this->assertNotEquals(0, $merchantTax);
        $this->assertNotEquals(0, $commissionTax);
    }

    protected function assertShouldNotCreateCommission(array $data)
    {
        $postAction = $data['post_action'];

        $calculator = $postAction['calculator'];

        $shouldCreateCommission = $this->invokePrivateMethod(
                                    $calculator,
                                    Calculator::class,
                                    'shouldCreateCommission');

        $this->assertFalse($shouldCreateCommission);
    }

    protected function assertShouldCreateCommission(array $data)
    {
        $postAction = $data['post_action'];

        $calculator = $postAction['calculator'];

        $shouldCreateCommission = $this->invokePrivateMethod(
                                    $calculator,
                                    Calculator::class,
                                    'shouldCreateCommission');

        $this->assertTrue($shouldCreateCommission);
    }

    protected function assertImplicitPlanType(Calculator $calculator, string $type)
    {
        $condition = false;

        if ($type === 'implicit_variable')
        {
            $condition = $this->invokePrivateMethod(
                $calculator,
                Calculator::class,
                'isImplicitCommissionVariable');
        }
        else if ($type === 'implicit_fixed')
        {
            $condition = $this->invokePrivateMethod(
                $calculator,
                Calculator::class,
                'isImplicitCommissionFixed');
        }

        $this->assertTrue($condition);
    }

    protected function assertZeroCommission(Calculator $calculator)
    {
        $shouldCreateCommission = $this->invokePrivateMethod(
            $calculator,
            Calculator::class,
            'shouldCreateCommission');

        $this->assertTrue($shouldCreateCommission);

        $partnerFee    = $calculator->getPartnerFee();
        $partnerTax    = $calculator->getPartnerTax();

        $this->assertNotNull($calculator->getPartnerConfig());

        $this->assertEquals(0, $partnerFee);
        $this->assertEquals(0, $partnerTax);

        $this->assertEmpty($calculator->getCommissions());
    }
}
