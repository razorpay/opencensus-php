<?php

namespace Functional\OneClickCheckout;

use Cache;
use RZP\Models\Merchant\OneClickCheckout\Shopify\Service;
use RZP\Tests\Functional\Helpers\MocksRedisTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Feature\Constants as FeatureConstants;

class ShopifyTest extends TestCase {
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;
    use MocksRedisTrait;

    protected function setUp(): void {
        $this->testDataFilePath = __DIR__ . '/ShopifyTestData.php';

        parent::setUp();

        $this->ba->magicConsumerAppAuth();

        $this->fixtures->merchant->enableCoD();

        $this->fixtures->pricing->create([
            'plan_id'        => 'DefaltCodRleId',
            'payment_method' => 'cod',
        ]);

        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);
    }

    public function testCreateOrderAndGetPreferences() {
        $response = $this->startTest();
        $order = $this->getDbLastOrder();
        $this->assertEquals($response['order_id'], $order->getPublicId());
    }

    public function testDuplicatePlaceShopifyOrder()
    {
        $order = $this->fixtures->create('order', ['status' => 'paid', 'receipt' => 'ORDER_PENDING']);
        $payment = $this->fixtures->create('payment', ['status' => 'authorized', 'order_id' => $order->getId()]);

        Cache::shouldReceive('get')
            ->zeroOrMoreTimes()
            ->with('1cc:shopify_order_placed:' . $order->getPublicId())
            ->andReturn(1);
        $service = new Service();
        $res = $service->completeCheckoutWithLock([
            'mode' => 'test',
            'merchant_id' => '10000000000000',
            'razorpay_order_id' => $order->getPublicId(),
            'razorpay_payment_id' => $payment->getPublicId(),
        ], false);

        $this->assertEquals([], $res);
    }
}
