<?php

namespace RZP\Tests\Functional\FundAccount;

use App;
use Queue;

use RZP\Error\Error;
use RZP\Models\Feature;
use RZP\Models\Card\Issuer;
use RZP\Models\Card\Network;
use RZP\Models\Contact\Type;
use RZP\Services\RazorXClient;
use RZP\Jobs\FTS\CreateAccount;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class FundAccountsTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

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

    public function testCreateFundAccountBankAccountPublic()
    {
        $this->ba->publicAuth();

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $response = $this->startTest();
    }

    public function testCreateFundAccountBankAccountBeneficiaryVerified()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();

        $bankAccount = $this->getLastEntity('bank_account', true);

        $nodalBeneficiary = $this->getLastEntity('nodal_beneficiary', true);

        // Verify Nodal Beneficiary entity
        $this->assertNotNull($nodalBeneficiary['id']);
        $this->assertEquals('verified', $nodalBeneficiary['registration_status']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$nodalBeneficiary['bank_account_id']);

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

        $this->startTest();

        $vpa = $this->getLastEntity('vpa', true);

        $expectedVpaAttrs = [
            'entity_type' => 'contact',
            'entity_id'   => '1000000contact',
            'username'    => 'amitm',
            'handle'      => 'upi',
            'merchant_id' => '10000000000000',
        ];

        $this->assertArraySelectiveEquals($expectedVpaAttrs, $vpa);

        Queue::assertPushed(CreateAccount::class);
    }

    public function testCreateWalletAccountFundAccount()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('on');

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

    public function testCreateWalletAccountFundAccountPhoneNull()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('on');

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateWalletAccountFundAccountPhoneEmpty()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('on');

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateWalletAccountFundAccountModeCapital()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('on');

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateWalletAccountFundAccountForNonwhitelistedMerchant()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('control');

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateWalletAccountFundAccountPhoneFormat1()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('on');

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
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('on');

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
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('on');
                          
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

        $this->startTest();
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

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                  ->willReturn('create_duplicate');

        $this->startTest();

        $contacts = $this->getEntities('contact');

        $fundAccounts = $this->getEntities('fund_account');

        $this->assertEquals(3, count($contacts['items']));

        $this->assertEquals(3, count($fundAccounts['items']));
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

        $fund_account1 = $this->getDbLastEntity('fund_account');

        $fundAccountRequest['content']['bank_account'] = [
            'ifsc'           => 'KKBK0000958',
            'name'           => 'Amit Mah',
            'account_number' => '111000222',
        ];

        $this->makeRequestAndGetContent($fundAccountRequest);

        $fund_account2 = $this->getDbLastEntity('fund_account');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/fund_accounts?contact_id=cont_1000000contact';

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        // Assert that there are 2 fund accounts for this contact.
        $this->assertEquals(2, count($response['items']));

        // Assert that the response has only those FA that we created above.
        $this->assertTrue(empty(array_diff(['fa_' . $fund_account1->getId(), 'fa_' . $fund_account2->getId()],
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

        $fund_account = $this->getDbLastEntity('fund_account');

        $fundAccountRequest['content']['bank_account'] = [
            'ifsc'           => 'KKBK0000958',
            'name'           => 'Amit Mah',
            'account_number' => '111000222',
        ];

        $this->makeRequestAndGetContent($fundAccountRequest);

        $fund_account2 = $this->getDbLastEntity('fund_account');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/fund_accounts/fa_' . $fund_account->getId();

        $this->testData[__FUNCTION__] = $testData;

        $response = $this->startTest();

        // Assert that the response has only that FA whose id we sent  in the get request.
        $this->assertEquals('fa_' . $fund_account->getId(), $response['id']);
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

        $fund_account = $this->getDbLastEntity('fund_account');

        $randomFundAccountId = (sprintf('%s', $fund_account->getId()));

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

    // check trimming in fund account creation when experiment is not on for merchant.
    public function testCreateFundAccountWithBankAccountAndUnnecessarySpacesTrimmedInNameAndNumber()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    // don't trim in fund account creation when experiment is on for merchant.
    public function testCreateFundAccountWithBankAccountAndUnnecessarySpacesInNameAndNumber()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    // check trimming in fund account creation when experiment is not on for merchant.
    public function testCreateFundAccountWithBankAccountAndUnnecessarySpacesTrimmedInNameAndNumberAndProxyAuth()
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateFundAccountBankAccountWithEmptyArrayNewApiError()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->startTest();
    }

    public function testCreateFundAccountBankAccountWithInvalidNameNewApiError()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        $this->fixtures->create('contact', ['id' => '1000000contact']);

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
}
