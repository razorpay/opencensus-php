<?php

namespace Functional\Merchant;

use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use Illuminate\Support\Facades\App;

class MerchantGiftCardPromotionsTest extends TestCase
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

    public function testApplyGiftCard()
    {
        $this->setUpMerchantForGiftCardRequest();

        $testData = $this->testData[__FUNCTION__];

        $order = $this->fixtures->order->create(['receipt' => 'receipt', 'amount' => 50000, 'currency' => 'INR']);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => ['line_items_total' => $order->getAmount()],
                'type'     => 'one_click_checkout',
            ]);

        $this->ba->publicAuth();

        $testData['request']['url'] = '/1cc/orders/'.$order->getPublicId().'/giftcard/apply';

        $this->runRequestResponseFlow($testData);
    }

    public function testApplyGiftCardInvalidGiftCardNumber()
    {
        $this->setUpMerchantForGiftCardRequest();

        $testData = $this->testData[__FUNCTION__];

        $order = $this->fixtures->order->create(['receipt' => 'receipt', 'amount' => 50000, 'currency' => 'INR']);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => ['line_items_total' => $order->getAmount()],
                'type'     => 'one_click_checkout',
            ]);

        $this->ba->publicAuth();

        $testData['request']['url'] = '/1cc/orders/'.$order->getPublicId().'/giftcard/apply';

        $this->runRequestResponseFlow($testData);
    }

    public function testRemoveGiftCard() {

        $this->setUpMerchantForGiftCardRequest();

        $testData = $this->testData[__FUNCTION__];

        $order = $this->fixtures->order->create(['receipt' => 'receipt', 'amount' => 50000, 'currency' => 'INR']);

        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => ['line_items_total' => $order->getAmount()],
                'type'     => 'one_click_checkout',
            ]);

        $this->ba->publicAuth();

        $testData['request']['url'] = '/1cc/orders/'.$order->getPublicId().'/giftcard/remove';

        $this->runRequestResponseFlow($testData);
    }

    private function setUpMerchantForGiftCardRequest()
    {
        $this->fixtures->merchant->addFeatures(Constants::ONE_CLICK_CHECKOUT);

        $this->fixtures->create(
            'merchant_1cc_configs',
            [
                'merchant_id' => '10000000000000',
                'config'      => 'platform',
                'value'       => 'woocommerce',
            ]
        );

        $this->fixtures->create(
            'merchant_1cc_configs',
            [
                'merchant_id' => '10000000000000',
                'config'      => 'one_cc_gift_card',
                'value'       => true,
            ]
        );

        $this->fixtures->create(
            'merchant_1cc_configs',
            [
                'merchant_id' => '10000000000000',
                'config'      => 'domain_url',
                'value'       => 'abc.fake.com',
            ]
        );
    }
}
