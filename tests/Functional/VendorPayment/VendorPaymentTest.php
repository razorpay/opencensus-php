<?php

namespace RZP\Tests\Functional\VendorPayment;

use App;
use Mockery;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Admin\Service;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Feature\Constants;
use RZP\Models\User\BankingRole;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\Payout\SourceUpdater\Core as SourceUpdater;

class VendorPaymentTest extends TestCase
{
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

    protected $config;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/VendorPaymentTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->config = App::getFacadeRoot()['config'];
    }

    public function testCompositeExpands()
    {
        $this->app['config']->set('applications.banking_account_service.mock', true);

        // will call the compostie api and check if the response are as expected
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->fixtures->create('user', ['id' => '10000000000000', 'name' => 'test-me']);

        $this->fixtures->create('contact', ['id' => 'Dsp92d4N1Mmm6Q', 'name' => 'test_contact']);

        $this->fixtures->create('fund_account:bank_account',
            [
                'id'          => 'D6Z9Jfir2egAUT',
                'source_type' => 'contact',
                'source_id'   => 'Dsp92d4N1Mmm6Q',
                'merchant_id' => '10000000000000'
            ]);

        $this->fixtures->create('fund_account:bank_account',
                                [
                                    'id'          => 'D6Z9Jfir2egAUD',
                                    'source_type' => 'contact',
                                    'source_id'   => 'Dsp92d4N1Mmm6Q',
                                    'merchant_id' => '10000000000000'
                                ]);

        $this->fixtures->create('payout', [
            'id' => 'DuuYxmO7Yegu3x',
            'fund_account_id' => 'D6Z9Jfir2egAUT',
            'pricing_rule_id' => '1nvp2XPMmaRLxb'
        ]);

        $this->startTest();
    }

    public function testCompositeExpandsWhenOnlyPayoutIsPassed()
    {
        $this->app['config']->set('applications.banking_account_service.mock', true);

        // will call the compostie api and check if the response are as expected
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->fixtures->create('user', ['id' => '10000000000000', 'name' => 'test-me']);

        $this->fixtures->create('contact', ['id' => 'Dsp92d4N1Mmm6Q', 'name' => 'test_contact']);

        $this->fixtures->create('fund_account:bank_account',
            [
                'id'          => 'D6Z9Jfir2egAUT',
                'source_type' => 'contact',
                'source_id'   => 'Dsp92d4N1Mmm6Q',
                'merchant_id' => '10000000000000'
            ]);

        $this->fixtures->create('payout', ['id' => 'DuuYxmO7Yegu3x',
                                         'fund_account_id' => 'D6Z9Jfir2egAUT',
                                         'pricing_rule_id' => '1nvp2XPMmaRLxb']);

        $this->startTest();
    }

    public function testCreatePayout()
    {
        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $payout1 = $this->startTest();

        $payout2 = $this->startTest();

        $this->assertEquals($payout1['id'], $payout2['id']);

    }

    public function testCreateScheduledPayout()
    {
        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $scheduledAtTime = Carbon::now(Timezone::IST)->hour(9)->addMonths(2)->getTimestamp();

        $scheduledAtStartOfHour = Carbon::createFromTimestamp($scheduledAtTime, Timezone::IST)
            ->startOfHour()
            ->getTimestamp();

        $testData = $this->testData['testCreateScheduledPayout'];

        $testData['request']['content']['scheduled_at'] = $scheduledAtTime;

        $testData['response']['content']['scheduled_at'] = $scheduledAtStartOfHour;

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testVendorPaymentBulkCancel()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayments\Service');

        $vpMock->shouldReceive('bulkCancel')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('bulkCancel');
    }

    public function testVendorPaymentGetOcrData()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayments\Service');

        $vpMock->shouldReceive('getOcrData')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('getOcrData');
    }

    public function testVendorPaymentOcrAccuracyCheck()
    {
        $this->ba->appAuthTest($this->config['applications.cron.secret']);

        $vpMock = Mockery::mock('RZP\Services\VendorPayments\Service');

        $vpMock->shouldReceive('ocrAccuracyCheck')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('ocrAccuracyCheck');
    }

    public function testVendorPaymentMarkAsPaid()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayments\Service');

        $vpMock->shouldReceive('markAsPaid')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('markAsPaid');
    }

    public function testVendorPaymentMarkAsPaidNegative()
    {
        $this->ba->proxyAuth();

        $this->ba->setProxyHeader(null);

        $this->startTest();
    }

    public function testVendorPaymentGenericEmailRouteCallsServiceMethod()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $vpMock = Mockery::mock('RZP\Services\VendorPayments\Service');

        $vpMock->shouldReceive('sendMail')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('sendMail');
    }

    public function testUpcomingMailCronRouteCallsServiceMethod()
    {
        $this->ba->appAuthTest($this->config['applications.cron.secret']);

        $vpMock = Mockery::mock('RZP\Services\VendorPayments\Service');

        $vpMock->shouldReceive('sendUpcomingMailCron')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('sendUpcomingMailCron');
    }

    public function testVendorPaymentSendMailValidation()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->startTest();
    }

    public function testVendorPaymentSendMailValidateEmails()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->startTest();
    }

    public function testVendorPaymentBulkExecuteCallsServiceMethods()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayments\Service');

        $vpMock->shouldReceive('executeVendorPaymentBulk')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('executeVendorPaymentBulk');
    }

    public function testGetReportingInfo()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('getReportingInfo')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('getReportingInfo');
    }

    public function testGetReportingInfoFromCARole()
    {
        $user = $this->fixtures->create('user', ['id' => '20000000000006']);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => '10000000000000',
            'role'        => BankingRole::CHARTERED_ACCOUNTANT,
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('getReportingInfo')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('getReportingInfo');
    }

    public function testBulkInvoiceDownload()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('bulkInvoiceDownload')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('bulkInvoiceDownload');
    }

    public function testEditInvoice()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('updateInvoiceFileId')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('updateInvoiceFileId');
    }

    public function testEditInvoiceFromCARole()
    {
        $user = $this->fixtures->create('user', ['id' => '20000000000006']);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => '10000000000000',
            'role'        => BankingRole::CHARTERED_ACCOUNTANT,
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->mockRazorxTreatment();

        $this->startTest();

    }

    public function testGetUfhFileStatus()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('getInvoicesFromUfh')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('getInvoicesFromUfh');
    }

    public function testGetQuickFilterAmounts()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('getQuickFilterAmounts')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('getQuickFilterAmounts');
    }

    public function testProcessIncomingMail()
    {
        $this->ba->appAuth('rzp_test', 'randommailgunsecret');

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('processIncomingMail')->andReturn([
            'status_code' => 406,
            'body' => 'error'
        ]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('processIncomingMail');
    }

    public function testProcessIncomingMailWithoutStatusCode()
    {
        $this->ba->appAuth('rzp_test', 'randommailgunsecret');

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('processIncomingMail')->andReturn([
            'body' => 'error'
        ]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('processIncomingMail');
    }

    public function testProcessIncomingMailSuccess()
    {
        $this->ba->appAuth('rzp_test', 'randommailgunsecret');

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('processIncomingMail')->andReturn([
            'status_code' => 200,
            'body' => [
                'mail' => 'mail_something'
            ]
        ]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('processIncomingMail');
    }

    public function testGetMerchantEmailAddress()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('getMerchantEmailAddress')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('getMerchantEmailAddress');

    }

    public function testCreateMerchantEmailMapping()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('createMerchantEmailMapping')->andReturn([
            'email_address' => 'invoices+abcdef@invoice.razorpay.com'
        ]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('createMerchantEmailMapping');

    }

    public function testGetAutoProcessedInvoice()
    {
        $user = $this->fixtures->create('user', ['id' => '20000000000006']);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => '10000000000000',
            'role'        => BankingRole::OWNER,
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('getAutoProcessedInvoice')->andReturn(
            [
                'created_at' => 1633512478,
                'failure_reason' => '',
                'file_format' => 'application/pdf',
                'file_name' => 'abcde.pdf',
                'file_size' => 42210,
                'invoice_file_id' => 'file_123456',
                'merchant_id' => '10000000000000',
                'ocr_reference_id' => 'ocr_I608S03WojmkBc',
                'status' => 'processed',
                'updated_at' => 1633512478,
                'user_id' => '20000000000006',
                'vendor_payment_id' => 'vdpm_I608cieF0R9bYr'
            ]
        );
        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('getAutoProcessedInvoice');
    }

    public function testSendVendorInvite()
    {
        $user = $this->fixtures->create('user', ['id' => '20000000000006']);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => '10000000000000',
            'role'        => BankingRole::OWNER,
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('inviteVendor')->andReturn([
                                                                            'success' => true
                                                                        ]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('inviteVendor');

    }

    public function testVendorPaymentAccept()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayments\Service');

        $vpMock->shouldReceive('accept')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('accept');
    }

    public function testDisableVendorPortal()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('disableVendorPortal')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('disableVendorPortal');
    }

    public function testEnableVendorPortal()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('enableVendorPortal')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('enableVendorPortal');
    }

    public function testVendorSettlementSingle()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('vendorSettlementSingle')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('vendorSettlementSingle');
    }

    public function testVendorSettlementMultiple()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('vendorSettlementMultiple')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('vendorSettlementMultiple');
    }

    public function testVendorSettlementMarkAsPaid()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('vendorSettlementMarkAsPaid')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('vendorSettlementMarkAsPaid');
    }

    public function testListVendors()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('listVendors')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('listVendors');
    }

    public function testGetFundAccounts()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('getFundAccounts')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('getFundAccounts');
    }

    public function testGetVendorBalance()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('getVendorBalance')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('getVendorBalance');
    }

    public function testExecuteVendorPayment2faRouteCallsServiceMethod()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('executeVendorPayment2fa')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('executeVendorPayment2fa');
    }

    public function testCreateBusinessInfo()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('createBusinessInfo')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('createBusinessInfo');
    }

    public function testGetBusinessInfoStatus()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('getBusinessInfoStatus')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('getBusinessInfoStatus');
    }

    public function testCheckIfInvoiceExistForVendor()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('checkIfInvoiceExistForVendor')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('checkIfInvoiceExistForVendor');
    }

    public function testAddOrUpdateSettings()
    {
        $this->ba->proxyAuth();

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('addOrUpdateSettings')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('addOrUpdateSettings');
    }

    public function testGetSettings()
    {
        $this->ba->proxyAuth('rzp_live_10000000000000');

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('getSettings')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('getSettings');
    }

    public function testApproveRejectInvoice()
    {
        $this->ba->proxyAuth('rzp_live_10000000000000');

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('approveReject')->andReturn([]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('approveReject');
    }

    function testPayoutStatusPushForVendorAdvanceAsSource()
    {
        $vpMock = Mockery::mock('RZP\Services\VendorPayments');

        $vpMock->shouldReceive('pushPayoutStatusUpdate');

        $this->app->instance('vendor-payment', $vpMock);

        $payout = $this->fixtures->create('payout', [
            'status' => 'processed',
            'pricing_rule_id' => '1nvp2XPMmaRLxb',
            'reference_id' => 'reference_id',
            'utr' => 'utr',
            'user_id' => 'user_id',
            'mode' => 'NEFT',
            'narration' => 'narration',
        ]);

        $this->fixtures->create('payout_source', [
            'payout_id' => $payout->getId(),
            'source_id' => 'vda_DummyId',
            'source_type' => 'vendor_advance',
            'priority' => 1
        ]);

        SourceUpdater::update($payout);

        $vpMock->shouldHaveReceived('pushPayoutStatusUpdate');
    }

    public function testCreateVendorAdvance()
    {
        $this->ba->proxyAuth('rzp_live_10000000000000');

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('createVendorAdvance')->andReturn([
            'id' => 'vda_testID'
        ]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('createVendorAdvance');
    }

    public function testGetVendorAdvance()
    {
        $this->ba->proxyAuth('rzp_live_10000000000000');

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('getVendorAdvance')->andReturn([
            'id' => 'vda_testID'
        ]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('getVendorAdvance');
    }

    public function testListVendorAdvance()
    {
        $this->ba->proxyAuth('rzp_live_10000000000000');

        $vpMock = Mockery::mock('RZP\Services\VendorPayment');

        $vpMock->shouldReceive('listVendorAdvances')->andReturn([
            'entity' => 'vendor_advance',
            'count' => 1,
            'items' => [
                [
                    'id' => 'vda_testID',
                ]
            ]
        ]);

        $this->app->instance('vendor-payment', $vpMock);

        $this->startTest();

        $vpMock->shouldHaveReceived('listVendorAdvances');
    }
}
