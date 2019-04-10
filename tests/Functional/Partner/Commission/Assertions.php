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
        $this->assertShouldCreateCommission($data);

        $postAction = $data['post_action'];

        $calculator = $postAction['calculator'];

        $this->assertImplicitPlanType($calculator, 'implicit_variable');

        $commission = $this->assertCommissionCreatedByType($calculator, Commission\Type::IMPLICIT);

        $this->assertNonZeroCommissionTaxByType($calculator, Commission\Type::IMPLICIT);

        $amount          = 400000; // INR 4000
        $merchantPricing = 2; // 2% pricing

        $this->assertEquals($this->getFee($amount, $merchantPricing), $calculator->getMerchantFee());
        $this->assertEquals($this->getTax($amount, $merchantPricing), $calculator->getMerchantTax());

        $this->assertEquals(944, $commission->getFee());
        $this->assertEquals(144, $commission->getTax());
    }

    public function testImplicitFixed(array $data)
    {
        $this->assertShouldCreateCommission($data);

        $calculator = $data['post_action']['calculator'];

        $this->assertImplicitPlanType($calculator, 'implicit_fixed');

        $commission = $this->assertCommissionCreatedByType($calculator, Commission\Type::IMPLICIT);

        $amount          = 400000; // INR 4000
        $merchantPricing = 2; // 2% pricing

        $this->assertEquals($this->getFee($amount, $merchantPricing), $calculator->getMerchantFee());
        $this->assertEquals($this->getTax($amount, $merchantPricing), $calculator->getMerchantTax());

        $commissionPricing = 0.3;

        $this->assertEquals($this->getFee($amount, $commissionPricing), $commission->getFee());
        $this->assertEquals($this->getTax($amount, $commissionPricing), $commission->getTax());
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
        $this->assertShouldCreateCommission($data);

        $postAction = $data['post_action'];

        $calculator = $postAction['calculator'];

        $commission = $this->assertCommissionCreatedByType($calculator, Commission\Type::IMPLICIT);

        $this->assertNonZeroCommissionTaxByType($calculator, Commission\Type::IMPLICIT);

        $amount          = 400000; // INR 4000
        $merchantPricing = 4; // 2% base pricing + 2% recurring payment pricing

        $this->assertEquals($this->getFee($amount, $merchantPricing), $calculator->getMerchantFee());
        $this->assertEquals($this->getTax($amount, $merchantPricing), $calculator->getMerchantTax());

        $this->assertEquals(944, $commission->getFee());
        $this->assertEquals(144, $commission->getTax());
    }

    public function testImplicitCustomerFeeBearer(array $data)
    {
        $postAction = $data['post_action'];

        $calculator = $postAction['calculator'];

        $this->assertImplicitPlanType($calculator, 'implicit_variable');

        $commission = $this->assertCommissionCreatedByType($calculator, Commission\Type::IMPLICIT);

        $this->assertNonZeroCommissionTaxByType($calculator, Commission\Type::IMPLICIT);

        $amount          = 400000; // INR 4000
        $merchantPricing = 2; // 2% pricing

        $this->assertEquals($this->getFee($amount, $merchantPricing), $calculator->getMerchantFee());
        $this->assertEquals($this->getTax($amount, $merchantPricing), $calculator->getMerchantTax());

        $this->assertEquals(944, $commission->getFee());
        $this->assertEquals(144, $commission->getTax());
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
        $this->assertShouldCreateCommission($data);

        $postAction = $data['post_action'];

        $calculator = $postAction['calculator'];

        $commission = $this->assertCommissionCreatedByType($calculator, Commission\Type::IMPLICIT);

        $amount          = 100000; // INR 1000
        $merchantPricing = 2; // 2% base pricing

        $this->assertEquals($this->getFee($amount, $merchantPricing), $calculator->getMerchantFee());
        $this->assertEquals($this->getTax($amount, $merchantPricing), $calculator->getMerchantTax());

        $this->assertEquals(236, $commission->getFee());
        $this->assertEquals(36, $commission->getTax());
    }

    public function testExplicit(array $data)
    {
        $this->assertShouldCreateCommission($data);

        $postAction = $data['post_action'];

        $calculator = $postAction['calculator'];

        $commission = $this->assertCommissionCreatedByType($calculator, Commission\Type::EXPLICIT);

        $this->assertNonZeroCommissionTaxByType($calculator, Commission\Type::EXPLICIT);

        $this->assertEquals(944, $commission->getFee());
        $this->assertEquals(144, $commission->getTax());
    }

    public function testExplicitRecordOnly(array $data)
    {
        $this->assertShouldCreateCommission($data);

        $postAction = $data['post_action'];

        $calculator = $postAction['calculator'];

        $commission = $this->assertCommissionCreatedByType($calculator, Commission\Type::EXPLICIT);

        $this->assertNonZeroCommissionTaxByType($calculator, Commission\Type::EXPLICIT);

        $this->assertEquals(944, $commission->getFee());
        $this->assertEquals(144, $commission->getTax());
    }

    public function testGSTOnExplicit(array $data)
    {
        $this->assertShouldCreateCommission($data);

        $postAction = $data['post_action'];

        $calculator = $postAction['calculator'];

        $commission = $this->assertCommissionCreatedByType($calculator, Commission\Type::EXPLICIT);

        $this->assertNonZeroCommissionTaxByType($calculator, Commission\Type::EXPLICIT);

        $this->assertEquals(236, $commission->getFee());
        $this->assertEquals(36, $commission->getTax());
    }

    public function testImplicitVariableAndExplicit(array $data)
    {
        $this->assertShouldCreateCommission($data);

        $postAction = $data['post_action'];

        $calculator = $postAction['calculator'];

        $commission = $this->assertCommissionCreatedByType($calculator, Commission\Type::IMPLICIT, 2);

        $this->assertNonZeroCommissionTaxByType($calculator, Commission\Type::IMPLICIT, 2);

        $this->assertEquals(944, $commission->getFee());
        $this->assertEquals(144, $commission->getTax());

        $commission = $this->assertCommissionCreatedByType($calculator, Commission\Type::EXPLICIT, 2);

        $this->assertNonZeroCommissionTaxByType($calculator, Commission\Type::EXPLICIT, 2);

        $this->assertEquals(944, $commission->getFee());
        $this->assertEquals(144, $commission->getTax());
    }

    public function testExplicitFixedFeesType(array $data)
    {
        $this->assertShouldCreateCommission($data);

        $postAction = $data['post_action'];

        $calculator = $postAction['calculator'];

        $commission = $this->assertCommissionCreatedByType($calculator, Commission\Type::EXPLICIT);

        $this->assertNonZeroCommissionTaxByType($calculator, Commission\Type::EXPLICIT);

        $this->assertEquals(944, $commission->getFee());
        $this->assertEquals(144, $commission->getTax());
    }

    public function testImplicitFixedAndExplicit(array $data)
    {
        $this->assertShouldCreateCommission($data);

        $postAction = $data['post_action'];

        $calculator = $postAction['calculator'];

        $commission = $this->assertCommissionCreatedByType($calculator, Commission\Type::IMPLICIT, 2);

        $this->assertNonZeroCommissionTaxByType($calculator, Commission\Type::IMPLICIT, 2);

        $this->assertEquals(944, $commission->getFee());
        $this->assertEquals(144, $commission->getTax());

        $commission = $this->assertCommissionCreatedByType($calculator, Commission\Type::EXPLICIT, 2);

        $this->assertNonZeroCommissionTaxByType($calculator, Commission\Type::EXPLICIT, 2);

        $this->assertEquals(944, $commission->getFee());
        $this->assertEquals(144, $commission->getTax());
    }

    public function testExplicitWithAddOnPricingRules(array $data)
    {
        $this->assertShouldCreateCommission($data);

        $postAction = $data['post_action'];

        $calculator = $postAction['calculator'];

        $commission = $this->assertCommissionCreatedByType($calculator, Commission\Type::EXPLICIT);

        $this->assertNonZeroCommissionTaxByType($calculator, Commission\Type::EXPLICIT);

        $this->assertEquals(1888, $commission->getFee());
        $this->assertEquals(288, $commission->getTax());
    }

    protected function getCommissionByType(Calculator $calculator, string $type, int $totalCount)
    {
        $commissions   = $calculator->getCommissions();

        $this->assertEquals($totalCount, count($commissions));

        $commissionByType = null;

        foreach ($commissions as $commission)
        {
            if ($commission->getType() === $type)
            {
                $commissionByType = $commission;
                break;
            }
        }

        return $commissionByType;
    }

    protected function assertCommissionCreatedByType(
        Calculator $calculator,
        string $type,
        int $totalCount = 1): Commission\Entity
    {
        $commission = $this->getCommissionByType($calculator, $type, $totalCount);

        $this->assertNotEmpty($commission);

        $commissionFee = $commission->getFee();

        $this->assertNotNull($calculator->getPartnerConfig());

        $this->assertNotEquals(0, $commissionFee);

        if ($type === Commission\Type::IMPLICIT)
        {
            $this->assertImplicitCommissionRules($calculator, $commission);
        }

        return $commission;
    }

    protected function assertImplicitCommissionRules(Calculator $calculator, Commission\Entity $commission)
    {
        $isVariableCommission = $this->invokePrivateMethod(
            $calculator,
            Calculator::class,
            'isImplicitCommissionVariable');

        $merchantFee   = $calculator->getMerchantFee();
        $commissionFee = $commission->getFee();

        if ($isVariableCommission === true)
        {
            $partnerFee = $calculator->getPartnerFee();

            $this->assertNotEquals(0, $partnerFee);
            $this->assertGreaterThan($partnerFee, $merchantFee);
        }

        $this->assertNotEquals(0, $merchantFee);

        $this->assertTrue($commissionFee < $merchantFee);
    }

    protected function assertNonZeroCommissionTaxByType(Calculator $calculator, string $type, int $totalCount = 1)
    {
        $commission = $this->getCommissionByType($calculator, $type, $totalCount);

        $this->assertNotEmpty($commission);

        $this->assertNotEquals(0, $commission->getTax());

        if ($type === Commission\Type::IMPLICIT)
        {
            $this->assertImplicitCommissionTaxRules($calculator, $commission);
        }
    }

    protected function assertImplicitCommissionTaxRules(Calculator $calculator, Commission\Entity $commission)
    {
        $merchantTax   = $calculator->getMerchantTax();

        $this->assertNotEquals(0, $merchantTax);

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
