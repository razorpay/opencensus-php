<?php

namespace RZP\Tests\Functional\TaxPayments;

use Mockery;

use App;
use RZP\Constants\Mode;
use RZP\Constants\Product;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Contact\Type;
use RZP\Models\Payout\Status;
use RZP\Models\Payout\Purpose;
use RZP\Models\Settings\Module;
use RZP\Models\Settings\Accessor;
use RZP\Models\User\BankingRole;
use RZP\Models\User\Role;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class TaxPaymentsTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

    protected $config;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/TaxPaymentsData.php';

        parent::setUp();

        $this->config = App::getFacadeRoot()['config'];

        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    public function testSettingsInternalApiAddOrUpdate()
    {
        // for vendor-payment internal auth
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->startTest();

        // you should also call the GET API and check if the value was actually updated or not?
        // no just check by calling the Accessors
        $merchant = $this->getDbEntity('merchant', ['id' => '10000000000000']);

        $keys = Accessor::for($merchant, 'tax_payments')->all();

        $this->assertEquals($keys['test_key'], 'test_value');
    }

    public function testSettingsInternalApiGet()
    {
        // calling POST api to create the settings keys
        $this->testSettingsInternalAPIAddOrUpdate();

        // for vendor-payment internal auth
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->startTest();
    }

    public function testTaxPaymentSettingGetCallsServiceMethods()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('getAllSettings')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        // assert that the Payout Update Status was called when feature was enabled
        $tpMock->shouldHaveReceived('getAllSettings');
    }

    public function testTaxPaymentSettingAddOrUpdateCallsServiceMethods()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('addOrUpdateSettings')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        // assert that the Payout Update Status was called when feature was enabled
        $tpMock->shouldHaveReceived('addOrUpdateSettings');
    }

    public function testTaxPaymentSettingAddOrUpdateForAutoTdsWithAdminRole()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $user = $this->fixtures->create('user', [
            'id' => '20000000000000',
        ]);

        $this->fixtures->create('user:user_merchant_mapping', [
            'user_id'     => $user['id'],
            'merchant_id' => $merchantId,
            'role'        => 'admin',
            'product'     => 'primary',
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $user['id']);

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('addOrUpdateSettingsForAutoTds')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('addOrUpdateSettingsForAutoTds');
    }

    public function testTaxPaymentSettingAddOrUpdateForAutoTdsWithOwnerRole()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $user = $this->fixtures->create('user', [
            'id' => '20000000000000',
        ]);

        $this->fixtures->create('user:user_merchant_mapping', [
            'user_id'     => $user['id'],
            'merchant_id' => $merchantId,
            'role'        => 'owner',
            'product'     => 'primary',
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $user['id']);

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('addOrUpdateSettingsForAutoTds')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('addOrUpdateSettingsForAutoTds');
    }

    public function testTaxPaymentSettingAddOrUpdateForAutoTdsWithFinanceRole()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $user = $this->fixtures->create('user', [
            'id' => '20000000000000',
        ]);

        $this->fixtures->create('user:user_merchant_mapping', [
            'user_id'     => $user['id'],
            'merchant_id' => $merchantId,
            'role'        => 'finance',
            'product'     => 'primary',
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $user['id']);

        $this->startTest();
    }

    public function testTaxPaymentSettingAddOrUpdateForNonAutoTdsWithOwnerRole()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $user = $this->fixtures->create('user', [
            'id' => '20000000000000',
        ]);

        $this->fixtures->create('user:user_merchant_mapping', [
            'user_id'     => $user['id'],
            'merchant_id' => $merchantId,
            'role'        => 'owner',
            'product'     => 'primary',
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $user['id']);

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('addOrUpdateSettings')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('addOrUpdateSettings');
    }

    public function testTaxPaymentSettingAddOrUpdateForNonAutoTdsWithAdminRole()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $user = $this->fixtures->create('user', [
            'id' => '20000000000000',
        ]);

        $this->fixtures->create('user:user_merchant_mapping', [
            'user_id'     => $user['id'],
            'merchant_id' => $merchantId,
            'role'        => 'admin',
            'product'     => 'primary',
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $user['id']);

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('addOrUpdateSettings')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('addOrUpdateSettings');
    }

    public function testTaxPaymentSettingAddOrUpdateForNonAutoTdsWithFinanceRole()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantId = $merchantDetail['merchant_id'];

        $user = $this->fixtures->create('user', [
            'id' => '20000000000000',
        ]);

        $this->fixtures->create('user:user_merchant_mapping', [
            'user_id'     => $user['id'],
            'merchant_id' => $merchantId,
            'role'        => 'finance',
            'product'     => 'primary',
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId, $user['id']);

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('addOrUpdateSettings')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('addOrUpdateSettings');
    }

    public function testGetTaxPaymentCallsServiceMethod()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('getTaxPayment')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        // assert that the Payout Update Status was called when feature was enabled
        $tpMock->shouldHaveReceived('getTaxPayment');
    }

    public function testListTaxPaymentCallsServiceMethod()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('listTaxPayments')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        // assert that the Payout Update Status was called when feature was enabled
        $tpMock->shouldHaveReceived('listTaxPayments');
    }

    public function testPayTaxPaymentCallsServiceMethod()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('payTaxPayment')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('payTaxPayment');
    }

    /**
     * This is to test that the Tax Payment Internal Contact can only be created from VendorPayments app
     *
     */
    public function testTaxPayContactCreationFailsWhenNotVendorPaymentApp()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testTaxContactCreationSuccessWithTheRightVendorApp()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->startTest();
    }

    public function testTaxPayFundAccountCreationFailsWhenNotVendorPaymentApp()
    {
        // first create an internal contact
        $contact = $this->fixtures->create('contact',
                                           [
                                               'name' => 'some test name',
                                               'type' => Type::TAX_PAYMENT_INTERNAL_CONTACT
                                           ]);

        $this->testData[__FUNCTION__]['request']['content']['contact_id'] = $contact->getPublicId();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testTaxFundAccountCreationSuccessWithTheRightVendorApp()
    {
        $contact = $this->fixtures->create('contact',
                                           [
                                               'name' => 'some test name',
                                               'type' => Type::TAX_PAYMENT_INTERNAL_CONTACT
                                           ]);

        $this->testData[__FUNCTION__]['request']['content']['contact_id'] = $contact->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['contact_id'] = $contact->getPublicId();

        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->startTest();
    }

    public function testTaxPaymentInternalContactUpdateForbidden()
    {
        $contact = $this->fixtures->create('contact',
                                           [
                                               'name' => 'some test name',
                                               'type' => Type::TAX_PAYMENT_INTERNAL_CONTACT
                                           ]);

        $this->testData[__FUNCTION__]['request']['url'] .= $contact->getPublicId();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testUpdatingContactTypeToInternalContactForbidden()
    {
        $contact = $this->fixtures->create('contact',
                                           [
                                               'name' => 'some test name',
                                               'type' => 'employee'
                                           ]);

        $this->testData[__FUNCTION__]['request']['url'] .= $contact->getPublicId();

        $this->ba->proxyAuth();

        $this->startTest();
    }

    // this one calls the route that allows creating payouts on internal contacts
    public function testPayoutCreateOnRzpInternalContactSucceeds()
    {
        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $contact = $this->fixtures->create('contact', ['type' => 'rzp_tax_pay']);

        $fundAccount = $this->fixtures->fund_account->createBankAccount(
            [
                'source_type' => 'contact',
                'source_id'   => $contact->getId(),
            ],
            [
                'name'           => 'test',
                'ifsc'           => 'SBIN0007105',
                'account_number' => '111000',
            ]);

        $this->testData[__FUNCTION__]['request']['content']['fund_account_id'] = $fundAccount->getPublicId();

        $this->startTest();
    }

    public function testBulkPayTaxPaymentCallsServiceMethod()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('bulkPayTaxPayment')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('bulkPayTaxPayment');
    }

    public function testInternalPayoutCancelAPI()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $payout = $this->fixtures->create('payout', ['status' => 'queued', 'pricing_rule_id' => '1nvp2XPMmaRLxb']);

        $this->testData[__FUNCTION__]['request']['url'] = sprintf('/payouts_internal/%s/cancel', $payout->getPublicId());

        $this->startTest();

        $payout = $this->getDbEntity('payout', ['id' => $payout->getId()]);

        $this->assertEquals(Status::CANCELLED, $payout->getStatus());

    }

    public function testQueuedPayoutCronAPICallsServiceMethod()
    {
        $this->ba->appAuthTest($this->config['applications.cron.secret']);

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('cancelQueuedPayouts')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('cancelQueuedPayouts');

    }

    public function testPayoutInternalPayoutRouteFailsWhenFundAccountIdMissing()
    {
        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->startTest();
    }

    public function testPayoutInternalPayoutRouteFailsWhenContactIsNotInternalType()
    {
        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $contact = $this->fixtures->create('contact', ['type' => 'employee']); // not an internal type

        $fundAccount = $this->fixtures->fund_account->createBankAccount(
            [
                'source_type' => 'contact',
                'source_id'   => $contact->getId(),
            ],
            [
                'name'           => 'test',
                'ifsc'           => 'SBIN0007105',
                'account_number' => '111000',
            ]);

        $this->testData[__FUNCTION__]['request']['content']['fund_account_id'] = $fundAccount->getPublicId();

        $this->startTest();
    }

    public function testInternalPayoutFailsWhenInternalContactIsRestrictedForCurrentApp()
    {
        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $contact = $this->fixtures->create('contact', ['type' => 'rzp_fees']); // not mapped to this internal app

        $fundAccount = $this->fixtures->fund_account->createBankAccount(
            [
                'source_type' => 'contact',
                'source_id'   => $contact->getId(),
            ],
            [
                'name'           => 'test',
                'ifsc'           => 'SBIN0007105',
                'account_number' => '111000',
            ]);

        $this->testData[__FUNCTION__]['request']['content']['fund_account_id'] = $fundAccount->getPublicId();

        $this->startTest();
    }

    public function testEnabledMerchantSettingInternalApiCall()
    {
        $this->setupEnabledMerchantSettingTests();

        $this->startTest();
    }

    public function testEnabledMerchantSettingInternalApiCallWithLimit()
    {
        $this->setupEnabledMerchantSettingTests();

        $this->startTest();
    }

    public function testEnabledMerchantSettingInternalApiCallWithOffset()
    {
        $this->setupEnabledMerchantSettingTests();

        $this->startTest();
    }

    public function setupEnabledMerchantSettingTests()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $m1 = $this->fixtures->create('merchant', ['id' => '200DemoAccount']);

        $xBalance1 = $this->fixtures->create('balance',
                                             [
                                                 'merchant_id'    => $m1->getId(),
                                                 'type'           => 'banking',
                                                 'account_type'   => 'shared',
                                                 'account_number' => '2224440041626905',
                                                 'balance'        => 200,
                                             ]);

        $ba1 = $this->fixtures->create('banking_account',
                                       [
                                           'account_number'        => '2224440041626905',
                                           'account_type'          => 'current',
                                           'merchant_id'           => $m1->getId(),
                                           'channel'               => 'yesbank',
                                           'status'                => 'created',
                                           'balance_id'            => $xBalance1->getId(),
                                           'pincode'               => '1',
                                           'bank_reference_number' => '',
                                           'account_ifsc'          => 'RATN0000156',
                                       ]);

        $m2 = $this->fixtures->create('merchant', ['id' => '201DemoAccount']);

        $m3 = $this->fixtures->create('merchant', ['id' => '202DemoAccount']);

        $this->createTestSettingsForMerchant($m1->getId(), [
            'tax_payment_enabled'                => "true",
            'merchant_auto_debit_account_number' => $ba1->getAccountNumber(),
        ]);

        $this->createTestSettingsForMerchant($m2->getId(), [
            'tax_payment_enabled'                => "true",
            'merchant_auto_debit_account_number' => 'm2_account',
        ]);

        $this->createTestSettingsForMerchant($m3->getId(), [
            'tax_payment_enabled'                => "false", // as this is false, the settings for this should not be returned
            'merchant_auto_debit_account_number' => 'm2_account',
        ]);
    }

    public function createTestSettingsForMerchant($merchantId, $settings)
    {
        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Razorpay-Account'] = $merchantId;

        $this->testData[__FUNCTION__]['request']['content'] = $settings;

        $this->startTest();
    }

    public function testInitiateMonthlyPayoutsCallsServiceMethod()
    {
        $this->ba->cronAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('initiateMonthlyPayouts')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('initiateMonthlyPayouts');
    }

    public function testUpcomingEmailCronCallsServiceMethod()
    {
        $this->ba->appAuthTest($this->config['applications.cron.secret']);

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('mailCron')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('mailCron');
    }

    public function testSendMailServiceMethodIsCalled()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('sendMail')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('sendMail');
    }

    public function testMonthlySummaryServiceMethodIsCalled()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('monthlySummary')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('monthlySummary');
    }

    public function testSendEmailValidation()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->startTest();
    }

    public function testSendEmailDataFieldRequired()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->startTest();
    }

    public function testSendEmailSubjectFieldRequired()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->startTest();
    }

    public function testSendEmailTemplateFieldRequired()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->startTest();
    }

    public function testPayoutWithTaxPaymentPurposeCanBeDeleted()
    {
        $this->ba->proxyAuth();
        // create a payout with this purpose and call payout delete and there should not be any exception
        $payout = $this->fixtures->create('payout',
                                          [
                                              'purpose'         => Purpose::RZP_TAX_PAYMENT,
                                              'status'          => Status::QUEUED,
                                              'pricing_rule_id' => '1nvp2XPMmaRLxb'
                                          ]);

        $this->testData[__FUNCTION__]['request']['url'] = sprintf('/payouts/%s/cancel', $payout->getPublicId());

        $this->startTest();
    }

    public function testTaxPaymentMarkAsPaid()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

        $tpMock->shouldReceive('markAsPaid')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('markAsPaid');
    }

    public function testTaxPaymentUploadChallan()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

        $tpMock->shouldReceive('uploadChallan')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('uploadChallan');
    }

    public function testTaxPaymentEditTp()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

        $tpMock->shouldReceive('updateChallanFileId')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('updateChallanFileId');
    }

    public function testbulkChallanDownload()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

        $tpMock->shouldReceive('bulkChallanDownload')->andReturn(['zip_file_id' => "file_HjPPzIGMCbahkO"]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('bulkChallanDownload');
    }

    public function testTaxPaymentMarkAsPaidNegative()
    {
        $this->ba->proxyAuth();

        $this->ba->setProxyHeader(null);

        $this->startTest();
    }

    public function testGetInternalMerchantWhenNoSettingsPresent()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->startTest();
    }

    public function testGetInternalMerchantWhenSettingsArePresent()
    {
        $merchant = $this->getDbEntity('merchant', ['id' => '10000000000000']);

        $settingAccessor = Accessor::for($merchant, Module::PAYOUT_LINK);

        $settingAccessor->upsert('support_email', 'test@email.com')->save();

        $settingAccessor->upsert('support_url', 'test.com')->save();

        $settingAccessor->upsert('support_contact', '1234')->save();

        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->startTest();
    }

    public function testTaxPaymentAddPenaltyCronCallsServiceMethod()
    {
        $this->ba->cronAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

        $tpMock->shouldReceive('addPenalty')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('addPenalty');
    }

    public function testTaxPaymentCreateTPCallsServiceMethod()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

        $tpMock->shouldReceive('create')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('create');
    }

    public function testTaxPaymentEditTPCallsServiceMethod()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

        $tpMock->shouldReceive('edit')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('edit');
    }

    public function testTaxPaymentCancelTPCallsServiceMethod()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

        $tpMock->shouldReceive('cancel')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('cancel');
    }

    public function testCreateDirectTaxPaymentCallsServiceMethod()
    {
        $this->ba->directAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

        $tpMock->shouldReceive('createDirectTaxPayment')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('createDirectTaxPayment');
    }

    public function testGetTdsCategoriesCallsServiceMethod()
    {
        $this->ba->directAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

        $tpMock->shouldReceive('getTdsCategories')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('getTdsCategories');
    }

    public function testWebHookHandlerCallsServiceMethod()
    {
        $this->ba->directAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

        $tpMock->shouldReceive('webHookHandler')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('webHookHandler');
    }

    public function testGetInvalidTanStatus()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

        $tpMock->shouldReceive('getInvalidTanStatus')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('getInvalidTanStatus');
    }

    public function testGetDowntimeSchedule()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('listDowntimeSchedule')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('listDowntimeSchedule');
    }

    public function testGetDTPConfig()
    {
        $this->ba->directAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('getDTPConfig')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('getDTPConfig');
    }

    public function testGetDTPConfigErrorCase()
    {
        $this->ba->directAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('getDTPConfig')->andThrow(new BadRequestException(ErrorCode::BAD_REQUEST_VENDOR_PAYMENT_MICRO_SERVICE_FAILED));

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('getDTPConfig');
    }

    public function testDowntimeScheduleByModule()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('getDowntimeSchedule')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('getDowntimeSchedule');
    }

    public function testReminderCallback()
    {
        $this->ba->appAuth('rzp_test', 'api');

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('reminderCallback')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('reminderCallback');
    }

    public function testFetchPendingGstCallsServiceMethod()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

        $tpMock->shouldReceive('fetchPendingGstPayments')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('fetchPendingGstPayments');
    }

    public function testTaxPaymentSettingGetCallsServiceMethodsForCARole()
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

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('getAllSettings')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('getAllSettings');
    }

    public function testListTaxPaymentCallsServiceMethodForCARole()
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

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('listTaxPayments')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('listTaxPayments');
    }

    public function testTaxPaymentEditTPCallsServiceMethodForCARole()
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

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

        $this->mockRazorxTreatment();

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();
    }

    public function testGetTaxPaymentCallsServiceMethodForCARole()
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

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('getTaxPayment')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('getTaxPayment');
    }
}
