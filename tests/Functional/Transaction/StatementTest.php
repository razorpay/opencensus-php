<?php

namespace RZP\Tests\Functional\Transaction;

use RZP\Models\Feature;
use RZP\Services\RazorXClient;
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

    public function testFetchStatementBankingOnVendorPaymentsAuth()
    {
        $this->createBankTransferTransaction();

        $this->getDbLastEntity('transaction', 'test');

        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $response = $this->startTest();

        $response = $response['items'][0];

        $this->assertNotEmpty($response['id']);
        $this->assertNotEmpty($response['created_at']);
        $this->assertNotEmpty($response['source']['id']);
        $this->assertNotEmpty($response['source']['entity']);
        $this->assertNotEmpty($response['source']['amount']);
    }

    //merchant has rules and hitting a route with access control policies allowed with role not allowed
    public function testFetchStatementWithAttributesPermissionFalse()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function($mid, $feature, $mode) {
                    if ($feature === 'rx_custom_access_control_disabled')
                    {
                        return 'on';
                    }

                    if ($feature === 'rx_custom_access_control_enabled')
                    {
                        return 'off';
                    }

                    return 'control';
                }));

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

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function($mid, $feature, $mode) {
                    if ($feature === 'rx_custom_access_control_disabled')
                    {
                        return 'on';
                    }

                    if ($feature === 'rx_custom_access_control_enabled')
                    {
                        return 'off';
                    }

                    return 'control';
                }));

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

    public function testFetchStatementForFailedFavFromLedger()
    {
        // use ledger mock to create journal for FAV
        $this->app['config']->set('applications.ledger.enabled', false);

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

        // Create an FAV first
        $fundAccountResponse = $this->createFundAccountBankAccount();

        $request = [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                'fund_account' => [
                    'id' => $fundAccountResponse['id'],
                ],
                'currency'     => 'INR',
                'notes'        => [],
                'receipt'      => '12345667',
            ],
        ];

        $this->ba->privateAuth();

        $favResponse = $this->makeRequestAndGetContent($request);

        // Fail the FAV
        $this->triggerFlowToUpdateFavWithNewState($favResponse['id']);

        $reversal = $this->getDbLastEntity('reversal');

        // now do custom mocking for journal fetchById route
        $this->app['config']->set('applications.ledger.enabled', true);

        $mockLedger = \Mockery::mock('RZP\Services\Ledger')->makePartial();
        $this->app->instance('ledger', $mockLedger);

        $mockJournalId = 'IWx1NL90G02vxr';

        $mockLedgerResponse = [
            'id'               => $mockJournalId,
            'created_at'       => '1634027277',
            'updated_at'       => '1634027277',
            'amount'           => '354.000000',
            'base_amount'      => '354.000000',
            'currency'         => 'INR',
            'tenant'           => 'X',
            'transactor_id'    => $reversal->getPublicId(),
            'transactor_event' => 'fav_failed',
            'transaction_date' => '1611132045',
            'ledger_entry'     => [
                [
                    'id'               => 'I8MJlgVttAs4KQ',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => $mockJournalId,
                    'account_id'       => 'GoRNyEuu9Hl0OZ',
                    'amount'           => '354.000000',
                    'base_amount'      => '354.000000',
                    'type'             => 'debit',
                    'currency'         => 'INR',
                    'balance'          => '98410.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['cash',],
                        'banking_account_id' => ['bacc_JLcwWU3SsZ7byJ',],
                        'fund_account_type'  => ['merchant_va',],
                        'transactor'         => ['X',],
                    ],
                ],
                [
                    'id'               => 'I8MJlgVttAs4KR',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => $mockJournalId,
                    'account_id'       => 'IwwUK4yc0R4avZ',
                    'amount'           => '354.000000',
                    'base_amount'      => '354.000000',
                    'type'             => 'credit',
                    'currency'         => 'INR',
                    'balance'          => '98410.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['payable',],
                        'banking_account_id' => ['bacc_JLcwWU3SsZ7byJ',],
                        'fund_account_type'  => ['merchant_va',],
                        'transactor'         => ['X',],
                    ],
                ],
                [
                    'id'               => 'K0Rf8eRaOhG5nN',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => $mockJournalId,
                    'account_id'       => 'IwwUK6KYrdHY2m',
                    'amount'           => '54.000000',
                    'base_amount'      => '54.000000',
                    'type'             => 'debit',
                    'currency'         => 'INR',
                    'balance'          => '88236.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['payable',],
                        'banking_account_id' => ['bacc_JLcwWU3SsZ7byJ',],
                        'fund_account_type'  => ['va_gst',],
                        'transactor'         => ['X',],
                    ],
                ],
            ],
        ];

        $mockLedger->shouldReceive('fetchById')
            ->times(1)
            ->andReturn(
                [
                    'code' => 200,
                    'body' => $mockLedgerResponse,
                ]
            );

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions/txn_IWx1NL90G02vxr';

        $this->ba->privateAuth();
        $response = $this->startTest();

        // Asserts other keys existence in response.
        $this->assertNotEmpty($response['id']);
        $this->assertNotEmpty($response['created_at']);
        $this->assertNotEmpty($response['source']['id']);
        $this->assertNotEmpty($response['source']['entity']);
        $this->assertEquals($response['source']['id'], $reversal->getPublicId());
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
