<?php

namespace Functional\Merchant;

use Mockery;
use RZP\Error\ErrorCode;
use RZP\Services\PincodeSearch;
use RZP\Models\Order\ProductType;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Exception\BadRequestValidationFailureException;

class ShippingInfoTest extends TestCase
{

    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/ShippingInfoTestData.php';
        parent::setUp();
        $this->fixtures->create('org:hdfc_org');
    }

    public function testGetShippingInfo()
    {
        $this->ba->publicAuth();
        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);
        $order = $this->fixtures->order->create(['receipt' => 'receipt']);
        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => ['line_items_total' => $order->getAmount()],
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

    public function testGetShippingInfoWithFailedZipcodeSearch()
    {
        $this->ba->publicAuth();
        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);
        $order = $this->fixtures->order->create(['receipt' => 'receipt']);
        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => ['line_items_total' => $order->getAmount()],
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

        $mock = Mockery::mock(PincodeSearch::class, [$this->app])->makePartial();

        $mock->shouldAllowMockingMethod('fetchCityAndStateFromPincode')
            ->shouldReceive('fetchCityAndStateFromPincode')
            ->andThrow(new BadRequestValidationFailureException(
                'pincode' . ' is not correct.'));

        $this->app->instance('pincodesearch', $mock);

        $this->runRequestResponseFlow($testData);
    }

//    public function testGetShippingInfoForInvalidMerchantResponse()
//    {
//        $this->ba->publicAuth();
//        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);
//        $order = $this->fixtures->order->create(['receipt' => 'receipt']);
//        $this->fixtures->create('order_meta',
//            [
//                'order_id' => $order->getId(),
//                'value'    => ['line_items_total' => $order->getAmount()],
//                'type'     => 'one_click_checkout',
//            ]);
//        $testData = $this->testData[__FUNCTION__];
//
//        $testData['request']['content']['order_id'] = $order->getPublicId();
//        $this->fixtures->create(
//            'merchant_1cc_configs',
//            [
//                'merchant_id' => '10000000000000',
//                'config'      => 'shipping_info_url',
//                'value'       => 'fake.url',
//            ]
//        );
//        $this->runRequestResponseFlow($testData);
//    }

    public function testGetShippingInfoWithoutValidOrderId()
    {
        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT);
        $this->ba->publicAuth();
        $this->startTest();
    }

    public function testGetShippingInfoForNocodeAppsFeatureNotEnabled()
    {
        $this->ba->publicAuth();
        $order = $this->fixtures->order->create([
            'receipt'       => 'receipt',
            'product_type'  => ProductType::PAYMENT_STORE
        ]);
        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => ['line_items_total' => $order->getAmount()],
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
}
