<?php

namespace RZP\Tests\Functional\TaxPayments;

use App;
use Mockery;
use RZP\Models\Contact\Type;
use RZP\Models\Settings\Accessor;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class TaxPaymentTests extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

    protected $config;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/TaxPaymentsData.php';

        parent::setUp();

        $this->config = App::getFacadeRoot()['config'];

        $this->fixtures->create('feature',
                                [
                                    'name'      => Constants::RX_VENDOR_PAYMENTS,
                                    'entity_id' => '10000000000000'
                                ]);
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

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

        $tpMock->shouldReceive('getAllSettings')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        // assert that the Payout Update Status was called when feature was enabled
        $tpMock->shouldHaveReceived('getAllSettings');
    }

    public function testTaxPaymentSettingAddOrUpdateCallsServiceMethods()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

        $tpMock->shouldReceive('addOrUpdateSettings')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        // assert that the Payout Update Status was called when feature was enabled
        $tpMock->shouldHaveReceived('addOrUpdateSettings');
    }

    public function testGetTaxPaymentCallsServiceMethod()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

        $tpMock->shouldReceive('getTaxPayment')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        // assert that the Payout Update Status was called when feature was enabled
        $tpMock->shouldHaveReceived('getTaxPayment');
    }

    public function testListTaxPaymentCallsServiceMethod()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

        $tpMock->shouldReceive('listTaxPayments')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        // assert that the Payout Update Status was called when feature was enabled
        $tpMock->shouldHaveReceived('listTaxPayments');
    }

    public function testPayTaxPaymentCallsServiceMethod()
    {
        $this->ba->proxyAuth();

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

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

        $tpMock = Mockery::mock('RZP\Services\TaxPayments');

        $tpMock->shouldReceive('bulkPayTaxPayment')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('bulkPayTaxPayment');
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

}
