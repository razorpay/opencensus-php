<?php

namespace RZP\Tests\Functional\SubVirtualAccount;

use Hash;
use Mockery;
use Carbon\Carbon;

use RZP\Services\RazorXClient;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Tests\Functional\TestCase;
use RZP\Services\Dcs\Features\Service;
use RZP\Models\SubVirtualAccount\Metric;
use RZP\Models\SubVirtualAccount\Constants;
use RZP\Models\Feature\Constants as Features;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class SubVirtualAccountsTest extends TestCase
{
    use TestsMetrics;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/SubVirtualAccountsTestData.php';

        parent::setUp();

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->fixtures->merchant->addFeatures([Features::SUB_VIRTUAL_ACCOUNT]);

        $this->app['config']->set('applications.dcs.mock', false);
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
        $subMerchant = $this->fixtures->on('test')->create('merchant', [
            'id'               => '100abc000abc01',
            'business_banking' => 1,
            'live'             => 1,
        ]);

        $subBalance = $this->fixtures->on('test')->create('balance', [
            'type'           => $type,
            'account_type'   => $accountType,
            'account_number' => $subAccountNumber,
            'merchant_id'    => '100abc000abc01',
            'balance'        => 0
        ]);

        $this->fixtures->on('test')->create('banking_account', [
            'merchant_id' => '100abc000abc01',
            'balance_id' => $subBalance->getId(),
            'account_type' => $accountType,
        ]);

        $this->fixtures->on('test')->create('bank_account', [
            'id'               => 'bnk10000000000',
            'beneficiary_name' => 'Test Tester',
            'ifsc_code'        => 'SBIN0007105',
            'account_number'   => '2345678901',
            'merchant_id'      => '100abc000abc01',
        ]);

        $this->fixtures->on('test')->create('virtual_account', [
            'id'          => 'virtual1000000',
            'merchant_id' => '100abc000abc01',
            'status'      => 'active',
            'balance_id'  => $subBalance->getId(),
            'bank_account_id' => 'bnk10000000000',
        ]);

        $directMasterBalance = $this->fixtures->on('test')->create('balance', [
            'id'             => random_alphanum_string(14),
            'merchant_id'    => '10000000000000',
            'type'           => 'banking',
            'account_number' => '2323230041626907',
            'account_type'   => 'direct',
            'channel'        => 'rbl'
        ]);

        return [$subMerchant, $subBalance, $directMasterBalance];
    }

    public function testSubVirtualAccountTransferWithOtp()
    {
        $this->ba->proxyAuth();

        [$subMerchant, $subBalance, $directMasterBalance] = $this->fixtureSetUpForSubVirtualAccount();

        $sharedMasterBalance = $this->fixtures->create('balance', [
            'merchant_id' => '10000000000000',
            'balance' => 1000000,
            'account_number' => '2224440041626905',
            'type' => 'banking',
            'account_type' => 'shared',
        ]);

        $this->fixtures->create('sub_virtual_account', [
            'id' => 'HM8yTa58wo3qRZ',
            'master_balance_id' => $sharedMasterBalance->getId(),
            'master_account_number' => $sharedMasterBalance->getAccountNumber(),
            'sub_merchant_id' => $subMerchant->getId(),
            'sub_account_number' => $subBalance->getAccountNumber(),
        ]);

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

        [$subMerchant, $subBalance, $directMasterBalance] = $this->fixtureSetUpForSubVirtualAccount();

        $sharedMasterBalance = $this->getDbEntity('balance', ['merchant_id' => '10000000000000', 'account_type' => 'shared']);

        $this->fixtures->create('sub_virtual_account', [
            'id'                 => 'HM8yTa58wo3qRZ',
            'sub_merchant_id'    => $subMerchant->getId(),
            'master_merchant_id' => $directMasterBalance->getMerchantId(),
            'master_balance_id'  => $sharedMasterBalance->getId(),
            'sub_account_number' => $subBalance->getAccountNumber(),
        ]);

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

    public function testCreateSubVirtualAccountTransferWhenMasterMerchantNotLiveButXVAActivated()
    {
        $this->fixtures->merchant->edit('10000000000000', ['live' => false]);

        [$subMerchant, $subBalance, $directMasterBalance] = $this->fixtureSetUpForSubVirtualAccount();

        $this->fixtures->create('merchant_attribute', [
            'merchant_id' => '10000000000000',
            'product'     => 'banking',
            'group'       => 'products_enabled',
            'type'        => 'X',
            'value'       => 'true'
        ]);

        $sharedMasterBalance = $this->fixtures->create('balance', [
           'merchant_id' => '10000000000000',
           'balance' => 1000000,
           'account_number' => '2224440041626905',
           'type' => 'banking',
           'account_type' => 'shared',
        ]);

        $this->fixtures->create('sub_virtual_account', [
            'id' => 'HM8yTa58wo3qRZ',
            'master_balance_id' => $sharedMasterBalance->getId(),
            'master_account_number' => $sharedMasterBalance->getAccountNumber(),
            'sub_merchant_id' => $subMerchant->getId(),
            'sub_account_number' => $subBalance->getAccountNumber(),
        ]);

        $testData = $this->testData['testSubVirtualAccountTransferWithOtp'];

        $this->ba->proxyAuth();

        $this->startTest($testData);
    }

    public function testCreateSubVirtualAccountTransferWhenSubMerchantNotLiveButXVAActivated()
    {
        [$subMerchant, $subBalance, $masterDirectBalance] = $this->fixtureSetUpForSubVirtualAccount();

        $this->fixtures->merchant->edit($subMerchant->getId(), ['live' => false]);

        $this->fixtures->create('merchant_attribute', [
            'merchant_id' => $subMerchant->getId(),
            'product'     => 'banking',
            'group'       => 'products_enabled',
            'type'        => 'X',
            'value'       => 'true'
        ]);

        $sharedMasterBalance = $this->fixtures->create('balance', [
            'merchant_id' => '10000000000000',
            'balance' => 1000000,
            'account_number' => '2224440041626905',
            'type' => 'banking',
            'account_type' => 'shared',
        ]);

        $this->fixtures->create('sub_virtual_account', [
            'id' => 'HM8yTa58wo3qRZ',
            'master_balance_id' => $sharedMasterBalance->getId(),
            'master_account_number' => $sharedMasterBalance->getAccountNumber(),
            'sub_merchant_id' => $subMerchant->getId(),
            'sub_account_number' => $subBalance->getAccountNumber(),
        ]);

        $testData = $this->testData['testSubVirtualAccountTransferWithOtp'];

        $this->ba->proxyAuth();

        $this->startTest($testData);
    }

    public function testCreateSubVirtualAccountForAccountSubAccountFlow()
    {
        $this->ba->adminAuth();

        $this->fixtureSetUpForSubVirtualAccount();

        $this->fixtures->merchant->addFeatures([Features::CAPITAL_CARDS, Features::CAPITAL_CARDS_ELIGIBLE], '100abc000abc01');

        $this->fixtures->merchant->removeFeatures([Features::SUB_VIRTUAL_ACCOUNT]);

        $masterMerchant = $this->getDbEntityById('merchant', '10000000000000');

        $this->mockRazorXForDCS("on_direct_dcs");

        $this->mockDcs();

        $this->startTest();

        $subMerchant = $this->getDbEntityById('merchant', '100abc000abc01');
        $virtualAccount = $this->getDbLastEntity('virtual_account');
        $subVirtualAccount = $this->getDbLastEntity('sub_virtual_account');

        $this->assertEquals('sub_direct_account', $subVirtualAccount->getSubAccountType());
        $this->assertEquals('100abc000abc01', $subVirtualAccount->getSubMerchantId());

        $this->assertEquals('100abc000abc01', $virtualAccount->getMerchantId());
        $this->assertEquals('closed', $virtualAccount->getStatus());
        $this->assertTrue($subMerchant->isFeatureEnabled(Features::ASSUME_SUB_ACCOUNT));
        $this->assertTrue($subMerchant->isFeatureEnabled(Features::BLOCK_FAV));
        $this->assertTrue($subMerchant->isFeatureEnabled(Features::DISABLE_X_AMAZONPAY));
        $this->assertFalse($subMerchant->isFeatureEnabled(Features::CAPITAL_CARDS));
        $this->assertFalse($subMerchant->isFeatureEnabled(Features::CAPITAL_CARDS_ELIGIBLE));

        //The feature flag is being replaced by another feature
        $this->assertFalse($masterMerchant->isFeatureEnabled(Features::SUB_VIRTUAL_ACCOUNT));
        $this->assertTrue($masterMerchant->isFeatureEnabled(Features::ASSUME_MASTER_ACCOUNT));

        return $subVirtualAccount;
    }

    public function testCreateSubVirtualAccountWithDuplicateSubAccountNumberForAccountSubAccountFlow()
    {
        $this->testCreateSubVirtualAccountForAccountSubAccountFlow();

        $this->startTest();
    }

    public function testCreateSubVirtualAccountForAccountSubAccountFlowWithInvalidType()
    {
        $this->ba->adminAuth();

        $this->fixtureSetUpForSubVirtualAccount();

        $this->startTest();

        $subVirtualAccount = $this->getDbLastEntity('sub_virtual_account');

        $this->assertNull($subVirtualAccount);
    }

    public function testCreateSubVirtualAccountWhenMasterMerchantHasFeatureEnabled()
    {
        $this->ba->adminAuth();

        $this->fixtureSetUpForSubVirtualAccount();

        $this->startTest();

        $subVirtualAccount = $this->getDbLastEntity('sub_virtual_account');

        $this->assertNull($subVirtualAccount);
    }

    public function testCreateSubVirtualAccountForAccountSubAccountFlowWhenSubMerchantSharedBalanceIsNonZero()
    {
        $this->ba->adminAuth();

        $this->fixtureSetUpForSubVirtualAccount();

        $this->fixtures->merchant->removeFeatures([Features::SUB_VIRTUAL_ACCOUNT]);

        $subMerchantBalance = $this->getDbEntity('balance', ['merchant_id' => '100abc000abc01']);

        $this->fixtures->edit('balance', $subMerchantBalance->getId(), ['balance' => 1000]);

        $this->startTest();
    }

    public function testCreateSubVirtualAccountForAccountSubAccountFlowWhenMasterMerchantNotLiveButXVAActivated()
    {
        $this->fixtures->merchant->edit('10000000000000', ['live' => false]);

        $this->fixtures->create('merchant_attribute', [
            'merchant_id' => '10000000000000',
            'product'     => 'banking',
            'group'       => 'products_enabled',
            'type'        => 'X',
            'value'       => 'true'
        ]);

        $this->testCreateSubVirtualAccountForAccountSubAccountFlow();
    }

    /*
     * There exists only 1 mapping for a master merchant.
     * Assert block_va_payouts on the sub_merchant is enabled along with existing sub merchant features
     * Assert sub_virtual_account feature on master merchant is disabled
     */
    public function testDisableSubVirtualAccountOfTypeSubDirectAccount()
    {
        $subVirtualAccount = $this->testCreateSubVirtualAccountForAccountSubAccountFlow();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = "/admin/sub_virtual_accounts/subva_" . $subVirtualAccount->getId();

        $this->startTest($testData);

        $masterMerchant = $this->getDbEntityById('merchant', $subVirtualAccount->getMasterMerchantId());

        $subMerchant = $this->getDbEntityById('merchant', $subVirtualAccount->getSubMerchantId());

        $this->assertTrue($subMerchant->isFeatureEnabled(Features::BLOCK_FAV));
        $this->assertTrue($subMerchant->isFeatureEnabled(Features::ASSUME_SUB_ACCOUNT));
        $this->assertTrue($subMerchant->isFeatureEnabled(Features::DISABLE_X_AMAZONPAY));
        $this->assertTrue($subMerchant->isFeatureEnabled(Features::BLOCK_VA_PAYOUTS));

        $this->assertFalse($masterMerchant->isFeatureEnabled(Features::SUB_VIRTUAL_ACCOUNT));

        return $subVirtualAccount;
    }

    public function testEnableSubVirtualAccountOfTypeSubDirectAccount()
    {
        $subVirtualAccount = $this->testDisableSubVirtualAccountOfTypeSubDirectAccount();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] .= $subVirtualAccount->getId();

        $this->startTest($testData);

        $masterMerchant    = $this->getDbEntityById('merchant', '10000000000000');
        $subMerchant       = $this->getDbEntityById('merchant', '100abc000abc01');
        $virtualAccount    = $this->getDbLastEntity('virtual_account');
        $subVirtualAccount = $this->getDbLastEntity('sub_virtual_account');

        $this->assertEquals('sub_direct_account', $subVirtualAccount->getSubAccountType());
        $this->assertEquals('100abc000abc01', $subVirtualAccount->getSubMerchantId());

        $this->assertEquals('100abc000abc01', $virtualAccount->getMerchantId());
        $this->assertEquals('closed', $virtualAccount->getStatus());
        $this->assertTrue($subMerchant->isFeatureEnabled(Features::BLOCK_FAV));
        $this->assertTrue($subMerchant->isFeatureEnabled(Features::ASSUME_SUB_ACCOUNT));
        $this->assertTrue($subMerchant->isFeatureEnabled(Features::DISABLE_X_AMAZONPAY));
        $this->assertFalse($subMerchant->isFeatureEnabled(Features::BLOCK_VA_PAYOUTS));

        //The feature flag is being replaced by another feature
        $this->assertFalse($masterMerchant->isFeatureEnabled(Features::SUB_VIRTUAL_ACCOUNT));
        $this->assertTrue($masterMerchant->isFeatureEnabled(Features::ASSUME_MASTER_ACCOUNT));
    }

    public function testAddLimitToSubAccountWithOtpViaCreditTransfer()
    {
        [$subMerchant, $subMerchantBalance, $masterMerchantDirectBalance] = $this->fixtureSetUpForSubVirtualAccount();

        $this->fixtures->merchant->addFeatures([Features::ASSUME_MASTER_ACCOUNT]);

        $this->fixtures->create('sub_virtual_account', [
            'sub_merchant_id'       => $subMerchant->getId(),
            'sub_account_number'    => $subMerchantBalance->getAccountNumber(),
            'master_merchant_id'    => '10000000000000',
            'master_balance_id'     => $masterMerchantDirectBalance->getId(),
            'master_account_number' => $masterMerchantDirectBalance->getAccountNumber(),
            'sub_account_type'      => 'sub_direct_account'
        ]);

        $this->fixtures->merchant->edit('10000000000000', ['name' => 'OG NBFC']);

        $this->ba->proxyAuth();

        $subMerchantBalanceBeforeLimitAddition = $subMerchantBalance->getBalance();

        $this->startTest();

        $transaction    = $this->getDbLastEntity('transaction');
        $creditTransfer = $this->getDbLastEntity('credit_transfer');
        $masterMerchant = $this->getDbEntityById('merchant', '10000000000000');
        $subMerchantBalance->reload();

        $this->assertEquals(200, $creditTransfer->getAmount());
        $this->assertEquals('processed', $creditTransfer->getStatus());
        $this->assertEquals($subMerchantBalance->getId(), $creditTransfer->getBalanceId());
        $this->assertEquals($subMerchantBalance->merchant->getId(), $creditTransfer->getMerchantId());
        $this->assertEquals('IFT', $creditTransfer->getMode());
        $this->assertEquals($masterMerchant->getId(), $creditTransfer->getPayerMerchantId());
        $this->assertEquals($masterMerchant->getName(), $creditTransfer->getPayerName());
        $this->assertEquals('MerchantUser01', $creditTransfer->getUserId());

        $expectedCreditTransferDescription = sprintf(Constants::CREDIT_TRANSFER_DESCRIPTION,
                                                     $masterMerchant->getDisplayNameElseName(),
                                                     $masterMerchant->getId(),
                                                     $subMerchant->getDisplayNameElseName(),
                                                     $subMerchantBalance->getAccountNumber());

        $this->assertEquals($expectedCreditTransferDescription, $creditTransfer->getDescription());

        //Assert that closing balance has increased by credit transfer amount
        $this->assertEquals($subMerchantBalanceBeforeLimitAddition + $creditTransfer->getAmount(),
                            $subMerchantBalance->getBalance());

        //Assertions related to the transaction created for credit transfer entity
        $this->assertEquals($transaction->getAmount(), $creditTransfer->getAmount());
        $this->assertEquals($transaction->getEntityId(), $creditTransfer->getId());
        $this->assertEquals($transaction->getType(), $creditTransfer->getEntityName());
        $this->assertEquals($subMerchantBalance->getBalance(), $transaction->getBalance());
        $this->assertEquals($transaction->getCredit(), $creditTransfer->getAmount());
        $this->assertEquals($transaction->getDebit(), 0);
        $this->assertEquals($transaction->getFee(), 0);
        $this->assertEquals($transaction->getTax(), 0);

        return $creditTransfer;
    }

    public function testLimitAdditionToSubAccountInLedgerReverseShadow()
    {
        [$subMerchant, $subMerchantBalance, $masterMerchantDirectBalance] = $this->fixtureSetUpForSubVirtualAccount();

        $this->fixtures->merchant->addFeatures([Features::ASSUME_MASTER_ACCOUNT]);

        $this->fixtures->create('sub_virtual_account', [
            'sub_merchant_id'       => $subMerchant->getId(),
            'sub_account_number'    => $subMerchantBalance->getAccountNumber(),
            'master_merchant_id'    => '10000000000000',
            'master_balance_id'     => $masterMerchantDirectBalance->getId(),
            'master_account_number' => $masterMerchantDirectBalance->getAccountNumber(),
            'sub_account_type'      => 'sub_direct_account'
        ]);

        $this->fixtures->merchant->edit('10000000000000', ['name' => 'OG NBFC']);

        $this->fixtures->merchant->addFeatures([Features::LEDGER_REVERSE_SHADOW]);

        $this->mockLedgerResponse($subMerchant->getId(), 200);

        $this->ba->proxyAuth();

        $this->startTest($this->testData['testAddLimitToSubAccountWithOtpViaCreditTransfer']);
    }

    public function testLimitAdditionToSubMerchantInLedgerShadowMode()
    {
        [$subMerchant, $subMerchantBalance, $masterMerchantDirectBalance] = $this->fixtureSetUpForSubVirtualAccount();

        $this->fixtures->merchant->addFeatures([Features::ASSUME_MASTER_ACCOUNT]);

        $this->fixtures->create('sub_virtual_account', [
            'sub_merchant_id'       => $subMerchant->getId(),
            'sub_account_number'    => $subMerchantBalance->getAccountNumber(),
            'master_merchant_id'    => '10000000000000',
            'master_balance_id'     => $masterMerchantDirectBalance->getId(),
            'master_account_number' => $masterMerchantDirectBalance->getAccountNumber(),
            'sub_account_type'      => 'sub_direct_account'
        ]);

        $this->fixtures->merchant->edit('10000000000000', ['name' => 'OG NBFC']);

        $this->fixtures->merchant->addFeatures([Features::LEDGER_JOURNAL_WRITES]);

        $ledgerSnsPayloadArray = [];

        $this->mockLedgerSns(1, $ledgerSnsPayloadArray);

        $this->ba->proxyAuth();

        $this->startTest($this->testData['testAddLimitToSubAccountWithOtpViaCreditTransfer']);

        $creditTransfer = $this->getDbLastEntity('credit_transfer');

        for ($index = 0; $index<count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $ledgerRequestPayload['identifiers']       = json_decode($ledgerRequestPayload['identifiers'], true);
            $ledgerRequestPayload['additional_params'] = json_decode($ledgerRequestPayload['additional_params'], true);

            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('test', $ledgerRequestPayload['mode']);
            $this->assertEquals($creditTransfer->getPublicId(), $ledgerRequestPayload['transactor_id']);
            $this->assertEquals('100abc000abc01', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('INR', $ledgerRequestPayload['currency']);
            $this->assertEquals('0', $ledgerRequestPayload['commission']);
            $this->assertEquals('0', $ledgerRequestPayload['tax']);
            $this->assertEquals('va_to_va_credit_processed', $ledgerRequestPayload['transactor_event']);
            $this->assertArrayNotHasKey('fee_accounting', $ledgerRequestPayload['additional_params']);
            $this->assertArrayNotHasKey('fts_fund_account_id', $ledgerRequestPayload['identifiers']);
            $this->assertArrayNotHasKey('fts_account_type', $ledgerRequestPayload['identifiers']);
        }
    }

    public function testLimitAdditionToSubMerchantWhenMasterMerchantFeatureIsNotEnabled()
    {
        [$subMerchant, $subMerchantBalance, $masterMerchantDirectBalance] = $this->fixtureSetUpForSubVirtualAccount();

        $this->fixtures->create('sub_virtual_account', [
            'sub_merchant_id'       => $subMerchant->getId(),
            'sub_account_number'    => $subMerchantBalance->getAccountNumber(),
            'master_merchant_id'    => '10000000000000',
            'master_balance_id'     => $masterMerchantDirectBalance->getId(),
            'master_account_number' => $masterMerchantDirectBalance->getAccountNumber(),
            'sub_account_type'      => 'sub_direct_account'
        ]);

        $this->fixtures->merchant->edit('10000000000000', ['name' => 'OG NBFC']);

        $this->ba->proxyAuth();

        $subMerchantBalanceBeforeLimitAdditionAttempt = $subMerchantBalance->getBalance();

        $this->startTest();

        $creditTransfer = $this->getDbLastEntity('credit_transfer');
        $subMerchantBalanceAfterLimitAdditionAttempt = $subMerchantBalance->reload()->getBalance();

        $this->assertNull($creditTransfer);
        $this->assertEquals($subMerchantBalanceBeforeLimitAdditionAttempt, $subMerchantBalanceAfterLimitAdditionAttempt);
    }

    public function mockLedgerResponse($mid, $amount)
    {
        $this->app['config']->set('applications.ledger.enabled', true);
        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $expectedPayload = [
            'merchant_id'      => $mid,
            'transactor_event' => 'va_to_va_credit_processed',
            'amount'           => $amount,
            'base_amount'      => $amount,
            'commission'       => 0,
            'tax'              => 0
        ];

        $mockLedger->shouldReceive('createJournal')
            ->withArgs(function($payload, $headers, $throwExOnFailure) use ($expectedPayload)
            {
                $this->assertArraySelectiveEquals($expectedPayload, $payload);
                return true;
            });
    }

    public function testFetchSubVirtualAccountWithClosingBalance()
    {
        Carbon::setTestNow();

        $this->fixtureSetUpForSubVirtualAccount();

        $this->fixtures->create('sub_virtual_account', [
            'master_account_number' => '2323230041626907',
            'sub_account_number'    => '2323230041626906',
            'sub_account_type'      => 'sub_direct_account',
            'sub_merchant_id'       => '100abc000abc01',
            'master_merchant_id'    => '10000000000000',
            'name'                  => 'Sub Merchant 1',
            'active'                => true,
            'created_at'            => Carbon::now()->subSeconds(30)->getTimestamp(),
        ]);

        $this->fixtures->create('merchant', [
            'id' => '100xyz000xyz01',
            'display_name' => 'Fin Lease',
            'name' => 'Sub Merchant 2',
        ]);

        $this->fixtures->create('balance', [
            'id' => random_alphanum_string(14),
            'merchant_id' => '100xyz000xyz01',
            'type' => 'banking',
            'account_type' => 'shared',
            'account_number' => '2323230041626908',
            'balance' => 2020,
        ]);

        $this->fixtures->create('sub_virtual_account', [
            'master_account_number' => '2323230041626907',
            'sub_account_number'    => '2323230041626908',
            'sub_account_type'      => 'sub_direct_account',
            'sub_merchant_id'       => '100xyz000xyz01',
            'master_merchant_id'    => '10000000000000',
            'name'                  => 'Sub Merchant 2',
            'active'                => false,
            'created_at'            => Carbon::now()->subSeconds(60)->getTimestamp(),
        ]);

        $subBalance = $this->getDbEntity('balance', ['merchant_id' => '100abc000abc01' , 'account_type' => 'shared']);

        $this->fixtures->edit('balance', $subBalance->getId(), ['balance' => 1000]);

        $this->fixtures->edit('merchant', '100abc000abc01', ['name' => 'Sub Merchant 1', 'display_name' => null]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchSubVirtualAccounts_ClosingBalance_Shared()
    {
        Carbon::setTestNow();

        $this->fixtureSetUpForSubVirtualAccount();

        $this->fixtures->create('sub_virtual_account', [
            'master_account_number' => '2323230041626907',
            'sub_account_number'    => '2323230041626906',
            'sub_account_type'      => 'default',
            'sub_merchant_id'       => '100abc000abc01',
            'master_merchant_id'    => '10000000000000',
            'name'                  => 'Sub VA 1',
            'active'                => true,
            'created_at'            => Carbon::now()->subSeconds(30)->getTimestamp(),
        ]);

        $this->fixtures->create('merchant', [
            'id' => '100xyz000xyz01',
            'display_name' => 'Fin Lease',
            'name' => 'Sub Merchant 2',
        ]);

        $this->fixtures->create('balance', [
            'id' => random_alphanum_string(14),
            'merchant_id' => '100xyz000xyz01',
            'type' => 'banking',
            'account_type' => 'shared',
            'account_number' => '2323230041626908',
            'balance' => 2020,
        ]);

        $this->fixtures->create('sub_virtual_account', [
            'master_account_number' => '2323230041626907',
            'sub_account_number'    => '2323230041626908',
            'sub_account_type'      => 'default',
            'sub_merchant_id'       => '100xyz000xyz01',
            'master_merchant_id'    => '10000000000000',
            'name'                  => 'Sub VA 2',
            'active'                => false,
            'created_at'            => Carbon::now()->subSeconds(60)->getTimestamp(),
        ]);

        $subBalance = $this->getDbEntity('balance', ['merchant_id' => '100abc000abc01' , 'account_type' => 'shared']);

        $this->fixtures->edit('balance', $subBalance->getId(), ['balance' => 1000]);

        $this->fixtures->edit('merchant', '100abc000abc01', ['name' => 'Sub Merchant 1', 'display_name' => null]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchSubVirtualAccounts_Shared_Exception()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('sub_virtual_account', ['id' => 'HM8yTa58wo3qRZ']);

        $this->fixtures->create('sub_virtual_account', ['id' => 'HM8yTa58wo3qRY', 'active' => false]);

        $metricsMock = $this->createMetricsMock();

        $boolMetricCaptured = false;

        $this->mockAndCaptureCountMetric(
            Metric::SUB_VIRTUAL_ACCOUNT_FETCH_MULTIPLE_FAILURE,
            $metricsMock,
            $boolMetricCaptured,
            [
            ],
        );

        // Balance is not created for Sub Virtual Account. Hence, this should fail
        // while fetch balance
        $this->startTest();

        $this->assertTrue($boolMetricCaptured);
    }

    public function testFetchSubVirtualAccountCreditTransfers()
    {
        $this->fixtureSetUpForSubVirtualAccount();

        $user = $this->setupMerchantUser('10000000000000');

        $this->ba->proxyAuth();

        $this->fixtures->create('sub_virtual_account', [
            'master_account_number' => '2323230041626907',
            'sub_account_number'    => '2323230041626908',
            'sub_account_type'      => 'sub_direct_account',
            'sub_merchant_id'       => '100abc000abc01',
            'master_merchant_id'    => '10000000000000',
            'name'                  => 'Sub Merchant 1',
            'active'                => true,
        ]);

        $subBalance = $this->getDbEntity('balance', ['merchant_id' => '100abc000abc01']);

        $this->fixtures->create('credit_transfer', [
            'merchant_id'       => '100abc000abc01',
            'payer_merchant_id' => '10000000000000',
            'payer_user_id'     => $user->getId(),
            'balance_id'        => $subBalance->getId(),
            'amount'            => 300,
            'status'            => 'processed',
            'created_at'        => 1675352200,
            'updated_at'        => 1675352200,
        ]);

        $this->fixtures->create('credit_transfer', [
            'merchant_id'       => '100abc000abc01',
            'payer_merchant_id' => '10000000000000',
            'payer_user_id'     => $user->getId(),
            'balance_id'        => $subBalance->getId(),
            'amount'            => 400,
            'status'            => 'failed',
            'created_at'        => 1675352300,
            'updated_at'        => 1675352300,
        ]);

        $this->fixtures->create('credit_transfer', [
            'merchant_id'       => '100abc000abc01',
            'payer_merchant_id' => '10000000000000',
            'payer_user_id'     => $user->getId(),
            'balance_id'        => $subBalance->getId(),
            'amount'            => 500,
            'status'            => 'created',
            'created_at'        => 1675352400,
            'updated_at'        => 1675352400,
        ]);

        /* This should not be returned as the payer_merchant_id is different */
        $this->fixtures->create('credit_transfer', [
            'merchant_id'       => '100abc000abc01',
            'payer_merchant_id' => '1000000000001',
            'payer_user_id'     => $user->getId(),
            'balance_id'        => $subBalance->getId(),
            'amount'            => 500,
            'status'            => 'created',
            'created_at'        => 1675352400,
            'updated_at'        => 1675352400,
        ]);

        $this->startTest();
    }

    public function setupMerchantUser($merchantId = '100abc000abc01')
    {
        $user = $this->fixtures->create('user', ['id' => 'MerchantUser02', 'email' => 'merchant2@gmail.com']);

        $this->fixtures->create('merchant_user', [
            'merchant_id' => $merchantId,
            'user_id'     => 'MerchantUser02',
            'role'        => 'owner',
            'product'     => 'primary',
            'created_at'  => Carbon::now()->getTimestamp(),
            'updated_at'  => Carbon::now()->getTimestamp(),
        ]);

        $this->fixtures->create('key', [
            'merchant_id' => '100abc000abc01',
        ]);

        $this->ba->proxyAuth('rzp_test_100abc000abc01', 'MerchantUser02');

        return $user;
    }

    public function mockRazorXForDCS($returnValue)
    {
        $razorxMock = Mockery::mock(RazorXClient::class, [$this->app])->makePartial();

        $razorxMock->shouldReceive('getTreatment')
            ->andReturn($returnValue);

        $this->app->instance('razorx', $razorxMock);
    }


    public function mockDCS()
    {
        $dcsMock = $this->getMockBuilder(Service::class)
                        ->setConstructorArgs([$this->app])
                        ->onlyMethods(['editFeature'])
                        ->getMock();

        $this->app->instance('dcs', $dcsMock);

        $dcsMock->expects($this->any())->method('editFeature')->willReturn(null);

        return $dcsMock;
    }


}
