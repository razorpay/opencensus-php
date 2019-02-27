<?php

namespace Functional\Partner\Commission;
use Illuminate\Database\Eloquent\Factory;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Fixtures\Entity\Pricing;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class CommissionCreateTest extends TestCase
{
    use PartnerTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/CommissionCreateTestData.php';

        parent::setUp();

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);
    }

    public function testOnPaymentCapture()
    {
        $this->createPurePlatFormMerchantAndSubMerchant();

        $this->createImplicitPricingPlan();

        $payment = $this->fixtures->create('payment:authorized', [
            'merchant_id' => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
        ]);

        $this->fixtures->create(
            'partner_config',
            [
                'entity_type'         => 'merchant',
                'entity_id'           => Constants::DEFAULT_PLATFORM_SUBMERCHANT_ID,
                'origin_type'         => 'application',
                'origin_id'           => Constants::DEFAULT_PLATFORM_APP_ID,
                'default_plan_id'     => Pricing::DEFAULT_PRICING_PLAN_ID,
                'implicit_plan_id'    => Constants::DEFAULT_IMPLICIT_PRICING_PLAN,
                'commissions_enabled' => 1,
            ]
        );

        $this->fixtures->create(
            'entity_origin',
            [
                'entity_type'     => 'payment',
                'entity_id'       => $payment->getId(),
                'origin_type'     => 'application',
                'origin_id'       => Constants::DEFAULT_PLATFORM_APP_ID,
            ]
        );

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['amount'] = $payment->getAmount();

        $testData['request']['url'] = '/payments/pay_'.$payment->getId().'/capture';

        $this->setSubmerchantPrivateAuth();

        $this->startTest($testData);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['gateway_captured']);
    }
}
