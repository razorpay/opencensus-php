<?php

namespace Functional\OneClickCheckout;

use Cache;
use RZP\Models\Merchant\OneClickCheckout\Shopify\Service;
use RZP\Tests\Functional\Helpers\MocksRedisTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\OneClickCheckout\MagicCheckoutService\Client;
use Illuminate\Support\Facades\App;

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

    public function testFetchMetaFieldsApi()
    {
        $this->ba->adminAuth();

        $this->setUpAuthConfigForMerchant();

        $magicCheckoutServiceClientMock = $this->getMockBuilder(Client::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['sendRequest'])
            ->getMock();

        $this->app->instance('magic_checkout_service_client', $magicCheckoutServiceClientMock);

        $magicCheckoutServiceClientMock->expects($this->once())
            ->method('sendRequest')
            ->willReturn([
                'meta_fields' => [
                    [
                        'id'    => 'gid://shopify/Metafield/28810825924884',
                        'key'   => 'test_key',
                        'value' => 'test_value',
                        'type'  => 'string',
                    ],
                ],
            ]);

        $this->startTest();
    }

    public function testUpdateMetaFieldsApi()
    {
        $this->ba->adminAuth();

        $this->setUpAuthConfigForMerchant();

        $magicCheckoutServiceClientMock = $this->getMockBuilder(Client::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['sendRequest'])
            ->getMock();

        $this->app->instance('magic_checkout_service_client', $magicCheckoutServiceClientMock);

        $magicCheckoutServiceClientMock->expects($this->once())
            ->method('sendRequest')
            ->willReturn([
                'errors' => [],
            ]);

        $this->startTest();
    }

    public function testFetchShopifyStoreThemes()
    {
        $this->ba->adminAuth();

        $this->setUpAuthConfigForMerchant();

        $magicCheckoutServiceClientMock = $this->getMockBuilder(Client::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['sendRequest'])
            ->getMock();

        $this->app->instance('magic_checkout_service_client', $magicCheckoutServiceClientMock);

        $magicCheckoutServiceClientMock->expects($this->once())
            ->method('sendRequest')
            ->willReturn([
                'shop_id' => 'random-shop',
                'themes'  => [
                    [
                        'id'                   => 138358325524,
                        'name'                 => 'Dawn',
                        'created_at'           => '2022-11-18T00:53:52+05:30',
                        'updated_at'           => '2023-04-14T23:48:41+05:30',
                        'role'                 => 'main',
                        'theme_store_id'       => 887,
                        'previewable'          => true,
                        'processing'           => false,
                        'admin_graphql_api_id' => 'gid://shopify/Theme/138358325524',
                    ],
                ],
            ]);

        $this->startTest();
    }

    public function testInsertShopifySnippetApi()
    {
        $this->ba->adminAuth();

        $this->setUpAuthConfigForMerchant();

        $magicCheckoutServiceClientMock = $this->getMockBuilder(Client::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['sendRequest'])
            ->getMock();

        $this->app->instance('magic_checkout_service_client', $magicCheckoutServiceClientMock);

        $magicCheckoutServiceClientMock->expects($this->once())
            ->method('sendRequest')
            ->willReturn([
                'success' => true,
            ]);

        $this->startTest();
    }

    public function testRenderMagicSnippetApi()
    {
        $this->ba->adminAuth();

        $this->setUpAuthConfigForMerchant();

        $magicCheckoutServiceClientMock = $this->getMockBuilder(Client::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['sendRequest'])
            ->getMock();

        $this->app->instance('magic_checkout_service_client', $magicCheckoutServiceClientMock);

        $magicCheckoutServiceClientMock->expects($this->once())
            ->method('sendRequest')
            ->willReturn([
                'success' => true,
            ]);

        $this->startTest();
    }

    private function setUpAuthConfigForMerchant()
    {
        $app = App::getFacadeRoot();
        $this->fixtures->create(
            'merchant_1cc_auth_configs',
            [
                'merchant_id' => '10000000000000',
                'platform'    => 'shopify',
                'config'      => 'shop_id',
                'value'       => 'random-shop',
            ]
        );

        $this->fixtures->create(
            'merchant_1cc_auth_configs',
            [
                'merchant_id' => '10000000000000',
                'platform'    => 'shopify',
                'config'      => 'oauth_token',
                'value'       => $app['encrypter']->encrypt("shpca_ba"),
            ]
        );
    }
}
