<?php

namespace RZP\Tests\Functional\Partner\Commission;

use Illuminate\Database\Eloquent\Factory;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Partner\Commission\Type as CommissionType;

class CommissionCreateTest extends TestCase
{
    use CommissionTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/CommissionCreateTestData.php';

        parent::setUp();

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);
    }

    public function testImplicitVariableOnPaymentCapture()
    {
        $testData = $this->setUpImplicitCommissionCreate();

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'implicit_plan_id'    => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
            ]);

        $this->startTest($testData);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['gateway_captured']);

        $commissions = $this->getCommissionsForSourceEntity($payment['id'])->toArray();

        $this->assertCount(1, $commissions);

        $commission = $commissions[0];

        $this->assertEquals($commission['type'], CommissionType::IMPLICIT);
    }

    public function testImplicitFixedOnPaymentCapture()
    {
        $testData = $this->setUpImplicitCommissionCreate();

        $this->createConfigForPartnerApp(
            Constants::DEFAULT_PLATFORM_APP_ID,
            Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            [
                'implicit_plan_id'    => Pricing::DEFAULT_COMMISSION_PLAN_ID,
            ]);

        $this->startTest($testData);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['gateway_captured']);

        $commissions = $this->getCommissionsForSourceEntity($payment['id'])->toArray();

        $this->assertCount(0, $commissions);

        return;

        $this->assertCount(1, $commissions);

        $commission = $commissions[0];

        $this->assertEquals($commission['type'], CommissionType::IMPLICIT);
    }

    protected function setUpImplicitCommissionCreate()
    {
        $this->createPurePlatFormMerchantAndSubMerchant();

        $this->createImplicitPricingPlan();

        $payment = $this->fixtures->create('payment:authorized', [
            'merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
            'amount'      => 4000 * 100,
        ]);

        $this->createEntityOrigin('payment', $payment->getId());

        $this->setSubmerchantPrivateAuth();

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $testData['request']['content']['amount'] = $payment->getAmount();

        $testData['request']['url'] = '/payments/pay_'.$payment->getId().'/capture';

        return $testData;
    }
}
