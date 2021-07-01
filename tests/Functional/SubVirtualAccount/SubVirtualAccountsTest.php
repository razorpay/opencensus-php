<?php

namespace RZP\Tests\Functional\SubVirtualAccount;

use Hash;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Feature\Constants as Features;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class SubVirtualAccountsTest extends TestCase
{
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/SubVirtualAccountsTestData.php';

        parent::setUp();

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->fixtures->merchant->addFeatures([Features::SUB_VIRTUAL_ACCOUNT]);
    }

    public function testCreateSubVirtualAccount()
    {
        $this->ba->adminAuth();

        $this->fixtureSetUpForSubVirtualAccount();

        $this->startTest();
    }

    public function testCreateDuplicateSubVirtualAccount()
    {
        $this->ba->adminAuth();

        $this->testCreateSubVirtualAccount();

        $this->startTest();
    }

    public function testCreateSubVirtualAccountWhereMasterAccountNumberMissingInDB()
    {
        $this->ba->adminAuth();

        $this->fixtureSetUpForSubVirtualAccount();

        $this->startTest();
    }

    public function testCreateSubVirtualAccountWhereSubAccountNumberMissingInDB()
    {
        $this->ba->adminAuth();

        $this->fixtureSetUpForSubVirtualAccount();

        $this->startTest();
    }

    public function testCreateSubVirtualAccountWithInvalidAccountType()
    {
        $this->ba->adminAuth();

        $this->fixtureSetUpForSubVirtualAccount( '2323230041626906', 'banking', 'direct');

        $this->startTest();
    }

    public function testCreateSubVirtualAccountWithInvalidType()
    {
        $this->ba->adminAuth();

        $this->fixtureSetUpForSubVirtualAccount( '2323230041626906', 'primary');

        $this->startTest();
    }

    public function testFetchSubVirtualAccountsForAdmin()
    {
        $this->ba->adminAuth();

        $this->fixtures->create('sub_virtual_account', ['id' => 'HM8yTa58wo3qRZ']);

        $this->fixtures->create('sub_virtual_account', ['id' => 'HM8yTa58wo3qRY', 'active' => false]);

        $this->startTest();
    }

    public function testFetchSubVirtualAccountsForProxy()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('sub_virtual_account', ['id' => 'HM8yTa58wo3qRZ']);

        $this->fixtures->create('sub_virtual_account', ['id' => 'HM8yTa58wo3qRY', 'active' => false]);

        $this->startTest();
    }

    public function testDisableSubVirtualAccount()
    {
        $this->ba->adminAuth();

        $this->fixtures->create('sub_virtual_account', ['id' => 'HM8yTa58wo3qRZ', 'active' => true]);

        $this->startTest();
    }

    public function testEnableSubVirtualAccount()
    {
        $this->ba->adminAuth();

        $this->fixtures->create('sub_virtual_account', ['id' => 'HM8yTa58wo3qRZ', 'active' => false]);

        $this->startTest();
    }

    public function testEnableSubVirtualAccountWithInvalidId()
    {
        $this->ba->adminAuth();

        $this->fixtures->create('sub_virtual_account', ['id' => 'HM8yTa58wo3qRZ']);

        $this->startTest();
    }

    protected function fixtureSetUpForSubVirtualAccount(
        $subAccountNumber = '2323230041626906',
        $type = 'banking',
        $accountType = 'shared')
    {
        $this->fixtures->on('test')->create('merchant', ['id' => '100abc000abc01', 'business_banking' => 1, 'live' => 1]);

        $this->fixtures->on('test')->create('balance',
            [
                'type'           => $type,
                'account_type'   => $accountType,
                'account_number' => $subAccountNumber,
                'merchant_id'    => '100abc000abc01',
                'balance'        => 0
            ]);
    }

    public function testSubVirtualAccountTransferWithOtp()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('sub_virtual_account', ['id' => 'HM8yTa58wo3qRZ']);

        $this->fixtureSetUpForSubVirtualAccount();

        $this->startTest();
    }

    public function testSubVirtualAccountTransferWithInvalidOtp()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('sub_virtual_account', ['id' => 'HM8yTa58wo3qRZ']);

        $this->fixtureSetUpForSubVirtualAccount();

        $this->startTest();
    }

    public function testSubVirtualAccountTransferWithSubMerchantBusinessBankingNotEnabled()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('sub_virtual_account', ['id' => 'HM8yTa58wo3qRZ']);

        $this->fixtureSetUpForSubVirtualAccount();

        $this->fixtures->edit('merchant', '100abc000abc01', ['business_banking' => 0]);

        $this->startTest();
    }

    public function testSubVirtualAccountTransferWithInactiveSubVirtualAccount()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('sub_virtual_account', ['id' => 'HM8yTa58wo3qRZ', 'active' => false]);

        $this->fixtureSetUpForSubVirtualAccount();

        $this->startTest();
    }

    public function testSubVirtualAccountTransferWithMasterFundsOnHold()
    {
        $this->fixtures->edit('merchant', '10000000000000', ['hold_funds' => 1]);

        $this->ba->proxyAuth();

        $this->fixtures->create('sub_virtual_account', ['id' => 'HM8yTa58wo3qRZ']);

        $this->fixtureSetUpForSubVirtualAccount();

        $this->startTest();
    }

    public function testSubVirtualAccountTransferWithInvalidMasterAccountNumber()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('sub_virtual_account', ['id' => 'HM8yTa58wo3qRZ']);

        $this->fixtureSetUpForSubVirtualAccount();

        $this->startTest();
    }

    public function testSubVirtualAccountTransferWithInvalidSubAccountNumber()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('sub_virtual_account', ['id' => 'HM8yTa58wo3qRZ']);

        $this->fixtureSetUpForSubVirtualAccount();

        $this->startTest();
    }

    public function testSubVirtualAccountTransferWithInsufficientBalance()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('sub_virtual_account', ['id' => 'HM8yTa58wo3qRZ']);

        $this->fixtureSetUpForSubVirtualAccount();

        $this->startTest();
    }

    public function testSubVirtualAccountTransferWithExceedingMaxAmountOfTransfer()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('sub_virtual_account', ['id' => 'HM8yTa58wo3qRZ']);

        $this->fixtureSetUpForSubVirtualAccount();

        $this->startTest();
    }

    public function testSubVirtualAccountTransferWithMasterMerchantNotLive()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('sub_virtual_account', ['id' => 'HM8yTa58wo3qRZ']);

        $this->fixtureSetUpForSubVirtualAccount();

        $this->fixtures->edit('merchant', '10000000000000', ['live' => 0]);

        $this->startTest();
    }

    public function testSubVirtualAccountTransferWithSubMerchantNotLive()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('sub_virtual_account', ['id' => 'HM8yTa58wo3qRZ']);

        $this->fixtureSetUpForSubVirtualAccount();

        $this->fixtures->edit('merchant', '100abc000abc01', ['live' => 0]);

        $this->startTest();
    }

    public function testSubVirtualAccountTransferWithFeatureNotAssigned()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('sub_virtual_account', ['id' => 'HM8yTa58wo3qRZ']);

        $this->fixtures->merchant->removeFeatures([Features::SUB_VIRTUAL_ACCOUNT]);

        $this->fixtureSetUpForSubVirtualAccount();

        $this->startTest();
    }
}
