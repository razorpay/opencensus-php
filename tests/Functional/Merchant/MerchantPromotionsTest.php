<?php

namespace Functional\Merchant;

use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class MerchantPromotionsTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantTestData.php';

        parent::setUp();

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->fixtures->create('org:hdfc_org');

        $this->app->make(Factory::class)->load($factoryPath);
    }

    public function testFetchCoupons()
    {
        $this->setUpMerchantForCouponsRequest();

        $testData = $this->testData[__FUNCTION__];

        $order = $this->fixtures->order->create(['receipt' => 'receipt', 'amount' => 1000, 'currency' => 'INR']);
        $testData['request']['content']['order_id'] = $order->getPublicId();

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => ['line_items_total' => $order->getAmount()],
                'type'     => 'one_click_checkout',
            ]);

        $this->ba->publicAuth();
        $this->fixtures->create(
            'merchant_1cc_configs',
            [
                'merchant_id' => '10000000000000',
                'config'      => 'fetch_coupons_url',
                'value'       => 'fake.url',
            ]
        );

        $this->runRequestResponseFlow($testData);
    }

    public function testFetchCouponsNoCouponsAvailable()
    {
        $this->setUpMerchantForCouponsRequest();

        $testData = $this->testData[__FUNCTION__];

        $order = $this->fixtures->order->create(['receipt' => 'receipt', 'amount' => 1000, 'currency' => 'INR']);
        $testData['request']['content']['order_id'] = $order->getPublicId();

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => ['line_items_total' => $order->getAmount()],
                'type'     => 'one_click_checkout',
            ]);

        $this->ba->publicAuth();
        $this->fixtures->create(
            'merchant_1cc_configs',
            [
                'merchant_id' => '10000000000000',
                'config'      => 'fetch_coupons_url',
                'value'       => 'fake.url',
            ]
        );

        $this->runRequestResponseFlow($testData);
    }

    public function testFetchCouponsURLNotConfigured()
    {
        $this->setUpMerchantForCouponsRequest();

        $testData = $this->testData[__FUNCTION__];

        $order = $this->fixtures->order->create(['receipt' => 'receipt', 'amount' => 1000, 'currency' => 'INR']);
        $testData['request']['content']['order_id'] = $order->getPublicId();

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => ['line_items_total' => $order->getAmount()],
                'type'     => 'one_click_checkout',
            ]);
        $this->ba->publicAuth();
        $this->runRequestResponseFlow($testData);
    }

    public function testApplyCouponValidCoupon()
    {
        $this->setUpMerchantForCouponsRequest();

        $testData = $this->testData[__FUNCTION__];

        $order = $this->fixtures->order->create(['receipt' => 'receipt', 'amount' => 1000, 'currency' => 'INR']);
        $testData['request']['content']['order_id'] = $order->getPublicId();

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => ['line_items_total' => $order->getAmount()],
                'type'     => 'one_click_checkout',
            ]);

        $this->ba->publicAuth();
        $this->fixtures->create(
            'merchant_1cc_configs',
            [
                'merchant_id' => '10000000000000',
                'config'      => 'apply_coupon_url',
                'value'       => 'fake.url',
            ]
        );
        $this->runRequestResponseFlow($testData);
    }

    public function testApplyCouponInvalidCoupon()
    {
        $this->setUpMerchantForCouponsRequest();

        $testData = $this->testData[__FUNCTION__];

        $order = $this->fixtures->order->create(['receipt' => 'receipt', 'amount' => 1000, 'currency' => 'INR']);
        $testData['request']['content']['order_id'] = $order->getPublicId();

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => ['line_items_total' => $order->getAmount()],
                'type'     => 'one_click_checkout',
            ]);

        $this->ba->publicAuth();
        $this->fixtures->create(
            'merchant_1cc_configs',
            [
                'merchant_id' => '10000000000000',
                'config'      => 'apply_coupon_url',
                'value'       => 'fake.url',
            ]
        );

        $this->runRequestResponseFlow($testData);
    }

    public function testCouponValidityURLNotConfigured()
    {
        $this->setUpMerchantForCouponsRequest();

        $testData = $this->testData[__FUNCTION__];

        $order = $this->fixtures->order->create(['receipt' => 'receipt', 'amount' => 1000, 'currency' => 'INR']);
        $testData['request']['content']['order_id'] = $order->getPublicId();

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => ['line_items_total' => $order->getAmount()],
                'type'     => 'one_click_checkout',
            ]);

        $this->ba->publicAuth();

        $this->runRequestResponseFlow($testData);
    }

    private function setUpMerchantForCouponsRequest()
    {
        $this->fixtures->merchant->addFeatures(Constants::ONE_CLICK_CHECKOUT);
    }
}
