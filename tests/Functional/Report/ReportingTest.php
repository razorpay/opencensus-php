<?php

namespace RZP\Tests\Functional\Report;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

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
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);
        
        // This is required, because this is going to on board the merchant on X on the test mode
        // which requires the terminal entity to be present
        $this->fixtures->create('terminal:bank_account_terminal_for_business_banking',
                                ['merchant_id' => '100000Razorpay']);

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
