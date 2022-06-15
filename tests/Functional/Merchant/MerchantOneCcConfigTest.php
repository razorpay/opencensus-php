<?php

namespace Functional\Merchant;

use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\RequestResponseFlowTrait;

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

        $this->startTest();
    }

    public function testOneCcAutoFetchCouponsMerchant1ccConfig()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testOneCcBuyNowMerchant1ccConfig()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }
    public function testOneCcInternationalShippingMerchant1ccConfig()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }
    public function testOneCcCaptureBillingAddressMerchant1ccConfig()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }
    public function testOneCcGaAnalyticsMerchant1ccConfig()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }
    public function testOneCcFbAnalyticsMerchant1ccConfig()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

}
