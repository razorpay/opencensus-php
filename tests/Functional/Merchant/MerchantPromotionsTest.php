<?php

namespace Functional\Merchant;

use Mockery;
use RZP\Error\ErrorCode;
use RZP\Exception\ServerErrorException;
use RZP\Models\Feature\Constants;
use RZP\Models\Merchant\OneClickCheckout\MagicCheckoutService\Client;
use RZP\Constants\Environment;
use RZP\Constants\Mode;
use RZP\Models\Merchant\MerchantPromotions\Service;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class MerchantPromotionsTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantTestData.php';

        parent::setUp();

        $this->fixtures->create('org:hdfc_org');
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

    public function testApplyCouponWithCxService503()
    {
        $client = Mockery::mock(client::class, [$this->app])->makePartial();
        $response = new \stdClass();
        $response->status_code = 503;

        $client->shouldAllowMockingMethod('makeRequest')
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('makeRequest')
            ->andReturn(
                $response
            );

        try
        {
            $client->sendRequest('', [], 'POST');
        }
        catch (\Throwable $e)
        {
            $this->assertExceptionClass($e, ServerErrorException::class);
            $errorcode = $e->getError()->getInternalErrorCode();
            $this->assertEquals($errorcode, ErrorCode::GATEWAY_ERROR_REQUEST_ERROR);
        }
    }

    public function testCanRouteToMagicCXInProd()
    {
        $this->app['env'] = Environment::PRODUCTION;
        $this->app['rzp.mode'] = Mode::TEST;
        $merchant = $this->fixtures->create('merchant');
        $res = (new Service())->canRouteToMagicCheckoutService([], $merchant);
        $this->assertFalse($res);
    }

    private function setUpMerchantForCouponsRequest()
    {
        $this->fixtures->merchant->addFeatures(Constants::ONE_CLICK_CHECKOUT);
    }
}
