<?php

namespace Functional\Partner\Commission;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Partner\Commission\Calculator;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class Assertions extends TestCase
{
    use DbEntityFetchTrait;

    public function testImplicitVariable(array $data)
    {
        $postAction = $data['post_action'];

        $calculator = $postAction['calculator'];

        $this->assertBasicCalculatorRules($calculator);

        $commissions = $calculator->getCommissions();

        $this->assertEquals(1, count($commissions));
        $this->assertEquals(944, $commissions[0]->fee);
        $this->assertEquals(144, $commissions[0]->tax);
    }

    public function testPartnerConfigDoesNotExist(array $data)
    {
        $postAction = $data['post_action'];

        $calculator = $postAction['calculator'];

        $this->assertShouldNotCreateCommission($calculator);
    }

    protected function assertBasicCalculatorRules(Calculator $calculator)
    {
        $this->assertTrue($calculator->shouldCreateCommission());

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

    protected function assertShouldNotCreateCommission(Calculator $calculator)
    {
        $this->assertFalse($calculator->shouldCreateCommission());
    }
}
