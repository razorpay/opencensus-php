<?php

namespace RZP\Tests\Functional\FundAccount;

use App;
use Queue;

use RZP\Error\Error;
use RZP\Models\Feature;
use RZP\Models\FundAccount;
use RZP\Models\Card\Issuer;
use RZP\Models\Card\Network;
use RZP\Models\Contact\Type;
use RZP\Services\RazorXClient;
use RZP\Jobs\FTS\CreateAccount;
use RZP\Tests\Functional\TestCase;
use RZP\Jobs\FundAccountDetailsPropagatorJob;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\FundAccount\FundAccountValidationTrait;

class FundAccountsTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use FundAccountValidationTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/FundAccountsTestData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function testGetFundAccounts()
    {
        $this->fixtures->create('fund_account:bank_account', ['id' => '100000000000fa']);

        $this->startTest();
    }

    public function testFetchFundAccounts()
    {
        $this->fixtures->create('fund_account:bank_account', ['id' => '100000000001fa']);
        $this->fixtures->create('fund_account:bank_account', ['id' => '100000000002fa']);
        $this->fixtures->create('fund_account:vpa', ['id' => '100000000003fa']);

        $this->startTest();
    }

    public function testCreateFundAccountInactiveContact()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact', 'active' => 0]);

        $this->startTest();
    }

    public function testCreateFundAccountBankAccount()
    {
        Queue::fake();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $response = $this->startTest();

        $bankAccount = $this->getLastEntity('bank_account', true);

        $expectedBankAccount = [
            'type'             => 'contact',
            'entity_id'        => '1000000contact',
            'ifsc_code'        => 'SBIN0007105',
            'account_number'   => '111000111',
            'beneficiary_name' => 'Amit M',
            'merchant_id'      => '10000000000000',
        ];

        $this->assertArraySelectiveEquals($expectedBankAccount, $bankAccount);

        $this->assertArrayNotHasKey(FundAccount\Entity::UNIQUE_HASH, $response);

        $expectedHashInput = '10000000000000|contact|1000000contact|bank_account|111000111|SBIN0007105|AmitM';

        $expectedHash = hash('sha3-256', $expectedHashInput);

        $fundAccount = $this->getDbLastEntity('fund_account');

        $uniqueHash = $fundAccount->getUniqueHash();

        $this->assertEquals($expectedHash, $uniqueHash);

        Queue::assertPushed(CreateAccount::class);
    }

    public function testCreateFundAccountBankAccountWithFeatureFlagEnabled()
    {
        Queue::fake();

        $this->fixtures->merchant->addFeatures([Feature\Constants::SKIP_CONTACT_DEDUP_FA_BA]);

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $response = $this->startTest();

        $bankAccount = $this->getLastEntity('bank_account', true);

        $expectedBankAccount = [
            'type'             => 'contact',
            'entity_id'        => '1000000contact',
            'ifsc_code'        => 'SBIN0000011',
            'account_number'   => '111000371',
            'beneficiary_name' => 'Chirag C',
            'merchant_id'      => '10000000000000',
        ];

        $this->assertArraySelectiveEquals($expectedBankAccount, $bankAccount);

        $this->assertArrayNotHasKey(FundAccount\Entity::UNIQUE_HASH, $response);

        $expectedHashInput = '10000000000000|contact|bank_account|111000371|SBIN';

        $expectedHash = hash('sha3-256', $expectedHashInput);

        $fundAccount = $this->getDbLastEntity('fund_account');

        $uniqueHash = $fundAccount->getUniqueHash();

        $this->assertEquals($expectedHash, $uniqueHash);

        Queue::assertPushed(CreateAccount::class);
    }

    /*
     * Test if the dedup logic is working for merchants with feature flag enabled
     */
    public function testDuplicateFundAccountCreationWithFeatureFlagEnabled()
    {
        $this->testCreateFundAccountBankAccountWithFeatureFlagEnabled();

        $this->fixtures->create('contact', ['id' => '1000001contact']);

        $fundAccount = $this->getDbLastEntity('fund_account');

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertArrayNotHasKey(FundAccount\Entity::UNIQUE_HASH, $response);

        $this->assertEquals($fundAccount->getPublicId(), $response['id']);
    }

    /*
     * Tests the case where there is an attempt to create a duplicate fund account
     * which was stored earlier with old dedup logic.
     * Applicable for merchants with feature flag enabled.
     */
    public function testDuplicateFundAccountCreationOfOldHashWithFeatureFlagEnabled()
    {
        $this->testCreateFundAccountBankAccount();

        $fundAccount = $this->getDbLastEntity('fund_account');

        $this->fixtures->merchant->addFeatures([Feature\Constants::SKIP_CONTACT_DEDUP_FA_BA]);

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertArrayNotHasKey(FundAccount\Entity::UNIQUE_HASH, $response);

        $this->assertEquals($fundAccount->getPublicId(), $response['id']);

        $expectedHashInput = '10000000000000|contact|bank_account|111000111|SBIN';

        $expectedHash = hash('sha3-256', $expectedHashInput);

        $fundAccount = $this->getDbLastEntity('fund_account');

        $uniqueHash = $fundAccount->getUniqueHash();

        $this->assertEquals($expectedHash, $uniqueHash);
    }

    /*
     * Tests the case where there is an attempt to create a duplicate fund account
     * which was stored earlier with no unique hash.
     * Applicable for merchants with feature flag enabled.
     */
    public function testDuplicateFundAccountCreationWithNoHashAndFeatureFlagEnabled()
    {
        Queue::fake();

        $this->fixtures->merchant->addFeatures([Feature\Constants::SKIP_CONTACT_DEDUP_FA_BA]);

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->fixtures->create('fund_account:bank_account'
            , ['id'          => '100000000000fa',
               'source_type' => 'contact',
               'source_id'   => '1000000contact']);

        $fundAccount = $this->getDbLastEntity('fund_account');

        $this->fixtures->edit('bank_account', $fundAccount->getAccountId()
            , ['ifsc_code'        => 'SBIN0000011',
               'beneficiary_name' => 'Chirag C',
               'account_number'   => '111000371',
               'account_type'     => 'bank_account',
               'type'             => 'contact']);

        $response = $this->startTest();

        $this->assertArrayNotHasKey(FundAccount\Entity::UNIQUE_HASH, $response);

        $this->assertEquals($fundAccount->getPublicId(), $response['id']);

        $expectedHashInput = '10000000000000|contact|bank_account|111000371|SBIN';

        $expectedHash = hash('sha3-256', $expectedHashInput);

        $fundAccount->reload();

        $uniqueHash = $fundAccount->getUniqueHash();

        $this->assertEquals($expectedHash, $uniqueHash);
    }

    /*
     * Tests the case where there is an attempt to create a duplicate fund account
     * by a different contact ID which was stored earlier with different unique hash.
     * Applicable for merchants with feature flag enabled.
     */
    public function testDuplicateFundAccountCreationWithDifferentContactsAndFeatureFlagEnabled()
    {
        $this->testCreateFundAccountBankAccount();

        $fundAccount = $this->getDbLastEntity('fund_account');

        $this->fixtures->merchant->addFeatures([Feature\Constants::SKIP_CONTACT_DEDUP_FA_BA]);

        $this->fixtures->create('contact', ['id' => '1000001contact']);

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertArrayNotHasKey(FundAccount\Entity::UNIQUE_HASH, $response);

        $this->assertEquals($fundAccount->getPublicId(), $response['id']);

        $expectedHashInput = '10000000000000|contact|bank_account|111000111|SBIN';

        $expectedHash = hash('sha3-256', $expectedHashInput);

        $fundAccount->reload();

        $uniqueHash = $fundAccount->getUniqueHash();

        $this->assertEquals($expectedHash, $uniqueHash);
    }

    public function testCreateFundAccountBankAccountThreeCharName()
    {
        Queue::fake();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $response = $this->startTest();

        $bankAccount = $this->getLastEntity('bank_account', true);

        $expectedBankAccount = [
            'type'             => 'contact',
            'entity_id'        => '1000000contact',
            'ifsc_code'        => 'SBIN0007105',
            'account_number'   => '111000111',
            'beneficiary_name' => 'Ann',
            'merchant_id'      => '10000000000000',
        ];

        $this->assertArraySelectiveEquals($expectedBankAccount, $bankAccount);

        $this->assertArrayNotHasKey(FundAccount\Entity::UNIQUE_HASH, $response);

        $expectedHashInput = '10000000000000|contact|1000000contact|bank_account|111000111|SBIN0007105|Ann';

        $expectedHash = hash('sha3-256', $expectedHashInput);

        $fundAccount = $this->getDbLastEntity('fund_account');

        $uniqueHash = $fundAccount->getUniqueHash();

        $this->assertEquals($expectedHash, $uniqueHash);

        Queue::assertPushed(CreateAccount::class);
    }

    public function testCreateFundAccountBankAccountWithEmptyArray()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $response = $this->startTest();

        $this->assertArrayHasKey(Error::STEP, $response['error']);

        $this->assertArrayHasKey(Error::METADATA, $response['error']);
    }

    public function testCreateFundAccountBankAccountWithInvalidAccountNumber()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateFundAccountBankAccountWithInvalidName()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $response = $this->startTest();

        $this->assertArrayHasKey(Error::STEP, $response['error']);

        $this->assertArrayHasKey(Error::METADATA, $response['error']);
    }

    public function testCreateFundAccountBankAccountWithInvalidIfsc()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateFundAccountBankAccountWithOldIfscMappedToNewIfsc()
    {
        Queue::fake();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateFundAccountBankAccountWithNbsp()
    {
        Queue::fake();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateFundAccountBankAccountWithExtraSpacesAtStartAndEndOfBeneName()
    {
        Queue::fake();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateFundAccountBankAccountPublic()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $response = $this->startTest();
    }

    public function testCreateFundAccountBankAccountBeneficiaryNotRequired()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();

        $bankAccount = $this->getLastEntity('bank_account', true);

        $expectedBankAccount = [
            'type'           => 'contact',
            'entity_id'      => '1000000contact',
            'ifsc_code'      => 'SBIN0007105',
            'account_number' => '111000111',
            'merchant_id'    => '10000000000000',
        ];

        $this->assertArraySelectiveEquals($expectedBankAccount, $bankAccount);
    }

    public function testCreateVpa()
    {
        Queue::fake();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $response = $this->startTest();

        $vpa = $this->getLastEntity('vpa', true);

        $expectedVpaAttrs = [
            'entity_type' => 'contact',
            'entity_id'   => '1000000contact',
            'username'    => 'amitm',
            'handle'      => 'upi',
            'merchant_id' => '10000000000000',
        ];

        $this->assertArraySelectiveEquals($expectedVpaAttrs, $vpa);

        $this->assertArrayNotHasKey(FundAccount\Entity::UNIQUE_HASH, $response);

        $expectedHashInput = '10000000000000|contact|1000000contact|vpa|amitm|upi';

        $expectedHash = hash('sha3-256', $expectedHashInput);

        $fundAccount = $this->getDbLastEntity('fund_account');

        $uniqueHash = $fundAccount->getUniqueHash();

        $this->assertEquals($expectedHash, $uniqueHash);

        Queue::assertPushed(CreateAccount::class);
    }

    public function testCreateVpaWithNewRegex()
    {
        Queue::fake();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->mockRazorxTreatment();

        $response = $this->startTest();

        $vpa = $this->getLastEntity('vpa', true);

        $expectedVpaAttrs = [
            'entity_type' => 'contact',
            'entity_id'   => '1000000contact',
            'username'    => '50100177856195',
            'handle'      => 'HDFC.ifsc.npci',
            'merchant_id' => '10000000000000',
        ];

        $this->assertArraySelectiveEquals($expectedVpaAttrs, $vpa);

        $this->assertArrayNotHasKey(FundAccount\Entity::UNIQUE_HASH, $response);

        $expectedHashInput = '10000000000000|contact|1000000contact|vpa|50100177856195|hdfcifscnpci';

        $expectedHash = hash('sha3-256', $expectedHashInput);

        $fundAccount = $this->getDbLastEntity('fund_account');

        $uniqueHash = $fundAccount->getUniqueHash();

        $this->assertEquals($expectedHash, $uniqueHash);

        Queue::assertPushed(CreateAccount::class);
    }

    public function testCreateWalletAccountFundAccount()
    {
        Queue::fake();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $response = $this->startTest();

        $walletAccount = $this->getLastEntity('wallet_account', true);

        $expectedWalletAccountAttrs = [
            'entity_type'   => 'contact',
            'entity_id'     => '1000000contact',
            'phone'         => '+918124632237',
            'provider'      => 'amazonpay',
            'email'         => 'test@gmail.com',
            'name'          => 'test',
            'merchant_id'   => '10000000000000',
        ];

        $this->assertArraySelectiveEquals($expectedWalletAccountAttrs, $walletAccount);

        $this->assertArrayNotHasKey(FundAccount\Entity::UNIQUE_HASH, $response);

        $fundAccount = $this->getDbLastEntity('fund_account');

        $uniqueHash = $fundAccount->getUniqueHash();

        $this->assertNull($uniqueHash);

        Queue::assertPushed(CreateAccount::class);
    }

    public function testCreateWalletAccountFundAccountPhoneNull()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateWalletAccountFundAccountPhoneEmpty()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateWalletAccountFundAccountModeCapital()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateWalletAccountFundAccountForNonwhitelistedMerchant()
    {
        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::DISABLE_X_AMAZONPAY,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateWalletAccountFundAccountPhoneFormat1()
    {
        Queue::fake();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();

        $walletAccount = $this->getLastEntity('wallet_account', true);

        $expectedWalletAccountAttrs = [
            'entity_type'   => 'contact',
            'entity_id'     => '1000000contact',
            'phone'         => '+918124632237',
            'provider'      => 'amazonpay',
            'email'         => 'test@gmail.com',
            'name'          => 'test',
            'merchant_id'   => '10000000000000',
        ];

        $this->assertArraySelectiveEquals($expectedWalletAccountAttrs, $walletAccount);

        Queue::assertPushed(CreateAccount::class);
    }

    public function testCreateWalletAccountFundAccountPhoneFormat2()
    {
        Queue::fake();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();

        $walletAccount = $this->getLastEntity('wallet_account', true);

        $expectedWalletAccountAttrs = [
            'entity_type'   => 'contact',
            'entity_id'     => '1000000contact',
            'phone'         => '+918124632237',
            'provider'      => 'amazonpay',
            'email'         => 'test@gmail.com',
            'name'          => 'test',
            'merchant_id'   => '10000000000000',
        ];

        $this->assertArraySelectiveEquals($expectedWalletAccountAttrs, $walletAccount);

        Queue::assertPushed(CreateAccount::class);
    }

    public function testCreateWalletAccountFundAccountPhoneFormat3()
    {
        Queue::fake();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();

        $walletAccount = $this->getLastEntity('wallet_account', true);

        $expectedWalletAccountAttrs = [
            'entity_type'   => 'contact',
            'entity_id'     => '1000000contact',
            'phone'         => '+918124632237',
            'provider'      => 'amazonpay',
            'email'         => 'test@gmail.com',
            'name'          => 'test',
            'merchant_id'   => '10000000000000',
        ];

        $this->assertArraySelectiveEquals($expectedWalletAccountAttrs, $walletAccount);

        Queue::assertPushed(CreateAccount::class);
    }

    public function testCreateCard()
    {
        Queue::fake();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_TO_CARDS, Feature\Constants::S2S]);

        $this->mockCardVault();

        $response = $this->startTest();

        $card = $this->getLastEntity('card', true);

        $expectedCardAttrs = [
            'merchant_id'   => '10000000000000',
            'expiry_month'  => 4,
            'expiry_year'   => 2025,
        ];

        $this->assertArraySelectiveEquals($expectedCardAttrs, $card);

        $this->assertArrayNotHasKey(FundAccount\Entity::UNIQUE_HASH, $response);

        $fundAccount = $this->getDbLastEntity('fund_account');

        $uniqueHash = $fundAccount->getUniqueHash();

        $this->assertNull($uniqueHash);

        Queue::assertPushed(CreateAccount::class);
    }

    public function testCreateCardFundAccountWithNameAsAlphanumeric()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_TO_CARDS, Feature\Constants::S2S]);

        $this->mockCardVault();

        $this->startTest();
    }

    public function testCreateCardFundAccountWithSpecialCharName()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_TO_CARDS, Feature\Constants::S2S]);

        $this->mockCardVault();

        $this->startTest();
    }

    public function testCreateCardBeneficiaryVerified()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_TO_CARDS, Feature\Constants::S2S]);

        $this->mockCardVault();

        $this->startTest();

        $card = $this->getLastEntity('card', true);

        $expectedCardAttrs = [
            'merchant_id'   => '10000000000000',
            'expiry_month'  => 4,
            'expiry_year'   => 2025,
        ];

        $this->assertArraySelectiveEquals($expectedCardAttrs, $card);
    }

    public function testCreateCardBeneficiaryFailed()
    {
        $this->fixtures->create('contact', ['id' => 'invalidcontact']);

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_TO_CARDS, Feature\Constants::S2S]);

        $this->mockCardVault();

        $this->startTest();

        $card = $this->getLastEntity('card', true);

        $expectedCardAttrs = [
            'merchant_id'   => '10000000000000',
            'expiry_month'  => 4,
            'expiry_year'   => 2025,
        ];

        $this->assertArraySelectiveEquals($expectedCardAttrs, $card);
    }

    public function testCreateCardAndVpa()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateWithoutContactOrCustomer()
    {
        $this->startTest();
    }

    public function testCreateFundAccountInvalidVpa()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateFundAccountInvalidBankIfsc()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateFromCustomer()
    {
        $this->fixtures->create('customer', ['id' => '1000facustomer']);

        $response = $this->startTest();

        $this->assertArrayNotHasKey(FundAccount\Entity::UNIQUE_HASH, $response);

        $expectedHashInput = '10000000000000|customer|1000facustomer|vpa|amitm|upi';

        $expectedHash = hash('sha3-256', $expectedHashInput);

        $fundAccount = $this->getDbLastEntity('fund_account');

        $uniqueHash = $fundAccount->getUniqueHash();

        $this->assertEquals($expectedHash, $uniqueHash);
    }

    public function testUpdateFundAccount()
    {
        $this->fixtures->create('fund_account:bank_account', ['id' => '100000000000fa']);

        $this->startTest();
    }

    public function testInternalContactUpdateFailsForProxyAuth()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('contact', ['id' => '1000000contact', 'type' => Type::TAX_PAYMENT_INTERNAL_CONTACT]);

        $this->fixtures->create('fund_account:bank_account',
                                [
                                    'id'          => '100000000000fa',
                                    'source_id'   => '1000000contact',
                                    'source_type' => 'contact'
                                ]);

        $this->startTest();
    }

    public function testInternalContactUpdateAllowedForInternalAuth()
    {
        $this->ba->appAuthTest(App::getFacadeRoot()['config']['applications.vendor_payments.secret']);

        $this->fixtures->create('contact', ['id' => '1000000contact', 'type' => Type::TAX_PAYMENT_INTERNAL_CONTACT]);

        $this->fixtures->create('fund_account:bank_account',
                                [
                                    'id'          => '100000000000fa',
                                    'source_id'   => '1000000contact',
                                    'source_type' => 'contact'
                                ]);

        $this->startTest();
    }

    public function testInternalContactUpdateFailsForRZPFees()
    {
        $this->ba->appAuthTest(App::getFacadeRoot()['config']['applications.vendor_payments.secret']);

        $this->fixtures->create('contact', ['id' => '1000000contact', 'type' => Type::RZP_FEES]);

        $this->fixtures->create('fund_account:bank_account',
                                [
                                    'id'          => '100000000000fa',
                                    'source_id'   => '1000000contact',
                                    'source_type' => 'contact'
                                ]);

        $this->startTest();
    }

    public function testDeleteFundAccount()
    {
        $this->fixtures->create('fund_account:bank_account', ['id' => '100000000000fa']);

        $this->startTest();
    }

    public function testBulkFundAccount()
    {
        $this->ba->batchAuth();
        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];
        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;
        $this->startTest();
    }

    public function testBulkFundAccountForMerchantBehindRazorx()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];
        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();

        $contacts = $this->getEntities('contact');

        $fundAccounts = $this->getEntities('fund_account');

        $this->assertEquals(2, count($contacts['items']));

        $this->assertEquals(2, count($fundAccounts['items']));
    }

    public function testBulkFundAccountWithInvalidContactId()
    {
        $this->ba->batchAuth();
        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];
        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;
        $this->startTest();
    }

    public function testBulkFundAccountWithValidContactId()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->fixtures->create('contact', ['id' => '1000001contact', 'name' => 'Contact X']);

        $this->startTest();
    }

    public function testBulkFundAccountWithPrivateAuthFailed()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testBulkFundAccountWithSameContact()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->fixtures->create('contact', ['id' => '1000001contact', 'name' => 'Contact X']);

        $response = $this->startTest();

        $this->assertEquals($response['items'][0]['contact_id'], $response['items'][2]['contact_id']);
    }

    public function testBulkFundAccountWithSameFundAccount()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->fixtures->create('contact', ['id' => '1000001contact', 'name' => 'Contact X']);

        $response = $this->startTest();

        $this->assertEquals($response['items'][0]['id'], $response['items'][2]['id']);
    }

    public function testBulkFundAccountWithSameIdempotencyKey()
    {
        $this->ba->batchAuth();
        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];
        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;
        $this->fixtures->create('contact', ['id' => '1000001contact', 'name' => 'Contact X']);
        $this->startTest();
    }

    // below test cases are considering duplicate checks
    // on the same contact in context
    public function testDuplicateFundAccountCreationOnApiForCard()
    {
        // we do not have duplicate checks for card account type
        $this->markTestSkipped();
    }

    public function testDuplicateFundAccountCreationOnApiForBankAccount()
    {
        $this->testCreateFundAccountBankAccount();

        $fundAccount = $this->getLastEntity('fund_account', true);

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals($fundAccount['id'], $response['id']);
    }

    public function testDuplicateFundAccountCreationOnDashboardForVpa()
    {
        $this->testCreateVpa();

        $fundAccount = $this->getLastEntity('fund_account', true);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertNotEquals($fundAccount['id'], $response['id']);
    }

    public function testDuplicateFundAccountCreationOnDashboardForBankAccount()
    {
        $this->testCreateFundAccountBankAccount();

        $fundAccount = $this->getLastEntity('fund_account', true);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals($fundAccount['id'], $response['id']);
    }

    public function testCreateDuplicateFundAccountOnApi()
    {
        $this->testCreateFundAccountBankAccount();

        $contact = $this->getLastEntity('contact', true);

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                           ->willReturn('create_duplicate');

        $this->ba->privateAuth();

        $request =  [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotEquals($contact['id'], $response['id']);
    }

    public function testFundAccountDuplicatesForDifferentContacts()
    {
        $this->testCreateFundAccountBankAccount();

        $fundAccount1 = $this->getLastEntity('fund_account');

        $this->fixtures->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $request = [
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000001contact',
                'bank_account' => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ],
            'url'     => '/fund_accounts',
            'method'  => 'POST'
        ];

        $this->makeRequestAndGetContent($request);

        $fundAccount2 = $this->getLastEntity('fund_account');

        $this->assertNotEquals($fundAccount1['id'], $fundAccount2['id']);
    }

    public function testCreateSingleCharacterHandleOfVpa()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();

        $vpa = $this->getLastEntity('vpa', true);

        $expectedVpaAttrs = [
            'entity_type' => 'contact',
            'entity_id'   => '1000000contact',
            'username'    => 'a',
            'handle'      => 'upi',
            'merchant_id' => '10000000000000',
        ];

        $this->assertArraySelectiveEquals($expectedVpaAttrs, $vpa);
    }

    public function testCreateVpaWithDot()
    {
        Queue::fake();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();

        $vpa = $this->getLastEntity('vpa', true);

        $expectedVpaAttrs = [
            'entity_type' => 'contact',
            'entity_id'   => '1000000contact',
            'username'    => 'a.mitm',
            'handle'      => 'upi',
            'merchant_id' => '10000000000000',
        ];

        $expectedHashInput = '10000000000000|contact|1000000contact|vpa|a.mitm|upi';

        $expectedHash = hash('sha3-256', $expectedHashInput);

        $fundAccount = $this->getDbLastEntity('fund_account');

        $uniqueHash = $fundAccount->getUniqueHash();

        $this->assertEquals($expectedHash, $uniqueHash);

        $this->assertArraySelectiveEquals($expectedVpaAttrs, $vpa);

        Queue::assertPushed(CreateAccount::class);
    }

    public function testFundAccountsWithExpiredKey()
    {
        $this->fixtures->key->edit('TheTestAuthKey', ['expired_at' => time()]);

        $data = $this->testData[__FUNCTION__];

        // Create Contact
        $data['request']['url'] = '/fund_accounts';
        $data['request']['method'] = 'POST';

        $this->startTest($data);

        // Fetch Contacts
        $data['request']['url'] = '/fund_accounts';
        $data['request']['method'] = 'GET';

        $this->startTest($data);

        // GET Contact
        $data['request']['url'] = '/fund_accounts/100000000000fa';
        $data['request']['method'] = 'GET';

        $this->startTest($data);

        // GET Contact
        $data['request']['url'] = '/fund_accounts/100000000000fa';
        $data['request']['method'] = 'PATCH';

        $this->startTest($data);
    }

    public function testCreateFundAccountInvalidAccountType()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateFundAccountFromInactiveCustomer()
    {
        $this->fixtures->create('customer', ['id' => '1000facustomer', 'active' => 0]);

        $this->startTest();
    }

    public function testCreateCardFundAccountFeatureS2SNotEnabled()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::PAYOUT_TO_CARDS,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->merchant->removeFeatures(['s2s']);

        $this->startTest();
    }

    public function testCreateCardFundAccountFeaturePayoutToCardsNotEnabled()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::S2S,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->merchant->removeFeatures(['payout_to_cards']);

        $this->startTest();
    }

    public function testCreateCardFundAccountWithNameAsNumeric()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testBulkFundAccountCard()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkFundAccountAmazonPay()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkFundAccountAmazonPayWithoutProvider()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkFundAccountAmazonPayWithoutEmail()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkFundAccountAmazonPayMerchantDisabled()
    {
        $this->ba->batchAuth();

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::DISABLE_X_AMAZONPAY,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkFundAccountWithoutName()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testBulkFundAccountWithInvalidBankAccountNumber()
    {
        $this->ba->batchAuth();

        $headers = [
            'HTTP_X_Batch_Id'    => 'C0zv9I46W4wiOq',
        ];

        // append headers
        $this->testData[__FUNCTION__]['request']['server'] = $headers;

        $this->startTest();
    }

    public function testCreateFundAccountForRZPFeesContact()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact', 'type' => 'rzp_fees']);

        $this->startTest();
    }

    public function testUpdateFundAccountForRZPFeesContact()
    {
        $this->testCreateFundAccountBankAccount();

        $this->fixtures->edit('contact', 'cont_1000000contact', ['type' => 'rzp_fees']);

        $fundAccount = $this->getLastEntity('fund_account');

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/fund_accounts/' . $fundAccount['id'];

        $this->startTest();
    }

    public function testDuplicateFundAccountCreationOnDashboardForBankAccountWithLowerCaseIfsc()
    {
        $this->testCreateFundAccountBankAccount();

        $fundAccount = $this->getLastEntity('fund_account', true);

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals($fundAccount['id'], $response['id']);
    }

    public function testCreateFundAccountCardWithEmptyArray()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateFundAccountVpaWithEmptyArray()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testFetchFundAccountsWithContactIdIfFundAccountsExist()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $fundAccountRequest = [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ]
        ];

        $this->ba->privateAuth();

        $this->makeRequestAndGetContent($fundAccountRequest);

        $fundAccount1 = $this->getDbLastEntity('fund_account');

        $fundAccountRequest['content']['bank_account'] = [
            'ifsc'           => 'KKBK0000958',
            'name'           => 'Amit Mah',
            'account_number' => '111000222',
        ];

        $this->makeRequestAndGetContent($fundAccountRequest);

        $fundAccount2 = $this->getDbLastEntity('fund_account');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/fund_accounts?contact_id=cont_1000000contact';

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        // Assert that there are 2 fund accounts for this contact.
        $this->assertEquals(2, count($response['items']));

        // Assert that the response has only those FA that we created above.
        $this->assertTrue(empty(array_diff(['fa_' . $fundAccount1->getId(), 'fa_' . $fundAccount2->getId()],
                                     [$response['items'][0]['id'], $response['items'][1]['id']])));
    }

    public function testFetchFundAccountsWithContactIdIfFundAccountDoesNotExist()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/fund_accounts?contact_id=cont_1000000contact';

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        // Assert that there are 0 fund accounts for this contact.
        $this->assertEquals(0, count($response['items']));
    }

    public function testFetchFundAccountsWithFundAccountIdIfFundAccountExists()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $fundAccountRequest = [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ]
        ];

        $this->ba->privateAuth();

        $this->makeRequestAndGetContent($fundAccountRequest);

        $fundAccount = $this->getDbLastEntity('fund_account');

        $fundAccountRequest['content']['bank_account'] = [
            'ifsc'           => 'KKBK0000958',
            'name'           => 'Amit Mah',
            'account_number' => '111000222',
        ];

        $this->makeRequestAndGetContent($fundAccountRequest);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/fund_accounts/fa_' . $fundAccount->getId();

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        // Assert that the response has only that FA whose id we sent  in the get request.
        $this->assertEquals('fa_' . $fundAccount->getId(), $response['id']);
    }

    public function testFetchFundAccountsWithFundAccountIdIfFundAccountDoesNotExist()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $fundAccountRequest = [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account'      => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ]
        ];

        $this->ba->privateAuth();

        $this->makeRequestAndGetContent($fundAccountRequest);

        $fundAccount = $this->getDbLastEntity('fund_account');

        $randomFundAccountId = (sprintf('%s', $fundAccount->getId()));

        $randomFundAccountId++;

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/fund_accounts/fa_' . $randomFundAccountId;

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testCreateRuPayCard()
    {
        Queue::fake();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_TO_CARDS, Feature\Constants::S2S]);

        $this->mockCardVault();

        $this->mockRazorxTreatment('payout_to_prepaid_cards');

        $this->startTest();

        $card = $this->getLastEntity('card', true);

        $expectedCardAttrs = [
            'merchant_id'   => '10000000000000',
            'expiry_month'  => 4,
            'expiry_year'   => 2025,
        ];

        $this->assertArraySelectiveEquals($expectedCardAttrs, $card);

        Queue::assertPushed(CreateAccount::class);
    }

    // check trimming in fund account creation.
    public function testCreateFundAccountWithBankAccountAndUnnecessarySpacesTrimmedInNameAndNumber()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    // check trimming in fund account creation.
    public function testCreateFundAccountWithBankAccountAndUnnecessarySpacesTrimmedInNameAndNumberAndProxyAuth()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateFundAccountBankAccountWithEmptyArrayNewApiError()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        $this->fixtures->on('test')->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateFundAccountBankAccountWithEmptyArrayNewApiErrorOnLiveMode()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        $this->fixtures->on('live')->create('contact', ['id' => '1000000contact']);

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1,]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testCreateFundAccountBankAccountWithInvalidNameNewApiError()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        $this->fixtures->on('test')->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateFundAccountBankAccountWithInvalidNameNewApiErrorOnLiveMode()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        $this->fixtures->on('live')->create('contact', ['id' => '1000000contact']);

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1,]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest();
    }

    public function testCreateFundAccountInvalidVpaArray()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateFundAccountInvalidCardArray()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateFundAccountInvalidBankAccountArray()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    // Following test depends on configs. Adding/removing configs defined in Models/FundTransfer/M2P/M2PConfigs file can fail these.
    // We need to make changes to the test sample data to pass them
    public function testCreateFundAccountDebitCard()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::VISA],
            'type'    => \RZP\Models\Card\Type::DEBIT,
            'issuer'  => Issuer::KKBK
        ]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_TO_CARDS, Feature\Constants::S2S]);

        $this->mockCardVault();

        $this->startTest();
    }

    // Following test depends on configs. Adding/removing configs defined in Models/FundTransfer/M2P/M2PConfigs file can fail these.
    // We need to make changes to the test sample data to pass them
    public function testCreateFundAccountDebitCardWithoutSupportedModes()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::MC],
            'type'    => \RZP\Models\Card\Type::DEBIT,
            'issuer'  => "default_issuer"
        ]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_TO_CARDS, Feature\Constants::S2S]);

        $this->mockCardVault();

        $this->startTest();
    }

    public function testCreateFundAccountSCBLCardwithMastercard()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_TO_CARDS, Feature\Constants::S2S]);

        $this->fixtures->create('iin', [
            'iin'     => 652161,
            'network' => Network::$fullName[Network::MC],
            'type'    => \RZP\Models\Card\Type::CREDIT,
            'issuer'  => Issuer::SCBL
        ]);

        $this->startTest();
    }

    public function testCreateFundAccountSCBLCardwithVisa()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_TO_CARDS, Feature\Constants::S2S]);

        $this->fixtures->create('iin', [
            'iin'     => 652161,
            'network' => Network::$fullName[Network::VISA],
            'type'    => \RZP\Models\Card\Type::CREDIT,
            'issuer'  => Issuer::SCBL
        ]);

        $this->startTest();
    }

    public function testCreateBankAccountFundAccountWithAllowedSpecialCharacters()
    {
        Queue::fake();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $response = $this->startTest();

        $bankAccount = $this->getLastEntity('bank_account', true);

        $expectedBankAccount = [
            'type'             => 'contact',
            'entity_id'        => '1000000contact',
            'ifsc_code'        => 'SBIN0007105',
            'account_number'   => '111000111',
            'merchant_id'      => '10000000000000',
            'beneficiary_name' => 'Amit- &M',
        ];

        $this->assertArraySelectiveEquals($expectedBankAccount, $bankAccount);

        $this->assertArrayNotHasKey(FundAccount\Entity::UNIQUE_HASH, $response);

        $expectedHashInput = '10000000000000|contact|1000000contact|bank_account|111000111|SBIN0007105|Amit-&M';

        $expectedHash = hash('sha3-256', $expectedHashInput);

        $fundAccount = $this->getDbLastEntity('fund_account');

        $uniqueHash = $fundAccount->getUniqueHash();

        $this->assertEquals($expectedHash, $uniqueHash);

        Queue::assertPushed(CreateAccount::class);
    }

    public function testCreateDuplicateBankAccountFundAccountWithExtraSpacesInName()
    {
        $this->testCreateBankAccountFundAccountWithAllowedSpecialCharacters();

        $noOfFundAccountsBeforeSendingDuplicateRequest = count($this->getDbEntities('fund_account'));

        $noOfBankAccountsBeforeSendingDuplicateRequest = count($this->getDbEntities('bank_account'));

        $this->ba->privateAuth();

        $this->startTest();

        $noOfFundAccountsAfterSendingDuplicateRequest = count($this->getDbEntities('fund_account'));

        $noOfBankAccountsAfterSendingDuplicateRequest = count($this->getDbEntities('bank_account'));

        // We check that no new fund account or bank account is created if extra spaces are sent in the bank account
        // name.
        $this->assertEquals($noOfBankAccountsBeforeSendingDuplicateRequest,
                            $noOfBankAccountsAfterSendingDuplicateRequest);

        $this->assertEquals($noOfFundAccountsBeforeSendingDuplicateRequest,
                            $noOfFundAccountsAfterSendingDuplicateRequest);
    }

    public function testCreateTwoDifferentFundAccountsAndVerifyTheyHaveDifferentHashes()
    {
        $this->testCreateFundAccountBankAccount();

        $fundAccount1 = $this->getDbLastEntity('fund_account');

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__] = $this->testData['testCreateBankAccountFundAccountWithAllowedSpecialCharacters'];

        $this->startTest();

        $fundAccount2 = $this->getDbLastEntity('fund_account');

        $this->assertNotEquals($fundAccount1->getUniqueHash(), $fundAccount2->getUniqueHash());
    }

    public function testUpdationOfExistingDuplicateFundAccountWithHash()
    {
        $this->testCreateFundAccountBankAccount();

        $fundAccount = $this->getDbLastEntity('fund_account');

        $this->fixtures->edit(
            'fund_account',
            $fundAccount->getId(),
            [
                FundAccount\Entity::UNIQUE_HASH => null,
            ]
        );

        $this->ba->privateAuth();

        $this->startTest();

        $fundAccount->reload();

        $this->assertNotNull($fundAccount->getUniqueHash());
    }

    public function testTaxPaymentFundAccountCreationSuccessAndVerifyHashCreation()
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

        $expectedHashInput = '10000000000000|contact|' . $contact->getId() .
                             '|bank_account|000205031288|ICIC0000020|testname';

        $expectedHash = hash('sha3-256', $expectedHashInput);

        $fundAccount = $this->getDbLastEntity('fund_account');

        $uniqueHash = $fundAccount->getUniqueHash();

        $this->assertEquals($expectedHash, $uniqueHash);
    }

    public function testNullSourceFundAccountCreationAndVerifyHash()
    {
        $response = $this->createValidationWithFundAccountEntity();

        $bankAccount = $this->getLastEntity('bank_account', true);

        $expectedBankAccount = [
            'type'             => null,
            'entity_id'        => null,
            'ifsc_code'        => 'SBIN0010411',
            'account_number'   => '123456789',
            'merchant_id'      => '10000000000000',
            'beneficiary_name' => 'Rohit Keshwani',
        ];

        $this->assertArraySelectiveEquals($expectedBankAccount, $bankAccount);

        $this->assertArrayNotHasKey(FundAccount\Entity::UNIQUE_HASH, $response['fund_account']);

        $expectedHashInput = '10000000000000|||bank_account|123456789|SBIN0010411|RohitKeshwani';

        $expectedHash = hash('sha3-256', $expectedHashInput);

        $fundAccount = $this->getDbLastEntity('fund_account');

        $uniqueHash = $fundAccount->getUniqueHash();

        $this->assertEquals($expectedHash, $uniqueHash);
    }

    // We wish to test that a fund account of type vpa with a different case in username and handle is found via
    // fallback and the hash is updated properly for it.
    public function testUpdationOfExistingDuplicateVpaFundAccountWithHashWithDifferentCaseInInputAndDuplicate()
    {
        $this->testCreateVpa();

        $fundAccount = $this->getDbLastEntity('fund_account');

        $this->fixtures->edit(
            'fund_account',
            $fundAccount->getId(),
            [
                FundAccount\Entity::UNIQUE_HASH => null,
            ]
        );

        $this->ba->privateAuth();

        $this->startTest();

        $fundAccount->reload();

        $this->assertNotNull($fundAccount->getUniqueHash());
    }

    // We are just checking that if hash creation is on, we should get duplicate fund account via hash even if the
    // incoming request has a different case in vpa address cas compared to the duplicate in our db.
     public function testDuplicateVpaFundAccountFoundViaHashWithDifferentCaseInInputAndDuplicate()
     {
         $this->testCreateVpa();

         $fundAccountsBeforeDuplicateCreateRequest = count($this->getDbEntities('fund_account'));

         $this->testData[__FUNCTION__] =
             $this->testData['testUpdationOfExistingDuplicateVpaFundAccountWithHashWithDifferentCaseInInputAndDuplicate'];

         $this->ba->privateAuth();

         $this->startTest();

         $fundAccountsAfterDuplicateCreateRequest = count($this->getDbEntities('fund_account'));

         $this->assertEquals($fundAccountsBeforeDuplicateCreateRequest, $fundAccountsAfterDuplicateCreateRequest);
     }

    // 'merchant_disabled' field should be sent in the response for Fund Account GET requests
    // received from dashboard.
    // This test case checks the case where it should be set to true for amazonpay FA (merchant disabled on amazonpay)
    // and false for FA of type bank_account
    public function testFetchFundAccountsDashboardRequestMerchantDisabledForAmazonPay()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $fundAccountRequest1 = [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account' => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ]
        ];

        $fundAccountRequest2 = [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'content' => [
                'account_type' => 'wallet',
                'contact_id'   => 'cont_1000000contact',
                'wallet'       => [
                    'phone'         => '+918124632237',
                    'provider'      => 'amazonpay',
                    'email'         => 'test@gmail.com',
                ],
            ]
        ];

        $this->makeRequestAndGetContent($fundAccountRequest1);

        $fundAccount1 = $this->getDbLastEntity('fund_account');

        $this->makeRequestAndGetContent($fundAccountRequest2);

        $fundAccount2 = $this->getDbLastEntity('fund_account');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/fund_accounts?contact_id=cont_1000000contact';

        $this->testData[__FUNCTION__] = $testData;

        // Disabling amazonpay for the merchant
        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::DISABLE_X_AMAZONPAY,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $response = $this->startTest();

        // Assert that there are 2 fund accounts for this contact.
        $this->assertEquals(2, count($response['items']));

        // Assert that the response has only those FA that we created above.
        $this->assertTrue(empty(array_diff(['fa_' . $fundAccount1->getId(), 'fa_' . $fundAccount2->getId()],
            [$response['items'][0]['id'], $response['items'][1]['id']])));

        // Assert if merchant_disabled is set to true for wallet account
        $this->assertEquals("wallet", $response['items'][0]['account_type']);
        $this->assertEquals(true, $response['items'][0]['merchant_disabled']);

        // Assert if merchant_disabled is set to false for bank account
        $this->assertEquals("bank_account", $response['items'][1]['account_type']);
        $this->assertEquals(false, $response['items'][1]['merchant_disabled']);
    }

    // 'merchant_disabled' field should be sent in the response for Fund Account GET requests
    // received from dashboard.
    // This test case checks the case where it should be set to false for amazonpay FA (merchant enabled on amazonpay)
    // and false for FA of type bank_account too
    public function testFetchFundAccountsDashboardRequestMerchantEnabledForAmazonPay()
    {
        $this->ba->proxyAuth();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $fundAccountRequest1 = [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account' => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ]
        ];

        $fundAccountRequest2 = [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'content' => [
                'account_type' => 'wallet',
                'contact_id'   => 'cont_1000000contact',
                'wallet'       => [
                    'phone'         => '+918124632237',
                    'provider'      => 'amazonpay',
                    'email'         => 'test@gmail.com',
                ],
            ]
        ];

        $this->makeRequestAndGetContent($fundAccountRequest1);

        $fundAccount1 = $this->getDbLastEntity('fund_account');

        $this->makeRequestAndGetContent($fundAccountRequest2);

        $fundAccount2 = $this->getDbLastEntity('fund_account');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/fund_accounts?contact_id=cont_1000000contact';

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        // Assert that there are 2 fund accounts for this contact.
        $this->assertEquals(2, count($response['items']));

        // Assert that the response has only those FA that we created above.
        $this->assertTrue(empty(array_diff(['fa_' . $fundAccount1->getId(), 'fa_' . $fundAccount2->getId()],
            [$response['items'][0]['id'], $response['items'][1]['id']])));

        // Assert if merchant_disabled is set to false for wallet account
        $this->assertEquals("wallet", $response['items'][0]['account_type']);
        $this->assertEquals(false, $response['items'][0]['merchant_disabled']);

        // Assert if merchant_disabled is set to false for bank account
        $this->assertEquals("bank_account", $response['items'][1]['account_type']);
        $this->assertEquals(false, $response['items'][1]['merchant_disabled']);
    }

    // fund account GET responses for api requests should not contain
    // 'merchant_disabled' field
    public function testFetchFundAccountsApiRequestNoMerchantDisabledField()
    {
        $this->ba->privateAuth();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $fundAccountRequest1 = [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'content' => [
                'account_type' => 'bank_account',
                'contact_id'   => 'cont_1000000contact',
                'bank_account' => [
                    'ifsc'           => 'SBIN0007105',
                    'name'           => 'Amit M',
                    'account_number' => '111000111',
                ],
            ]
        ];

        $fundAccountRequest2 = [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'content' => [
                'account_type' => 'wallet',
                'contact_id'   => 'cont_1000000contact',
                'wallet'       => [
                    'phone'         => '+918124632237',
                    'provider'      => 'amazonpay',
                    'email'         => 'test@gmail.com',
                ],
            ]
        ];

        $this->makeRequestAndGetContent($fundAccountRequest1);

        $fundAccount1 = $this->getDbLastEntity('fund_account');

        $this->makeRequestAndGetContent($fundAccountRequest2);

        $fundAccount2 = $this->getDbLastEntity('fund_account');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/fund_accounts?contact_id=cont_1000000contact';

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        // Assert that there are 2 fund accounts for this contact.
        $this->assertEquals(2, count($response['items']));

        // Assert that the response has only those FA that we created above.
        $this->assertTrue(empty(array_diff(['fa_' . $fundAccount1->getId(), 'fa_' . $fundAccount2->getId()],
            [$response['items'][0]['id'], $response['items'][1]['id']])));

        // Assert if merchant_disabled is not set for wallet account
        $this->assertEquals("wallet", $response['items'][0]['account_type']);
        $this->assertArrayNotHasKey('merchant_disabled', $response['items'][0]);

        // Assert if merchant_disabled is not set for bank account
        $this->assertEquals("bank_account", $response['items'][1]['account_type']);
        $this->assertArrayNotHasKey('merchant_disabled', $response['items'][1]);
    }

    // Tests the case where wallet fund account create request is received from
    // dashboard. The response should have 'merchant_disabled' field set to false
    // as merchant should be enabled to create fund account
    public function testCreateFundAccountWalletDashboardRequestMerchantDisabledField()
    {
        Queue::fake();

        $this->ba->proxyAuth();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $response = $this->startTest();

        // Assert that response has merchant_disabled field and set
        // to false (as FA creation is only possible when merchant is enabled)
        $this->assertArrayHasKey('merchant_disabled', $response);
        $this->assertEquals(false, $response['merchant_disabled']);
    }

    // Tests the case where bank account create request is received from
    // dashboard. The response should have 'merchant_disabled' field set to false as
    // merchant should be enabled to create fund account
    public function testCreateFundAccountBankAccountDashboardRequestMerchantDisabledField()
    {
        Queue::fake();

        $this->ba->proxyAuth();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $response = $this->startTest();

        // Assert that response has merchant_disabled field and set
        // to false (as FA creation is only possible when merchant is enabled)
        $this->assertArrayHasKey('merchant_disabled', $response);
        $this->assertEquals(false, $response['merchant_disabled']);
    }

    // Tests the case where wallet fund account create request is received from
    // api. The response shouldn't have merchant_disabled field
    public function testCreateFundAccountWalletApiRequestNoMerchantDisabledField()
    {
        Queue::fake();

        $this->ba->privateAuth();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $response = $this->startTest();

        // Assert that merchant_disabled field is not set in the response
        $this->assertArrayNotHasKey('merchant_disabled', $response);
    }

    // Tests the case where bank account create request is received from
    // api. The response shouldn't have merchant_disabled field
    public function testCreateFundAccountBankAccountApiRequestNoMerchantDisabledField()
    {
        Queue::fake();

        $this->ba->privateAuth();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $response = $this->startTest();

        // Assert that merchant_disabled field is not set in the response
        $this->assertArrayNotHasKey('merchant_disabled', $response);
    }

    public function testFundAccountDetailsPushedToQueue()
    {
        Queue::fake();

        $this->ba->privateAuth();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();

        Queue::assertPushed(FundAccountDetailsPropagatorJob::class,1);
    }

    public function testCapitalCollectionsInternalContactFundaccountCreation()
    {
        $this->ba->capitalCollectionsAuth();

        $contact = $this->fixtures->create('contact',
            [
                'name' => 'test name',
                'type' => Type::CAPITAL_COLLECTIONS_INTERNAL_CONTACT
            ]);

        $this->testData[__FUNCTION__]['request']['content']['contact_id'] = $contact->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['contact_id'] = $contact->getPublicId();

        $this->startTest();

        $fundAccount = $this->getDbLastEntity('fund_account');

        $this->assertEquals($fundAccount->contact['id'], $contact['id']);
    }

    public function testCapitalCollectionsInternalContactFundaccountCreationByOtherInternalAppFailure()
    {
        $contact = $this->fixtures->create('contact',
            [
                'name' => 'test name',
                'type' => Type::CAPITAL_COLLECTIONS_INTERNAL_CONTACT
            ]);

        $this->testData[__FUNCTION__]['request']['content']['contact_id'] = $contact->getPublicId();

        $this->ba->xPayrollAuth();

        $this->startTest();
    }

    public function testXpayrollInternalContactFundaccountCreation()
    {
        $this->ba->xPayrollAuth();

        $contact = $this->fixtures->create('contact',
            [
                'name' => 'test name',
                'type' => Type::XPAYROLL_INTERNAL
            ]);

        $this->testData[__FUNCTION__]['request']['content']['contact_id'] = $contact->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['contact_id'] = $contact->getPublicId();

        $this->startTest();

        $fundAccount = $this->getDbLastEntity('fund_account');

        $contactDb = $this->getDbLastEntity('contact');

        $this->assertEquals($contactDb['type'],'rzp_xpayroll');

        $this->assertEquals($contactDb['id'],$contact['id']);

        $this->assertEquals($fundAccount->contact['id'],$contact['id']);
    }

    public function testXpayrollInternalContactFundaccountCreationByOtherInternalAppFailure()
    {
        $this->ba->xPayrollAuth();

        $contact = $this->fixtures->create('contact',
            [
                'name' => 'test name',
                'type' => Type::XPAYROLL_INTERNAL
            ]);

        $this->testData[__FUNCTION__]['request']['content']['contact_id'] = $contact->getPublicId();

        $this->ba->payoutLinksAppAuth();

        $this->startTest();
    }
}
