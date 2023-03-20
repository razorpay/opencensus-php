<?php

namespace RZP\Tests\Functional\Report;

use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Tests\Functional\RequestResponseFlowTrait;


class ReportingTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
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

    public function testPGReportLogForInvalidEmails()
    {
        $user = (new User())->createUserForMerchant('10000000000000', [
            'email' => 'test3@razorpay.com'
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->mockRazorxTreatment();

        $this->startTest();
    }

    public function testPGReportLogForValidEmails()
    {
        $this->ba->proxyAuth();

        $this->mockRazorxTreatment();

        $this->startTest();
    }

    public function testRXReportLogForInvalidEmails()
    {
        $user = (new User())->createBankingUserForMerchant('10000000000000', [
            'email' => 'test2@razorpay.com',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->mockRazorxTreatment();

        $this->startTest();
    }

    public function testRXReportLogSkipEmailValidation()
    {
        $user = (new User())->createBankingUserForMerchant('10000000000000', [
            'email' => 'test2@razorpay.com',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->mockRazorxTreatment();

        $this->startTest();
    }

    public function testRXReportLogForValidEmails()
    {
        $user = (new User())->createBankingUserForMerchant('10000000000000', [
            'email' => 'test2@razorpay.com',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->mockRazorxTreatment();

        $this->startTest();
    }

    public function testRXReportingLogForValidMasterAndSubMerchantIds()
    {
        $user = (new User())->createBankingUserForMerchant('10000000000000', [
            'email' => 'test2@razorpay.com',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->mockRazorxTreatment();

        $this->fixtures->create('sub_virtual_account', [
            "master_merchant_id" => '10000000000000',
            "sub_account_type"   => 'sub_direct_account',
            "sub_merchant_id"    => 'sub_merchant_1'
        ]);

        $this->startTest();
    }

    public function testRXReportingLogForValidMasterMerchantAndInvalidSubMerchantIds()
    {
        $user = (new User())->createBankingUserForMerchant('10000000000000', [
            'email' => 'test2@razorpay.com',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->mockRazorxTreatment();

        $this->fixtures->create('sub_virtual_account', [
            "master_merchant_id" => '10000000000000',
            "sub_account_type"   => 'sub_direct_account',
            "sub_merchant_id"    => 'sub_merchant_1'
        ]);

        $this->fixtures->create('sub_virtual_account', [
            "master_merchant_id" => '10000000000000',
            "sub_account_type"   => 'sub_direct_account',
            "sub_merchant_id"    => 'sub_merchant_2'
        ]);

        $this->startTest();
    }

    public function testRXReportingLogForInvalidMasterMerchant()
    {
        $user = (new User())->createBankingUserForMerchant('10000000000000', [
            'email' => 'test2@razorpay.com',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->mockRazorxTreatment();

        $this->fixtures->create('sub_virtual_account', [
            "master_merchant_id" => '10000000000001',
            "sub_account_type"   => 'sub_direct_account',
            "sub_merchant_id"    => 'sub_merchant_1'
        ]);

        $this->fixtures->create('sub_virtual_account', [
            "master_merchant_id" => '10000000000001',
            "sub_account_type"   => 'sub_direct_account',
            "sub_merchant_id"    => 'sub_merchant_2'
        ]);

        $this->startTest();
    }

    /*
     * Here payer_merchant_id in filter is not the merchant who is requesting the report.
     * Hence we throw an "Access Denied" error.
     */
    public function testRXReportingLogForInvalidPayerMerchantIdInFilters()
    {
        $user = (new User())->createBankingUserForMerchant('10000000000000', [
            'email' => 'test2@razorpay.com',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->mockRazorxTreatment();

        $this->fixtures->create('sub_virtual_account', [
            "master_merchant_id" => '10000000000000',
            "sub_account_type"   => 'sub_direct_account',
            "sub_merchant_id"    => 'sub_merchant_1'
        ]);

        $this->fixtures->create('sub_virtual_account', [
            "master_merchant_id" => '10000000000000',
            "sub_account_type"   => 'sub_direct_account',
            "sub_merchant_id"    => 'sub_merchant_2'
        ]);

        $this->startTest();
    }

    /*
     * Here, the account_numbers provided in the filters does not belong to the sub merchants.
     * Thus, in this case also, we will throw an "Access Denied" error.
     */
    public function testRXReportingLogForInvalidAccountNumbersInFilters()
    {
        $user = (new User())->createBankingUserForMerchant('10000000000000', [
            'email' => 'test2@razorpay.com',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->mockRazorxTreatment();

        $this->fixtures->create('sub_virtual_account', [
            "master_merchant_id" => '10000000000000',
            "sub_account_type"   => 'sub_direct_account',
            "sub_merchant_id"    => 'sub_merchant_1',
            "sub_account_number" => '343411111111'
        ]);

        $this->fixtures->create('sub_virtual_account', [
            "master_merchant_id" => '10000000000000',
            "sub_account_type"   => 'sub_direct_account',
            "sub_merchant_id"    => 'sub_merchant_2',
            "sub_account_number" => '343422222222',
        ]);

        $this->startTest();
    }

    /*
     * Here the payer_merchant_id is correct hence request goes through
     */
    public function testRXReportingLogForValidPayerMerchantIdInFilters()
    {
        $user = (new User())->createBankingUserForMerchant('10000000000000', [
            'email' => 'test2@razorpay.com',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->mockRazorxTreatment();

        $this->fixtures->create('sub_virtual_account', [
            "master_merchant_id" => '10000000000000',
            "sub_account_type"   => 'sub_direct_account',
            "sub_merchant_id"    => 'sub_merchant_1'
        ]);

        $this->fixtures->create('sub_virtual_account', [
            "master_merchant_id" => '10000000000000',
            "sub_account_type"   => 'sub_direct_account',
            "sub_merchant_id"    => 'sub_merchant_2'
        ]);

        $testData['request'] = $this->testData['testRXReportingLogForInvalidPayerMerchantIdInFilters']['request'];

        $testData['request']['content']['template_overrides']['filters']['credit_transfers']['payer_merchant_id']['values'] = ['10000000000000'];

        $testData['response'] = $this->testData['testRXReportingLogForValidMasterAndSubMerchantIds']['response'];

        $this->startTest($testData);
    }

    public function testPGReportLogEditForInvalidEmails()
    {
        $user = (new User())->createUserForMerchant('10000000000000', [
            'email' => 'test3@razorpay.com'
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->mockRazorxTreatment();

        $this->startTest();
    }

    public function testPGReportLogEditForValidEmails()
    {
        $this->ba->proxyAuth();

        $this->mockRazorxTreatment();

        $this->startTest();
    }

    public function testRXReportLogEditForInvalidEmails()
    {
        $user = (new User())->createBankingUserForMerchant('10000000000000', [
            'email' => 'test2@razorpay.com',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->mockRazorxTreatment();

        $this->startTest();
    }

    public function testRXReportLogEditForValidEmails()
    {
        $user = (new User())->createBankingUserForMerchant('10000000000000', [
            'email' => 'test2@razorpay.com',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->mockRazorxTreatment();

        $this->startTest();
    }

    public function testConfigCreateForInvalidFeatures()
    {
        $this->fixtures->create('feature', [
            'name'        => 'feature1',
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testConfigCreateWithAdminAuthValid()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testConfigCreateWithAdminAuthInvalid()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testConfigUpdateWithAdminAuthValid()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testConfigDeleteWithAdminAuthValid()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    protected function mockRazorxTreatment(string $returnValue = 'on')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn($returnValue);
    }
}
