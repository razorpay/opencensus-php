<?php

namespace RZP\Tests\Functional\Request;

use Request;
use ApiResponse;
use RZP\Models\Merchant\Entity;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class MerchantIpFilterTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected $mid;
    protected $merchant;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MerchantIpFilterTestData.php';

        parent::setUp();

        $this->mid = '10000000000000';

        $this->merchant  = $this->getDbEntityById('merchant', $this->mid);
    }

    public function testApiGetsExpectedResponseForWhitelistedIps()
    {
        $this->fixtures->edit(
            'merchant',
             $this->mid,
             [Entity::DASHBOARD_WHITELISTED_IPS_TEST => ['127.0.0.1', '192.168.7.5']]);

        $this->ba->setMerchant($this->merchant);

        $this->ba->proxyAuth();

        $this->ba->addXDashboardIpHeader('192.168.7.5');

        $this->startTest();
    }

    public function testApiGetsExpectedResponseForNoWhitelistedIps()
    {
        $this->ba->setMerchant($this->merchant);

        $this->ba->proxyAuth();

        $this->ba->addXDashboardIpHeader('192.168.7.5');

        $this->startTest();
    }

    public function testApiGetsBlockedForNonWhitelistedIps()
    {
        $this->fixtures->edit(
            'merchant',
             $this->mid,
             [Entity::DASHBOARD_WHITELISTED_IPS_TEST => ['127.0.0.1', '192.168.7.6']]);

        $this->ba->setMerchant($this->merchant);

        $this->ba->proxyAuth();

        $this->ba->addXDashboardIpHeader('192.168.7.5');

        $this->startTest();
    }

    public function testApiGetsExpectedResponseForOtherAuths()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }
}
