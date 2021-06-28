<?php

namespace RZP\Tests\Functional\SubVirtualAccount;

use Hash;

use RZP\Tests\Functional\TestCase;
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
        $this->fixtures->on('test')->create('merchant', ['id' => '100abc000abc01', 'email' => 'mahbubani.amit@gmail.com']);

        $this->fixtures->on('test')->create('balance',
            [
                'type'           => $type,
                'account_type'   => $accountType,
                'account_number' => $subAccountNumber,
                'merchant_id'    => '100abc000abc01',
                'balance'        => 0
            ]);
    }
}
