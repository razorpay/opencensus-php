<?php

namespace Functional\Merchant;

use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use Illuminate\Support\Facades\App;
use RZP\Models\Merchant\OneClickCheckout\ShippingProvider\Service;

class MerchantOneCcConfigTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantTestData.php';

        parent::setUp();

        $this->fixtures->create('org:hdfc_org');
    }

    public function testOneClickCheckoutMerchant1ccConfig()
    {
        $this->ba->proxyAuth();
        $this->setUpAuthConfigForMerchant();
        $this->startTest();
    }

    public function testGetOneCcMerchantConfigsForCheckout()
    {
        $this->ba->checkoutServiceProxyAuth();
        $this->fixtures->merchant->addFeatures(Constants::ONE_CLICK_CHECKOUT);
        $this->startTest();
    }


    public function testOneCcAutoFetchCouponsMerchant1ccConfig()
    {
        $this->ba->proxyAuth();
        $this->setUpAuthConfigForMerchant();

        $this->startTest();
    }

    public function testOneCcBuyNowMerchant1ccConfig()
    {

        $this->ba->proxyAuth();
        $this->setUpAuthConfigForMerchant();

        $this->startTest();
    }
    public function testOneCcInternationalShippingMerchant1ccConfig()
    {
        $this->ba->proxyAuth();
        $this->setUpAuthConfigForMerchant();

        $this->startTest();
    }
    public function testOneCcCaptureBillingAddressMerchant1ccConfig()
    {
        $this->ba->proxyAuth();
        $this->setUpAuthConfigForMerchant();
        $this->startTest();
    }
//    public function testOneCcGaAnalyticsMerchant1ccConfig()
//    {
//        $this->ba->proxyAuth();
//        $this->setUpAuthConfigForMerchant();
//        $this->startTest();
//    }
    public function testOneCcFbAnalyticsMerchant1ccConfig()
    {
        $this->ba->proxyAuth();
        $this->setUpAuthConfigForMerchant();
        $this->startTest();
    }


    public function testOneCcGiftCardConfigs() {
        $this->ba->proxyAuth();
        $this->setUpAuthConfigForMerchant();
        $this->startTest();
    }

    public function testDomainUrlMerchant1ccConfig()
    {
        $this->ba->proxyAuth();
        $this->setUpAuthConfigForMerchant();
        $this->startTest();
    }

    public function testCODIntelligenceNativeMerchant1ccConfig()
    {
        $this->ba->proxyAuth();
        $this->setUpAuthConfigForMerchant();
        $this->startTest();
    }

    public function testCODIntelligenceShopifyMerchant1ccConfig()
    {
        $this->ba->proxyAuth();
        $this->setUpAuthConfigForMerchant();
        $this->startTest();
    }

    public function testCODIntelligenceWoocommerceMerchant1ccConfig()
    {
        $this->ba->proxyAuth();
        $this->setUpAuthConfigForMerchant();
        $this->startTest();
    }

    public function testManualControlCodOrderShopifyMerchant1ccConfig()
    {
        $this->ba->proxyAuth();
        $this->setUpAuthConfigForMerchant();
        $this->startTest();
    }

    public function testManualControlCodOrderWoocommerceMerchant1ccConfig()
    {
        $this->ba->proxyAuth();
        $this->setUpAuthConfigForMerchant();
        $this->startTest();
    }

    public function testInvalidAuthWoocommerceMerchant1ccConfig()
    {
        $this->ba->proxyAuth();
        $this->setUpAuthConfigForMerchant();
        $this->startTest();
    }

    public function testManualControlCodOrderNativeMerchant1ccConfig()
    {
        $this->ba->proxyAuth();
        $this->setUpAuthConfigForMerchant();
        $this->startTest();
    }

    public function testInvalidAuthNativeMerchant1ccConfig()
    {
        $this->ba->proxyAuth();
        $this->setUpAuthConfigForMerchant();
        $this->startTest();
    }

    public function testAuthNativeMerchant1ccConfig()
    {
        $this->ba->proxyAuth();
        $this->setUpAuthConfigForMerchant();
        $this->startTest();
    }

    public function testAuthWoocommerceMerchant1ccConfig()
    {
        $this->ba->proxyAuth();
        $this->setUpAuthConfigForMerchant();
        $this->startTest();
    }

    public function testOrderNotesMerchant1ccConfig(){
        $this->ba->proxyAuth();
        $this->setUpAuthConfigForMerchant();
        $this->startTest();
    }

    public function testOneCcShippingInfoUrlMerchant1ccConfig() {
        $this->ba->proxyAuth();
        $this->setUpAuthConfigForMerchant();
        $this->setUpShippingServiceMock();
        $this->startTest();
    }

    private function setUpShippingServiceMock() {
        $shippingServiceMock = $this->getMockBuilder(Service::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['create'])
            ->getMock();

        $this->app->instance('shipping_provider_service', $shippingServiceMock);

        $shippingServiceMock->expects($this->any())->method('create')->willReturn([]);
    }

    private function setUpAuthConfigForMerchant()
    {
        $app = App::getFacadeRoot();
        $this->fixtures->create(
            'merchant_1cc_auth_configs',
            [
                "merchant_id" => "10000000000000",
                "platform" => "shopify",
                "config" => "shop_id",
                "value" => "hias",
            ]
        );

        $this->fixtures->create(
            'merchant_1cc_auth_configs',
            [
                "merchant_id" => "10000000000000",
                "platform" => "shopify",
                "config" => "api_key",
                "value" => "abasc",
            ]
        );

        $this->fixtures->create(
            'merchant_1cc_auth_configs',
            [
                "merchant_id" => "10000000000000",
                "platform" => "shopify",
                "config" => "api_secret",
                "value" => $app['encrypter']->encrypt("234fg")
            ]
        );

        $this->fixtures->create(
            'merchant_1cc_auth_configs',
            [
                "merchant_id" => "10000000000000",
                "platform" => "shopify",
                "config" => "oauth_token",
                "value" => $app['encrypter']->encrypt("shpca_ba"),
            ]
        );

        $this->fixtures->create(
            'merchant_1cc_auth_configs',
            [
                "merchant_id" => "10000000000000",
                "platform" => "shopify",
                "config" => "storefront_access_token",
                "value" => $app['encrypter']->encrypt("41bffd")
            ]
        );
    }

}
