<?php

namespace RZP\Tests\Functional\Report;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class ReportingTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/ReportingTestData.php';

        parent::setUp();
    }

    public function testMerchantConfigsForInvalidReportType()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGettingPartnerConfigsByNonPartner()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testReportPrivilegeAuth()
    {
        $this->ba->proxyAuth();

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $this->startTest();
    }

    public function testReportXDashboardAuth()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testReportPgDashboardAuth()
    {
        $this->ba->proxyAuth();

        $user = $this->fixtures->create('user');

        $testData['request']['server']['HTTP_X-Dashboard-User-id'] = $user['id'];

        $this->startTest();
    }
}
