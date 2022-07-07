<?php

namespace Functional\Merchant;

use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use Illuminate\Support\Facades\App;

class MerchantOneCcConfigTest extends TestCase
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

    public function testOneClickCheckoutMerchant1ccConfig()
    {
        $this->ba->proxyAuth();
        $this->setUpAuthConfigForMerchant();
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
    public function testOneCcGaAnalyticsMerchant1ccConfig()
    {
        $this->ba->proxyAuth();
        $this->setUpAuthConfigForMerchant();
        $this->startTest();
    }
    public function testOneCcFbAnalyticsMerchant1ccConfig()
    {
        $this->ba->proxyAuth();
        $this->setUpAuthConfigForMerchant();
        $this->startTest();
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
