<?php

namespace Functional\ThirdWatch;

use RZP\Services\ThirdWatchService;
use RZP\Models\Merchant;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class ThirdWatchClientTest extends TestCase
{
    use RequestResponseFlowTrait;

    private $cache;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/ThirdWatchClientTestData.php';
        parent::setUp();

        $this->cache = $this->app['cache'];
    }

    // Invalid payload
    public function testTWAddressServiceabilityWithInvalidInput()
    {
        $this->ba->publicAuth();

        $this->startTest();
    }

    // Kafka push fails or ThirdWatch service timeout
    public function testTWAddressServiceabilityWithThirdWatchCallTimeout()
    {
        $this->ba->publicAuth();

        $this->startTest();
    }

    // ThirdWatch sends address as valid for cod
    public function testTWAddressServiceabilityWithValidAddress()
    {
        $entityId = 'id_should_pass';

        $twCacheKey = ThirdWatchService::ADDRESS_VALIDITY_CACHE_KEY_PREFIX . ':' . $entityId;

        $twResponse = ['score' => 0.82, 'label' => 'green', 'id' => $entityId];

        $this->cache->put($twCacheKey, $twResponse, ThirdWatchService::ADDRESS_COD_VALIDITY_TTL);

        $this->ba->publicAuth();

        $this->startTest();
    }

    // ThirdWatch sends address as invalid for cod
    public function testTWAddressServiceabilityWithInvalidAddress()
    {
        $entityId = 'id_should_fail';

        $twCacheKey = ThirdWatchService::ADDRESS_VALIDITY_CACHE_KEY_PREFIX . ':' . $entityId;

        $twResponse = ['score' => 0.22, 'label' => 'red', 'id' => $entityId];

        $this->cache->put($twCacheKey, $twResponse, ThirdWatchService::ADDRESS_COD_VALIDITY_TTL);

        $this->ba->publicAuth();

        $this->startTest();
    }

    // ThirdWatch sends invalid payload
    public function testTWAddressServiceabilityCallbackInvalidInput()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    // ThirdWatch post req is successful
    public function testTWAddressServiceabilityCallback()

    {
        $this->markTestSkipped("No live traffic.");
        $this->ba->privateAuth('rzp_test', getenv("THIRDWATCH_COD_SCORE_SERVICE_SECRET"));
        $this->startTest();

    }

}
