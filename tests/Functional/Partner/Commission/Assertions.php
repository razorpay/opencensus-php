<?php

namespace RZP\Tests\Functional\Partner\Commission;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Partner\Commission\Calculator;
use RZP\Tests\Functional\Helpers\PrivateMethodTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class Assertions extends TestCase
{
    use DbEntityFetchTrait;
    use PrivateMethodTrait;

    const GST_RATE = 18;

    public function testImplicitVariable(array $data)
    {
        $postAction = $data['post_action'];

        $calculator = $postAction['calculator'];

        $this->assertBasicCalculatorRules($calculator);

        $commissions = $calculator->getCommissions();

        $amount          = 400000; // INR 4000
        $merchantPricing = 2; // 2% pricing

        $this->assertEquals($this->getFee($amount, $merchantPricing), $calculator->getMerchantFee());
        $this->assertEquals($this->getTax($amount, $merchantPricing), $calculator->getMerchantTax());

        $this->assertEquals(1, count($commissions));
        $this->assertEquals(944, $commissions[0]->fee);
        $this->assertEquals(144, $commissions[0]->tax);
    }

    public function testImplicitVariableMultiplePricingRules(array $data)
    {
        $postAction = $data['post_action'];

        $calculator = $postAction['calculator'];

        $this->assertBasicCalculatorRules($calculator);

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

    public function testImplicitPricingDoesNotExist(array $data)
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

    protected function assertBasicCalculatorRules(Calculator $calculator)
    {
        $shouldCreateCommission = $this->invokePrivateMethod(
                                    $calculator,
                                    Calculator::class,
                                    'shouldCreateCommission');

        $this->assertTrue($shouldCreateCommission);

        $commissionFee = $calculator->getCommissionFee();
        $commissionTax = $calculator->getCommissionTax();
        $merchantFee   = $calculator->getMerchantFee();
        $merchantTax   = $calculator->getMerchantFee();
        $partnerFee    = $calculator->getPartnerFee();
        $partnerTax    = $calculator->getPartnerTax();

        $this->assertNotNull($calculator->getPartnerConfig());

        $this->assertNotEquals(0, $merchantFee);
        $this->assertNotEquals(0, $partnerFee);
        $this->assertNotEquals($partnerFee, $merchantFee);
        $this->assertNotEquals(0, $merchantTax);

        $this->assertNotEquals(0, $partnerTax);
        $this->assertNotEquals($partnerTax, $merchantTax);
        $this->assertNotEquals(0, $commissionTax);

        $this->assertTrue($commissionFee < $merchantFee);
        $this->assertTrue($commissionTax < $merchantTax);

        $this->assertNotEmpty($calculator->getCommissions());
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

    protected function getFee(int $amount, int $rate)
    {
        return ($this->getFeeWithoutTax($amount, $rate) + $this->getTax($amount, $rate));
    }

    protected function getFeeWithoutTax(int $amount, int $rate)
    {
        return ($amount * ($rate / 100));
    }

    protected function getTax(int $amount, int $rate)
    {
        return ($this->getFeeWithoutTax($amount, $rate) * self::GST_RATE / 100);
    }
}
