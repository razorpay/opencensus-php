<?php

namespace Functional\Merchant;

use RZP\Tests\Functional\TestCase;
use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Feature\Constants as FeatureConstants;

class ShippingInfoTest extends TestCase
{

    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/ShippingInfoTestData.php';
        parent::setUp();
        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';
        $this->fixtures->create('org:hdfc_org');
        $this->app->make(Factory::class)->load($factoryPath);

    }

    public function testGetShippingInfo()
    {
        $this->ba->publicAuth();
        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);
        $order = $this->fixtures->order->create(['receipt' => 'receipt']);
        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => $this->app['encrypter']->encrypt(['line_items_total' => $order->getAmount()]),
                'type'     => 'one_click_checkout',
            ]);
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();
        $this->fixtures->create(
            'merchant_1cc_configs',
            [
                'merchant_id' => '10000000000000',
                'config'      => 'shipping_info_url',
                'value'       => 'fake.url',
            ]
        );
        $this->runRequestResponseFlow($testData);
    }

    public function testGetShippingInfoForInvalidMerchantResponse()
    {
        $this->ba->publicAuth();
        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);
        $order = $this->fixtures->order->create(['receipt' => 'receipt']);
        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => $this->app['encrypter']->encrypt(['line_items_total' => $order->getAmount()]),
                'type'     => 'one_click_checkout',
            ]);
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['order_id'] = $order->getPublicId();
        $this->fixtures->create(
            'merchant_1cc_configs',
            [
                'merchant_id' => '10000000000000',
                'config'      => 'shipping_info_url',
                'value'       => 'fake.url',
            ]
        );
        $this->runRequestResponseFlow($testData);
    }

    public function testGetShippingInfoWithoutValidOrderId()
    {
        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);
        $this->ba->publicAuth();
        $this->startTest();
    }

}
