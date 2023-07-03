<?php

namespace Functional\Merchant;

use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class OneClickCheckoutAuthConfigTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantTestData.php';
        parent::setUp();
    }

    public function testUpdateMerchant1ccShopifyConfig()
    {
        $this->ba->appAuthTest($this->config['applications.thirdwatch_cod_score.secret']);
        $this->startTest();
    }

    public function testUpdateMerchant1ccShopifyConfigInvalidBody()
    {
        $this->ba->appAuthTest($this->config['applications.thirdwatch_cod_score.secret']);
        $this->startTest();
    }

    public function testGetTestMerchantConfigWithLiveConsumerAppKey()
    {
        $this->ba->publicAuth();
        $key_id = $this->ba->getKey();
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = $testData['request']['url'] . $key_id;

        $this->ba->appAuthLive($this->config['applications.consumer_app.secret']);
        $this->startTest($testData);
    }

    public function testGetLiveMerchantConfigWithLiveConsumerAppKey()
    {
        $this->ba->publicLiveAuth();
        $key_id = $this->ba->getKey();
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = $testData['request']['url'] . $key_id;

        $this->ba->appAuthLive($this->config['applications.consumer_app.secret']);
        $this->startTest($testData);
    }

    public function testGetConfigWithInvalidMerchantKey()
    {
        $key_id = "invalid_key";
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = $testData['request']['url'] . $key_id;

        $this->ba->appAuthLive($this->config['applications.consumer_app.secret']);
        $this->startTest($testData);
    }

    public function testGetConfigWithShopIdAndMode()
    {
        $this->ba->publicAuth();
        $testData = $this->testData[__FUNCTION__];
        $this->setUpAuthConfigForMerchant();
        $this->ba->appAuthLive($this->config['applications.magic_checkout_service.secret']);
        $this->startTest($testData);
    }

    public function testGetConfigWithShopIdAndInvalidMode()
    {
        $this->ba->publicAuth();
        $testData = $this->testData[__FUNCTION__];
        $this->setUpAuthConfigForMerchant();
        $this->ba->appAuthLive($this->config['applications.magic_checkout_service.secret']);
        $this->startTest($testData);
    }

    public function testGetConfigWithMerchantIdAndMode()
    {
        $this->ba->publicAuth();
        $testData = $this->testData[__FUNCTION__];
        $this->setUpAuthConfigForMerchant();
        $this->ba->appAuthLive($this->config['applications.magic_checkout_service.secret']);
        $this->startTest($testData);
    }

    private function setUpAuthConfigForMerchant()
    {
        $this->fixtures->create(
            'merchant_1cc_auth_configs',
            [
                "merchant_id" => "10000000000000",
                "platform" => "shopify",
                "config" => "shop_id",
                "value" => "plugins-store",
            ]
        );
    }
}
