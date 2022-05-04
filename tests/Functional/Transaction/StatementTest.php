<?php

namespace RZP\Tests\Functional\Transaction;

use RZP\Models\Feature;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\InvalidArgumentException;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\FundAccount\FundAccountTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;
use RZP\Tests\Functional\Helpers\FundAccount\FundAccountValidationTrait;

class StatementTest extends TestCase
{
    use PaymentTrait;
    use FundAccountTrait;
    use DbEntityFetchTrait;
    use VirtualAccountTrait;
    use TestsBusinessBanking;
    use FundAccountValidationTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/StatementTestData.php';

        parent::setUp();

        $this->setUpMerchantForBusinessBanking(false, 100000);
    }

    public function testFetchMultipleStatements()
    {
        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->fixtures->edit('merchant', '10000000000000', ['business_banking' => true]);

        // Creates two bank transfer transaction on banking balance.
        $this->createBankTransferTransaction();
        $this->createBankTransferTransaction();

        $this->createPayout();

        $payout = $this->getDbEntity('payout');

        $this->reversePayout($payout);

        // Creates one normal payment transaction on primary balance.
        $this->doAuthAndCapturePayment(null, 50000);

        // One the first two transactions should appear in response.
        $this->ba->privateAuth();

        $response = $this->startTest();

        // Asserts other keys existence in items - for bank_transfer txn
        $statement = $response['items'][2];
        $this->assertNotEmpty($statement['id']);
        $this->assertNotEmpty($statement['created_at']);
        $this->assertNotEmpty($statement['source']['id']);
        $this->assertNotEmpty($statement['source']['bank_reference']);
        $this->assertNotEmpty($statement['source']['payee_account']);
    }

    public function testFetchMultipleStatementsForBanking()
    {
        //TODO : Testcase has to be fixed
        $this->markTestSkipped("Skipping Testcase, Need to be fixed");

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->fixtures->edit('merchant', '10000000000000', ['business_banking' => true]);

        // Creates two bank transfer transaction on banking balance.
        $this->createBankTransferTransaction();
        $this->createBankTransferTransaction();

        $this->createPayout();

        $payout = $this->getDbEntity('payout');

        $this->reversePayout($payout);

        // Creates one normal payment transaction on primary balance.
        $this->doAuthAndCapturePayment(null, 50000);

        $user =  (new User())->createBankingUserForMerchant('10000000000000', [
            'contact_mobile' => '8888888888',
        ],'admin');

        $this->fixtures->create('merchant_attribute',
            [
                'merchant_id' => '10000000000000',
                'product'     => 'banking',
                'group'       => 'x_transaction_view',
                'type'        => 'admin',
                'value'       => 'true'
            ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'      =>  '10000000000000',
            'business_name'    =>  'Test Name Private Limited ltd ltd. Liability partnership',
            'business_website' =>  'https://shopify.secondleveldomain.edu.in'
        ]);

        // One the first two transactions should appear in response.
        $this->ba->privateAuth();

        $response = $this->startTest();

        // Asserts other keys existence in items - for bank_transfer txn
        $statement = $response['items'][2];
        $this->assertNotEmpty($statement['id']);
        $this->assertNotEmpty($statement['created_at']);
        $this->assertNotEmpty($statement['source']['id']);
        $this->assertNotEmpty($statement['source']['bank_reference']);
        $this->assertNotEmpty($statement['source']['payee_account']);
    }

    public function testFetchStatement()
    {
        $this->createBankTransferTransaction();

        $transaction = $this->getDbLastEntity('transaction');
        $this->testData[__FUNCTION__]['request']['url'] = '/transactions/' . $transaction->getPublicId();

        $this->ba->privateAuth();
        $response = $this->startTest();

        // Asserts other keys existence in response.
        $this->assertNotEmpty($response['id']);
        $this->assertNotEmpty($response['created_at']);
        $this->assertNotEmpty($response['source']['id']);
        $this->assertNotEmpty($response['source']['bank_reference']);
        $this->assertNotEmpty($response['source']['payee_account']);
    }

    //merchant has rules and hitting a route with access control policies allowed with role allowed
    public function testFetchStatementWithAttributesPermissionTrue()
    {
        $this->createBankTransferTransaction();

        $transaction = $this->getDbLastEntity('transaction');

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] =  'https://x.razorpay.com';

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions/' . $transaction->getPublicId();

        $user =  (new User())->createBankingUserForMerchant('10000000000000', [
            'contact_mobile' => '8888888888',
        ],'admin');

        $this->fixtures->create('merchant_attribute',
            [
                'merchant_id' => '10000000000000',
                'product'     => 'banking',
                'group'       => 'x_transaction_view',
                'type'        => 'admin',
                'value'       => 'true'
            ]);

       $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

       $this->startTest();
    }

    public function testFetchStatementBankingWithAttributesPermissionTrue()
    {
        $this->createBankTransferTransaction();

        $transaction = $this->getDbLastEntity('transaction');

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] =  'https://x.razorpay.com';

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking';

        $user =  (new User())->createBankingUserForMerchant('10000000000000', [
            'contact_mobile' => '8888888888',
        ],'admin');

        $this->fixtures->create('merchant_attribute',
            [
                'merchant_id' => '10000000000000',
                'product'     => 'banking',
                'group'       => 'x_transaction_view',
                'type'        => 'admin',
                'value'       => 'true'
            ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();
    }

    //merchant has rules and hitting a route with access control policies allowed with role not allowed
    public function testFetchStatementWithAttributesPermissionFalse()
    {

        $this->createBankTransferTransaction();

        $transaction = $this->getDbLastEntity('transaction');

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] =  'https://x.razorpay.com';

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions/' . $transaction->getPublicId();

        $user =  (new User())->createBankingUserForMerchant('10000000000000', [
            'contact_mobile' => '8888888888',
        ],'admin');

        $this->fixtures->create('merchant_attribute',
            [
                'merchant_id' => '10000000000000',
                'product'     => 'banking',
                'group'       => 'x_transaction_view',
                'type'        => 'admin',
                'value'       => 'false'
            ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();
    }

    //merchant has no rules and hitting a route with access control policies allowed
    public function testFetchStatementWithNoAttributes()
    {
        $this->createBankTransferTransaction();

        $transaction = $this->getDbLastEntity('transaction');

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] =  'https://x.razorpay.com';

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions/' . $transaction->getPublicId();

        $user =  (new User())->createBankingUserForMerchant('10000000000000', [
            'contact_mobile' => '8888888888',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();
    }

    public function testFetchStatementWithNoAttributesWithOperationsRole()
    {
        $this->createBankTransferTransaction();

        $transaction = $this->getDbLastEntity('transaction');

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] =  'https://x.razorpay.com';

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions/' . $transaction->getPublicId();

        $user =  (new User())->createBankingUserForMerchant('10000000000000', [
            'contact_mobile' => '8888888888',
        ],'operations');

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();
    }

    //merchant has rules and hitting a route with no access control policies allowed
    public function testGetBillingLabelWithMerchantAttributesTrueForTransactionFetch()
    {
        $this->fixtures->create('merchant_detail', [
            'merchant_id'      =>  '10000000000000',
            'business_name'    =>  'Test Name Private Limited ltd ltd. Liability partnership',
            'business_website' =>  'https://shopify.secondleveldomain.edu.in'
        ]);

        $this->fixtures->create('merchant_attribute',
            [
                'merchant_id' => '10000000000000',
                'product'     => 'banking',
                'group'       => 'x_transaction_view',
                'type'        => 'admin',
                'value'       => 'false'
            ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function createMerchantAttribute(string $merchant_id, string $product, string $group, string $type, string $value)
    {
        $this->fixtures->create('merchant_attribute',
            [
                'merchant_id'   => $merchant_id,
                'product'       => $product,
                'group'         => $group,
                'type'          => $type,
                'value'         => $value,
                'updated_at'    => time(),
                'created_at'    => time()
            ]);
    }

    public function testFetchMultipleStatementsWithMerchantRules()
    {
        $this->createBankTransferTransaction();

        $this->createBankTransferTransaction();


        $transaction = $this->getDbLastEntity('transaction');

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] =  'https://x.razorpay.com';

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions';

        $user =  (new User())->createBankingUserForMerchant('10000000000000', [
            'contact_mobile' => '8888888888',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();
    }

    public function testFetchStatementForPayoutFromLedger()
    {
        $this->app['config']->set('applications.ledger.enabled', false);

        $this->createPayout([
            'id' => 'payout00000001'
        ]);

        $transaction = $this->getDbLastEntity('transaction');
        $payoutCreated = $this->getDbLastEntity('payout');

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions/' . $transaction->getPublicId();

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->fixtures->create('banking_account', [
            'id'                    => 'JLcwWU3SsZ7byJ',
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'yesbank',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $this->ba->privateAuth();
        $response = $this->startTest();

        // Asserts other keys existence in response.
        $this->assertNotEmpty($response['id']);
        $this->assertNotEmpty($response['created_at']);
        $this->assertNotEmpty($response['source']['id']);
        $this->assertNotEmpty($response['source']['entity']);
        $this->assertNotEmpty($response['source']['amount']);
        $this->assertEquals($response['source']['id'], $payoutCreated->getPublicId());
    }

    public function testFetchStatementForDirectPayoutFromLedger()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $this->createPayout([
            'id' => 'payout00000001'
        ]);

        $transaction = $this->getDbLastEntity('transaction');
        $payoutCreated = $this->getDbLastEntity('payout');

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions/' . $transaction->getPublicId();

        $this->fixtures->merchant->addFeatures([Feature\Constants::DA_LEDGER_REVERSE_SHADOW]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('fetchById')
            ->times(1)
            ->andReturn([
                "code" => 200,
                "body" => [
                    "id"                => $transaction['id'],
                    "created_at"        => "1634027277",
                    "updated_at"        => "1634027277",
                    "amount"            => "1590",
                    "base_amount"       => "130.000000",
                    "currency"          => "INR",
                    "tenant"            => "X",
                    "transactor_id"     => "pout_payout00000001",
                    "transactor_event"  => "da_payout_processed",
                    "transaction_date"  => "1611132045",
                    "ledger_entry" => [
                        [
                            "id"          => "I8MJlgVttAs4KQ",
                            "created_at"  => "1634027277",
                            "updated_at"  => "1634027277",
                            "merchant_id" => "10000000000000",
                            "journal_id"  => $transaction['id'],
                            "account_id"  => "GoRNyEuu9Hl0OZ",
                            "amount"      => "1590",
                            "base_amount" => "1590",
                            "type"        => "debit",
                            "currency"    => "INR",
                            "balance"     => "98410",
                            "account_entities" => [
                                "account_type"                      => ["payable"],
                                "fund_account_type"                 => ["merchant_da"],
                                "banking_account_stmt_detail_id"    => ["basd_JLcwWU3SsZ7byJ"]
                            ]
                        ],
                        [
                            "id"          => "HNjsypHPOUlxDR",
                            "created_at"  => "1634027277",
                            "updated_at"  => "1634027277",
                            "merchant_id" => "10000000000000",
                            "journal_id"  => $transaction['id'],
                            "account_id"  => "HN5AGgmKu0ki13",
                            "amount"      => "1590",
                            "base_amount" => "1590",
                            "type"        => "credit",
                            "currency"    => "INR",
                            "balance"     => "984100",
                            "account_entities" => [
                                "account_type"                      => ["payable"],
                                "fund_account_type"                 => ["merchant_da_vendor"],
                                "banking_account_stmt_detail_id"    => ["basd_JLcwWU3SsZ7byJ"]
                            ]
                        ]
                    ]
                ]
            ]);

        $this->fixtures->create('banking_account_statement', [
            'type'                      => 'debit',
            'amount'                    => '1590',
            'channel'                   => 'rbl',
            'utr'                       => '123456',
            'account_number'            => '2224440041626905',
            'transaction_id'            => $payoutCreated['transaction_id'],
            'entity_id'                 => $payoutCreated['id'],
            'entity_type'               => 'payout',
            'bank_transaction_id'       => 'SDHDH',
            'balance'                   => 98410,
            'transaction_date'          => 1584987183
        ]);

        $this->fixtures->create('banking_account_statement_details',[
            'id'                    => 'JLcwWU3SsZ7byJ',
            'merchant_id'           => '10000000000000',
            'account_number'        => '2224440041626905',
            'channel'               => 'rbl',
            'status'                => 'active',
        ]);

        $basCreated = $this->getDbLastEntity('banking_account_statement');

        $this->ba->privateAuth();
        $response = $this->startTest();

        // Asserts other keys existence in response.
        $this->assertNotEmpty($response['id']);
        $this->assertNotEmpty($response['created_at']);
        $this->assertNotEmpty($response['source']['id']);
        $this->assertNotEmpty($response['source']['entity']);
        $this->assertNotEmpty($response['source']['amount']);
        $this->assertEquals($response['source']['id'], $payoutCreated->getPublicId());
    }

    public function testFetchStatementForDirectReversalFromLedger()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $this->createPayout([
            'id' => 'payout00000001'
        ]);

        $payout = $this->getDbLastEntity('payout');

        $reversalCreated = $this->fixtures->create('reversal', [
            'id'              => 'revrsl00000002',
            'merchant_id'     => '10000000000000',
            'utr'             => '123456',
            'balance_id'      => $payout['balance_id'],
            'amount'          => '1590',
            'channel'         => 'rbl',
            'entity_type'     => 'payout',
            'entity_id'       => $payout['id']
        ]);
        $transaction = $this->getDbLastEntity('transaction');

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions/' . $transaction->getPublicId();

        $this->fixtures->merchant->addFeatures([Feature\Constants::DA_LEDGER_REVERSE_SHADOW]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('fetchById')
            ->times(1)
            ->andReturn([
                "code" => 200,
                "body" => [
                    "id"                => $transaction['id'],
                    "created_at"        => "1634027277",
                    "updated_at"        => "1634027277",
                    "amount"            => "1590",
                    "base_amount"       => "130.000000",
                    "currency"          => "INR",
                    "tenant"            => "X",
                    "transactor_id"     => "rvrsl_revrsl00000002",
                    "transactor_event"  => "da_payout_reversed",
                    "transaction_date"  => "1611132045",
                    "ledger_entry" => [
                        [
                            "id"          => "I8MJlgVttAs4KQ",
                            "created_at"  => "1634027277",
                            "updated_at"  => "1634027277",
                            "merchant_id" => "10000000000000",
                            "journal_id"  => $transaction['id'],
                            "account_id"  => "GoRNyEuu9Hl0OZ",
                            "amount"      => "1590",
                            "base_amount" => "credit",
                            "type"        => "debit",
                            "currency"    => "INR",
                            "balance"     => "98410",
                            "account_entities" => [
                                "account_type"                      => ["payable"],
                                "fund_account_type"                 => ["merchant_da"],
                                "banking_account_stmt_detail_id"    => ["basd_JLcwWU3SsZ7byJ"]
                            ]
                        ],
                        [
                            "id"          => "HNjsypHPOUlxDR",
                            "created_at"  => "1634027277",
                            "updated_at"  => "1634027277",
                            "merchant_id" => "10000000000000",
                            "journal_id"  => $transaction['id'],
                            "account_id"  => "HN5AGgmKu0ki13",
                            "amount"      => "1590",
                            "base_amount" => "1590",
                            "type"        => "debit",
                            "currency"    => "INR",
                            "balance"     => "984100",
                            "account_entities" => [
                                "account_type"                      => ["payable"],
                                "fund_account_type"                 => ["merchant_da_vendor"],
                                "banking_account_stmt_detail_id"    => ["basd_JLcwWU3SsZ7byJ"]
                            ]
                        ]
                    ]
                ]
            ]);

        $this->fixtures->create('banking_account_statement', [
            'type'                      => 'credit',
            'amount'                    => '1590',
            'channel'                   => 'rbl',
            'utr'                       => '123456',
            'account_number'            => '2224440041626905',
            'transaction_id'            => $reversalCreated['transaction_id'],
            'entity_id'                 => $reversalCreated['id'],
            'entity_type'               => 'reversal',
            'bank_transaction_id'       => 'SDHDH',
            'balance'                   => 98410,
            'transaction_date'          => 1584987183
        ]);

        $this->fixtures->create('banking_account_statement_details',[
            'id'                    => 'JLcwWU3SsZ7byJ',
            'merchant_id'           => '10000000000000',
            'account_number'        => '2224440041626905',
            'channel'               => 'rbl',
            'status'                => 'active',
        ]);

        $basCreated = $this->getDbLastEntity('banking_account_statement');

        $this->ba->privateAuth();
        $response = $this->startTest();

        // Asserts other keys existence in response.
        $this->assertNotEmpty($response['id']);
        $this->assertNotEmpty($response['created_at']);
        $this->assertNotEmpty($response['source']['id']);
        $this->assertNotEmpty($response['source']['entity']);
        $this->assertNotEmpty($response['source']['amount']);
        $this->assertEquals($response['source']['id'], $reversalCreated->getPublicId());
    }

    public function testFetchStatementForExternalEntityFromLedger()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $this->fixtures->create('transaction', [
            'id'          => '00000000000001',
            'merchant_id' => '10000000000000',
            'amount'      => '1590',
            'type'        => 'external',
            'entity_id'   => 'extrnl00000001',
            'balance_id'  => $this->bankingBalance->getId(),
        ]);
        $transaction = $this->getDbLastEntity('transaction');

        // create a external for this payout so it gets picked
        $this->fixtures->create('external', [
            'id'             => 'extrnl00000001',
            'merchant_id'    => '10000000000000',
            'amount'         => '1590',
            'channel'        => 'rbl',
            'transaction_id' => $transaction['id'],
            'utr'            => '123456',
            'balance_id'  => $this->bankingBalance->getId(),
        ]);
        $externalCreated = $this->getDbLastEntity('external');

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions/' . $transaction->getPublicId();

        $this->fixtures->merchant->addFeatures([Feature\Constants::DA_LEDGER_REVERSE_SHADOW]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('fetchById')
            ->times(1)
            ->andReturn([
                "code" => 200,
                "body" => [
                    "id"                => $transaction['id'],
                    "created_at"        => "1634027277",
                    "updated_at"        => "1634027277",
                    "amount"            => "1590",
                    "base_amount"       => "130.000000",
                    "currency"          => "INR",
                    "tenant"            => "X",
                    "transactor_id"     => "ext_extrnl00000001",
                    "transactor_event"  => "da_ext_debit",
                    "transaction_date"  => "1611132045",
                    "ledger_entry" => [
                        [
                            "id"          => "I8MJlgVttAs4KQ",
                            "created_at"  => "1634027277",
                            "updated_at"  => "1634027277",
                            "merchant_id" => "10000000000000",
                            "journal_id"  => $transaction['id'],
                            "account_id"  => "GoRNyEuu9Hl0OZ",
                            "amount"      => "1590",
                            "base_amount" => "1590",
                            "type"        => "debit",
                            "currency"    => "INR",
                            "balance"     => "98410",
                            "account_entities" => [
                                "account_type"                      => ["payable"],
                                "fund_account_type"                 => ["merchant_da"],
                                "banking_account_stmt_detail_id"    => ["basd_JLcwWU3SsZ7byJ"]
                            ]
                        ],
                        [
                            "id"          => "HNjsypHPOUlxDR",
                            "created_at"  => "1634027277",
                            "updated_at"  => "1634027277",
                            "merchant_id" => "10000000000000",
                            "journal_id"  => $transaction['id'],
                            "account_id"  => "HN5AGgmKu0ki13",
                            "amount"      => "1590",
                            "base_amount" => "1590",
                            "type"        => "credit",
                            "currency"    => "INR",
                            "balance"     => "984100",
                            "account_entities" => [
                                "account_type"                      => ["payable"],
                                "fund_account_type"                 => ["merchant_da_vendor"],
                                "banking_account_stmt_detail_id"    => ["basd_JLcwWU3SsZ7byJ"]
                            ]
                        ]
                    ]
                ]
            ]);

        $this->fixtures->create('banking_account_statement', [
            'type'                      => 'debit',
            'amount'                    => '1590',
            'channel'                   => 'rbl',
            'utr'                       => '123456',
            'account_number'            => '2224440041626905',
            'transaction_id'            => $externalCreated['transaction_id'],
            'entity_id'                 => $externalCreated['id'],
            'entity_type'               => 'external',
            'bank_transaction_id'       => 'SDHDH',
            'balance'                   => 98410,
            'transaction_date'          => 1584987183
        ]);

        $this->fixtures->create('banking_account_statement_details',[
            'id'                    => 'JLcwWU3SsZ7byJ',
            'merchant_id'           => '10000000000000',
            'account_number'        => '2224440041626905',
            'channel'               => 'rbl',
            'status'                => 'active',
        ]);

        $basCreated = $this->getDbLastEntity('banking_account_statement');

        $this->ba->privateAuth();
        $response = $this->startTest();

        // Asserts other keys existence in response.
        $this->assertNotEmpty($response['id']);
        $this->assertNotEmpty($response['created_at']);
        $this->assertNotEmpty($response['source']['id']);
        $this->assertNotEmpty($response['source']['entity']);
        $this->assertNotEmpty($response['source']['amount']);
        $this->assertEquals($response['source']['id'], $externalCreated->getPublicId());
    }

    public function testFetchStatementForDirectExternalToPayoutFromLedger()
    {
        $this->app['config']->set('applications.ledger.enabled', true);

        $this->createPayout([
            'id' => 'payout00000001'
        ]);

        $transaction = $this->getDbLastEntity('transaction');
        $payoutCreated = $this->getDbLastEntity('payout');

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions/' . $transaction->getPublicId();

        $this->fixtures->merchant->addFeatures([Feature\Constants::DA_LEDGER_REVERSE_SHADOW]);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockLedger->shouldReceive('fetchById')
            ->times(1)
            ->andReturn([
                "code" => 200,
                "body" => [
                    "id"                => $transaction['id'],
                    "created_at"        => "1634027277",
                    "updated_at"        => "1634027277",
                    "amount"            => "1590",
                    "base_amount"       => "130.000000",
                    "currency"          => "INR",
                    "tenant"            => "X",
                    "transactor_id"     => "pout_payout00000001",
                    "transactor_event"  => "da_ext_payout_processed",
                    "transaction_date"  => "1611132045",
                    "ledger_entry" => [
                        [
                            "id"          => "I8MJlgVttAs4OP",
                            "created_at"  => "1634027277",
                            "updated_at"  => "1634027277",
                            "merchant_id" => "10000000000000",
                            "journal_id"  => $transaction['id'],
                            "account_id"  => "GoRNyEuu9Hl0OZ",
                            "amount"      => "1590",
                            "base_amount" => "1590",
                            "type"        => "credit",
                            "currency"    => "INR",
                            "balance"     => "98410",
                            "account_entities" => [
                                "account_type"                      => ["payable"],
                                "fund_account_type"                 => ["merchant_da"],
                                "banking_account_stmt_detail_id"    => ["basd_JLcwWU3SsZ7byJ"]
                            ]
                        ],
                        [
                            "id"          => "I8MJlgVttAs4KQ",
                            "created_at"  => "1634027277",
                            "updated_at"  => "1634027277",
                            "merchant_id" => "10000000000000",
                            "journal_id"  => $transaction['id'],
                            "account_id"  => "GoRNyEuu9Hl0OZ",
                            "amount"      => "1590",
                            "base_amount" => "1590",
                            "type"        => "debit",
                            "currency"    => "INR",
                            "balance"     => "98410",
                            "account_entities" => [
                                "account_type"                      => ["payable"],
                                "fund_account_type"                 => ["merchant_da"],
                                "banking_account_stmt_detail_id"    => ["basd_JLcwWU3SsZ7byJ"]
                            ]
                        ],
                        [
                            "id"          => "HNjsypHPOUlxDR",
                            "created_at"  => "1634027277",
                            "updated_at"  => "1634027277",
                            "merchant_id" => "10000000000000",
                            "journal_id"  => $transaction['id'],
                            "account_id"  => "HN5AGgmKu0ki13",
                            "amount"      => "1590",
                            "base_amount" => "1590",
                            "type"        => "credit",
                            "currency"    => "INR",
                            "balance"     => "984100",
                            "account_entities" => [
                                "account_type"                      => ["payable"],
                                "fund_account_type"                 => ["merchant_da_vendor"],
                                "banking_account_stmt_detail_id"    => ["basd_JLcwWU3SsZ7byJ"]
                            ]
                        ]
                    ]
                ]
            ]);

        $this->fixtures->create('banking_account_statement', [
            'type'                      => 'debit',
            'amount'                    => '1590',
            'channel'                   => 'rbl',
            'utr'                       => '123456',
            'account_number'            => '2224440041626905',
            'transaction_id'            => $payoutCreated['transaction_id'],
            'entity_id'                 => $payoutCreated['id'],
            'entity_type'               => 'payout',
            'bank_transaction_id'       => 'SDHDH',
            'balance'                   => 98410,
            'transaction_date'          => 1584987183
        ]);

        $this->fixtures->create('banking_account_statement_details',[
            'id'                    => 'JLcwWU3SsZ7byJ',
            'merchant_id'           => '10000000000000',
            'account_number'        => '2224440041626905',
            'channel'               => 'rbl',
            'status'                => 'active',
        ]);

        $basCreated = $this->getDbLastEntity('banking_account_statement');

        $this->ba->privateAuth();
        $response = $this->startTest();

        // Asserts other keys existence in response.
        $this->assertNotEmpty($response['id']);
        $this->assertNotEmpty($response['created_at']);
        $this->assertNotEmpty($response['source']['id']);
        $this->assertNotEmpty($response['source']['entity']);
        $this->assertNotEmpty($response['source']['amount']);
        $this->assertEquals($response['balance'], 98410);
        $this->assertEquals($response['source']['id'], $payoutCreated->getPublicId());
    }

    public function testFetchMultipleStatementsWithIncorrectAccountNumberParameter()
    {
        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testFetchMultipleStatementsWithoutAccountNumberParameter()
    {
        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testFetchByContactId()
    {
        $this->createPayout();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?contact_id=cont_' . $this->contact['id'];

        $this->ba->privateAuth();
        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getCreatedAt(), $txn['created_at']);
        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchByPayoutId()
    {
        $this->createPayout();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?payout_id=' . $this->payout->getPublicId();

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getCreatedAt(), $txn['created_at']);
        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchByUtr()
    {
        $this->createPayout(['utr' => 'Dq3XuFEay83Zlo']);

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?utr=' . $this->payout['utr'];

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testFetchByUtrBankTransfer()
    {
        $this->createBankTransferTransaction();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?utr=' . $this->payout['utr'];

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testFetchByType()
    {
        $this->createPayout();
        $this->createBankTransferTransaction();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?type=payout';

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchByInvalidType()
    {
        $this->createPayout();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?type=x';

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testFetchByInvalidPaymentType()
    {
        $this->createPayout();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?type=settlement';

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testFetchByContactName()
    {
        $this->createPayout();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?contact_name=' . $this->contact['name'];

        // Sets ES fetch expected retunr values.
        $this->testData[__FUNCTION__ . 'ExpectedSearchParams']['body']['query']['bool']['filter']['bool']['must'][0]['term']['balance_id']['value'] = $this->bankingBalance->getId();
        $this->testData[__FUNCTION__ . 'ExpectedSearchResponse']['hits']['hits'][0]['_id'] = str_after($this->transaction['id'], 'txn_');
        $this->createEsMockAndSetExpectations(__FUNCTION__);

        $this->ba->privateAuth();
        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchByContactEmail()
    {
        $this->createPayout();

        //Create another Payout with a different Contact Details
        $this->createPayout([], [
            'id' => '1000015contact',
            'email' => 'contact2@razorpay.com',
            'contact' => '8888888888',
            'name' => 'test user'
        ]);

        //Fetch the Transactions of the first Contact
        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?contact_email=contact@razorpay.com';

        // Sets ES fetch expected return values.
        $this->testData[__FUNCTION__ . 'ExpectedSearchResponse']['hits']['hits'][0]['_id'] = str_after($this->transaction['id'], 'txn_');
        $this->createEsMockAndSetExpectations(__FUNCTION__);

        $this->ba->privateAuth();
        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchByContactPhone()
    {
        $this->createPayout();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?contact_phone=' . $this->contact['contact'];

        $this->ba->privateAuth();
        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchByFundAccountId()
    {
        $this->createPayout();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?fund_account_id=' . $this->fundAccount->getPublicId();

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFAVBankAccountTransaction()
    {
        $this->ba->privateAuth();

        $this->createFAVBankAccount();

        $response = $this->startTest();

        $txn = $response['items'][0];

        $this->assertEquals("fund_account.validation", $txn['source']['entity']);
        $this->assertEquals("fund_account", $txn['source']['fund_account']['entity']);
        $this->assertEquals("bank_account", $txn['source']['fund_account']['account_type']);
        $this->assertEquals("111000111", $txn['source']['fund_account']['bank_account']['account_number']);
    }

    public function testFAVFetchStatement()
    {
        $this->ba->privateAuth();

        $this->createFAVBankAccount();

        $transaction = $this->getDbLastEntity('transaction');
        $this->testData[__FUNCTION__]['request']['url'] = '/transactions/' . $transaction->getPublicId();

        $this->ba->privateAuth();
        $response = $this->startTest();

        // Asserts other keys existence in response.
        $this->assertEquals("fund_account.validation", $response['source']['entity']);
        $this->assertEquals("fund_account", $response['source']['fund_account']['entity']);
        $this->assertEquals("bank_account", $response['source']['fund_account']['account_type']);
        $this->assertEquals("111000111", $response['source']['fund_account']['bank_account']['account_number']);
    }

    protected function createBankTransferTransaction()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $merchant = $this->getDbEntityById('merchant', '10000000000000');

        // Disabling tpv flow here because this flow is used for some business banking fund loading tests as well and we
        // don't want them to fail because of tpv check (as tpv entry would not be created for those tests)
        if($merchant->isFeatureEnabled( Feature\Constants::DISABLE_TPV_FLOW) === false)
        {
            $this->fixtures->create('feature', [
                'name'        => Feature\Constants::DISABLE_TPV_FLOW,
                'entity_id'   => 10000000000000,
                'entity_type' => 'merchant',
            ]);
        }

        $this->payVirtualAccount($this->virtualAccount->getPublicId(), ['amount' => 25]);
    }

    public function testActionFilter()
    {
        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->createPayout();

        // Test debit filter after payout
        $merchantUser = $this->fixtures->user->createUserForMerchant('10000000000000');
        $this->ba->proxyAuth('rzp_test_10000000000000', $merchantUser->getId());
        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?action=debit';

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);
        $txn = $response['items'][0];
        $this->assertEquals(count($response['items']), 1);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction['amount'], $txn['debit']);

        // test credit filter after payout
        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?action=credit';
        $response = $this->startTest();
        $this->assertEquals(0, $response['count']);

        // create reverse payout
        $payout = $this->getDbEntity('payout');
        $this->reversePayout($payout);

        // test debit filter after reversal
        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?action=debit';
        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);
        $txn = $response['items'][0];
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction['amount'], $txn['debit']);

        // test credit filter after reversal
        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?action=credit';
        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);
        $txn = $response['items'][0];
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction['amount'], $txn['credit']);
    }

    public function testActionFilterFailedPrivateAuth()
    {
        $this->createPayout();

        $this->ba->privateAuth();
        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?action=debit';

        $this->startTest();
    }
}
