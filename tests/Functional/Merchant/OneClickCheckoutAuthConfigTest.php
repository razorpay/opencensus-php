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

}
