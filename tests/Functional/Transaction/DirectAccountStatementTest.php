<?php

namespace RZP\Tests\Functional\Transaction;

use Carbon\Carbon;

use RZP\Models\Payout;
use RZP\Constants\Mode;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\FundAccount\FundAccountTrait;

class DirectAccountStatementTest extends TestCase
{
    use PaymentTrait;
    use FundAccountTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/DirectAccountStatementTestData.php';

        parent::setUp();

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->setUpMerchantForBusinessBanking(false, 10000000, 'direct', 'rbl');

        $this->app['rzp.mode'] = Mode::TEST;
    }

    public function createDummyPayout($extraPayoutParams = [], $contact = [])
    {
        $this->createPayout($extraPayoutParams, $contact);

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->create('fund_transfer_attempt',
                                [
                                    'source_id' => $payout->getId(),
                                    'source_type' => 'payout',
                                    'merchant_id' => $payout->getMerchantId(),
                                    'utr' => $payout->getUtr(),
                                    'purpose' => $payout->getPurpose(),
                                    'bank_account_id' => $payout->fundaccount->account->getId(),
                                    'channel' => 'rbl',
                                    'version' => 'v3',
                                    'bank_status_code' => 'SUCCESS',
                                    'mode' => $payout->getMode(),
                                    'status' => $payout->getStatus(),
                                    'cms_ref_no' => 'M2134213',
                                    'initiate_at' => $payout->getCreatedAt(),
                                ]);

        $this->fixtures->create('banking_account_statement',
                                [
                                    'entity_id' => $payout->getId(),
                                    'entity_type' => 'payout',
                                    'utr' => $payout->getUtr(),
                                    'amount' => $payout->getAmount(),
                                    'balance' => $this->bankingBalance->getBalance() - $payout->getAmount(),
                                    'channel' => 'rbl',
                                    'account_number' => $this->bankingBalance->getAccountNumber(),
                                    'bank_transaction_id' => 'M2134213',
                                    'type' => 'debit',
                                    'posted_date' => 1650628947,
                                    'description' => $payout->getUtr() . '-LOAN492835',
                                    'category' => 'customer_initiated',
                                    'bank_instrument_id' => "",
                                    'transaction_date' => 1650565800,
                                    'transaction_id' => $payout->getTransactionId(),
                                ]);
    }

    public function createDummyReversal($extraPayoutParams = [], $contact = ['name' => 'tester'])
    {
        $this->createPayout($extraPayoutParams, $contact);

        $payout = $this->getDbLastEntity('payout');

        $this->fixtures->create('fund_transfer_attempt',
                                [
                                    'source_id' => $payout->getId(),
                                    'source_type' => 'payout',
                                    'merchant_id' => $payout->getMerchantId(),
                                    'utr' => $payout->getUtr(),
                                    'purpose' => $payout->getPurpose(),
                                    'bank_account_id' => $payout->fundaccount->account->getId(),
                                    'channel' => 'rbl',
                                    'version' => 'v3',
                                    'bank_status_code' => 'SUCCESS',
                                    'mode' => $payout->getMode(),
                                    'status' => $payout->getStatus(),
                                    'cms_ref_no' => 'M2134214',
                                    'initiate_at' => $payout->getCreatedAt(),
                                ]);

        $this->fixtures->create('banking_account_statement',
                                [
                                    'entity_id' => $payout->getId(),
                                    'entity_type' => 'payout',
                                    'utr' => $payout->getUtr(),
                                    'amount' => $payout->getAmount(),
                                    'balance' => $this->bankingBalance->getBalance() - $payout->getAmount(),
                                    'channel' => 'rbl',
                                    'account_number' => $this->bankingBalance->getAccountNumber(),
                                    'bank_transaction_id' => 'M2134214',
                                    'type' => 'debit',
                                    'posted_date' => 1650628947,
                                    'description' => $payout->getUtr() . '-LOAN492845',
                                    'category' => 'customer_initiated',
                                    'bank_serial_number' => 2,
                                    'bank_instrument_id' => "",
                                    'transaction_date' => 1650565800,
                                    'transaction_id' => $payout->getTransactionId(),
                                ]);

        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'     => 'reversed',
            'failure_reason' => '',
        ]);

        $reversal = $this->getDbLastEntity('reversal');

        $this->fixtures->create('banking_account_statement',
                                [
                                    'entity_id' => $reversal->getId(),
                                    'entity_type' => 'reversal',
                                    'utr' => $reversal->getUtr(),
                                    'amount' => $reversal->getAmount(),
                                    'balance' => $this->bankingBalance->getBalance() + $reversal->getAmount(),
                                    'channel' => 'rbl',
                                    'account_number' => $this->bankingBalance->getAccountNumber(),
                                    'bank_transaction_id' => 'M2134215',
                                    'type' => 'credit',
                                    'posted_date' => 1650628967,
                                    'description' => $reversal->getUtr() . '-LOAN492836',
                                    'category' => 'customer_initiated',
                                    'bank_serial_number' => 3,
                                    'bank_instrument_id' => "",
                                    'transaction_date' => 1650565810,
                                    'transaction_id' => $reversal->getTransactionId(),
                                ]);
    }

    public function createDummyExternal()
    {
        $bas = $this->fixtures->create('banking_account_statement',
                                [
                                    'entity_id' => 'testExternal00',
                                    'entity_type' => 'external',
                                    'utr' => '211708954836',
                                    'amount' => 1000,
                                    'balance' => $this->bankingBalance->getBalance() + 1000,
                                    'channel' => 'rbl',
                                    'account_number' => $this->bankingBalance->getAccountNumber(),
                                    'bank_transaction_id' => 'M2134215',
                                    'type' => 'credit',
                                    'posted_date' => 1650628967,
                                    'description' => '211708954836-LOAN492836',
                                    'category' => 'customer_initiated',
                                    'bank_serial_number' => 7,
                                    'bank_instrument_id' => "",
                                    'transaction_date' => 1650565810,
                                    'transaction_id' => 'JOPkusQyH3wn3u',
                                ]);

        $this->fixtures->create('external',
                                [
                                    'id' => 'testExternal00' ,
                                    'merchant_id' => '10000000000000',
                                    'transaction_id' => 'JOPkusQyH3wn3u',
                                    'banking_account_statement_id' => $bas->getId(),
                                    'channel' => 'rbl',
                                    'bank_reference_number' => $bas->getBankTransactionId(),
                                    'utr' => '211708954836',
                                    'type' => $bas->getType(),
                                    'amount' => $bas->getAmount(),
                                    'currency' => 'INR',
                                    'balance_id' => $this->bankingBalance->getId(),
                                ]);
    }

    public function createDummyBasRecordsWithoutLinkingEntity()
    {
        $utr = str_shuffle('211708954836');

        $bas = $this->fixtures->create('banking_account_statement',
                                       [
                                           'utr' => $utr,
                                           'amount' => 1000,
                                           'balance' => $this->bankingBalance->getBalance() + 1000,
                                           'channel' => 'rbl',
                                           'account_number' => $this->bankingBalance->getAccountNumber(),
                                           'bank_transaction_id' => 'M2134218',
                                           'type' => 'credit',
                                           'posted_date' => 1650628967,
                                           'description' => $utr . '-LOAN' . str_shuffle('492836'),
                                           'category' => 'customer_initiated',
                                           'bank_serial_number' => 7,
                                           'bank_instrument_id' => "",
                                           'transaction_date' => 1650565810,
                                       ]);
    }

    public function testFetchMultipleStatementsForDirectAccount()
    {
        $this->createDummyPayout();

        $this->createDummyReversal();

        $this->createDummyExternal();

        // This BAS won't show up in the results.
        $this->createDummyBasRecordsWithoutLinkingEntity();

        // Creates one normal payment transaction on primary balance.
        $this->doAuthAndCapturePayment(null, 50000);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchMultipleStatementsForDirectAccountWithPrivateAuth()
    {
        $this->createDummyPayout();

        $this->createDummyReversal();

        $this->createDummyExternal();

        // This BAS won't show up in the results.
        $this->createDummyBasRecordsWithoutLinkingEntity();

        // Creates one normal payment transaction on primary balance.
        $this->doAuthAndCapturePayment(null, 50000);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testStatementsFetchLogicForBankingForDirectAccount()
    {
        $this->fixtures->edit('merchant', '10000000000000', ['business_banking' => true]);

        $user = (new User())->createBankingUserForMerchant('10000000000000', [
            'contact_mobile' => '8888888888'
        ], 'admin');

        $this->fixtures->create('merchant_attribute',
                                [
                                    'merchant_id' => '10000000000000',
                                    'product'     => 'banking',
                                    'group'       => 'x_transaction_view',
                                    'type'        => 'admin',
                                    'value'       => 'true'
                                ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'      => '10000000000000',
            'business_name'    => 'Test Name Private Limited ltd ltd. Liability partnership',
            'business_website' => 'https://shopify.secondleveldomain.edu.in'
        ]);

        $this->createPayout();

        $payout = $this->getDbLastEntity('payout');

        for ($i = 0; $i < 5; $i++)
        {
            $this->fixtures->create('banking_account_statement',
                                    [
                                        'entity_id'           => $payout->getId(),
                                        'entity_type'         => 'payout',
                                        'utr'                 => $payout->getUtr(),
                                        'amount'              => $payout->getAmount(),
                                        'balance'             => $this->bankingBalance->getBalance() - $payout->getAmount(),
                                        'channel'             => 'rbl',
                                        'account_number'      => $this->bankingBalance->getAccountNumber(),
                                        'bank_transaction_id' => 'M2134213',
                                        'type'                => 'debit',
                                        // posted date is of 10 nov 1 am
                                        'posted_date'         => strtotime("+" . $i . " day", 1668022260),
                                        'description'         => $payout->getUtr() . '-LOAN492835',
                                        'category'            => 'customer_initiated',
                                        'bank_instrument_id'  => "",
                                        //transaction date - 9 nov 12 am
                                        'transaction_date'    => strtotime("+" . $i . " day", 1667932200),
                                        'transaction_id'      => $payout->getTransactionId(),
                                    ]);

            $this->fixtures->create('banking_account_statement',
                                    [
                                        'entity_id'           => $payout->getId(),
                                        'entity_type'         => 'payout',
                                        'utr'                 => $payout->getUtr(),
                                        'amount'              => $payout->getAmount(),
                                        'balance'             => $this->bankingBalance->getBalance() - $payout->getAmount(),
                                        'channel'             => 'rbl',
                                        'account_number'      => $this->bankingBalance->getAccountNumber(),
                                        'bank_transaction_id' => 'M2134213',
                                        'type'                => 'debit',
                                        // posted date is of 9 nov 10 pm
                                        'posted_date'         => strtotime("+" . $i . " day", 1668011400),
                                        'description'         => $payout->getUtr() . '-LOAN492835',
                                        'category'            => 'customer_initiated',
                                        'bank_instrument_id'  => "",
                                        // transaction date - 9 nov 12 am
                                        'transaction_date'    => strtotime("+" . $i . " day", 1667932200),
                                        'transaction_id'      => $payout->getTransactionId(),
                                    ]);

        }

        $request        = &$this->testData[__FUNCTION__]['request'];
        $request['url'] = '/transactions_banking?from=1667932200&to=1668018599';
        $this->ba->privateAuth();
        $response = $this->startTest();
        $this->assertEquals(2, $response['count']);

        $request        = &$this->testData[__FUNCTION__]['request'];
        $request['url'] = '/transactions_banking?from=1667932200&to=1668104999';
        $response       = $this->startTest();
        $this->assertEquals(4, $response['count']);

        $request        = &$this->testData[__FUNCTION__]['request'];
        $request['url'] = '/transactions_banking?from=1667932200&to=1668191399';
        $response       = $this->startTest();
        $this->assertEquals(6, $response['count']);

        $request        = &$this->testData[__FUNCTION__]['request'];
        $request['url'] = '/transactions_banking?from=1667932200&to=1668277799';
        $response       = $this->startTest();
        $this->assertEquals(8, $response['count']);

        $request        = &$this->testData[__FUNCTION__]['request'];
        $request['url'] = '/transactions_banking?from=1667932200';
        $response       = $this->startTest();
        $this->assertEquals(10, $response['count']);

    }

    public function testFetchMultipleStatementsForBankingForDirectAccount()
    {
        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->fixtures->edit('merchant', '10000000000000', ['business_banking' => true]);

        $this->createDummyPayout();

        $this->createDummyReversal();

        // This BAS won't show up in the results.
        $this->createDummyBasRecordsWithoutLinkingEntity();

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
        $this->createDummyPayout();

        $this->createDummyReversal();

        $this->testData[__FUNCTION__]['request']['server']['HTTP_X-Request-Origin'] =  'https://x.razorpay.com';

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions';

        $user =  (new User())->createBankingUserForMerchant('10000000000000', [
            'contact_mobile' => '8888888888',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();
    }

    public function testFetchWithDateFilter()
    {
        // set current time to control created_at columns
        $timestamp = Carbon::now();
        Carbon::setTestNow($timestamp);

        // take 2 days older timestamp, 1 day older timestamp and get its int value
        $oldTimestamp = Carbon::now()->subDays(2)->timestamp;
        $newTimestamp = Carbon::now()->subDays(1)->timestamp;

        // create a dummy payout and a BAS and set the BAS posted_date to 2 days earlier.
        $this->createDummyPayout();
        $bas = $this->getDbLastEntity('banking_account_statement');
        $this->fixtures->edit('banking_account_statement', $bas->getId(), ['posted_date' => $oldTimestamp]);
        $this->fixtures->edit('banking_account_statement', $bas->getId(), ['transaction_date' => $oldTimestamp]);

        // create a dummy reversal and a BAS and set the BAS posted_date to 2 days earlier.
        $this->createDummyReversal();
        $bas = $this->getDbLastEntity('banking_account_statement');
        $this->fixtures->edit('banking_account_statement', $bas->getId(), ['posted_date' => $oldTimestamp]);
        $this->fixtures->edit('banking_account_statement', $bas->getId(), ['transaction_date' => $oldTimestamp]);

        // create a dummy external and a BAS and set the BAS posted_date to 2 days earlier.
        $this->createDummyExternal();
        $bas = $this->getDbLastEntity('banking_account_statement');
        $this->fixtures->edit('banking_account_statement', $bas->getId(), ['posted_date' => $oldTimestamp]);
        $this->fixtures->edit('banking_account_statement', $bas->getId(), ['transaction_date' => $oldTimestamp]);

        // create a dummy BAS and set the BAS posted_date to 2 days earlier.
        $this->createDummyBasRecordsWithoutLinkingEntity();
        $bas = $this->getDbLastEntity('banking_account_statement');
        $this->fixtures->edit('banking_account_statement', $bas->getId(), ['posted_date' => $oldTimestamp]);
        $this->fixtures->edit('banking_account_statement', $bas->getId(), ['transaction_date' => $oldTimestamp]);

        // we expect date filter to happen on posted_date
        // if it doesn't happen, then we shall be missing all statements in the response
        $this->testData[__FUNCTION__]['request']['content']['from'] = $oldTimestamp;
        $this->testData[__FUNCTION__]['request']['content']['to']   = $newTimestamp;

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchByContactId()
    {
        $this->createDummyPayout();

        $this->createDummyReversal();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?contact_id=cont_' . $this->contact['id'];

        $this->ba->proxyAuth();
        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchByContactIdWithPrivateAuth()
    {
        $this->createDummyPayout();

        $this->createDummyReversal();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?contact_id=cont_' . $this->contact['id'];

        $this->ba->privateAuth();
        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchByPayoutId()
    {
        $this->createDummyReversal();

        $this->createDummyPayout();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?payout_id=' . $this->payout->getPublicId();

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchByUtr()
    {
        $this->createDummyPayout(['utr' => 'Dq3XuFEay83Zlo']);

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?utr=' . $this->payout['utr'];

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchByType()
    {
        $this->createDummyPayout();

        $this->createDummyReversal();

        $this->createDummyExternal();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?type=payout';

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(2, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchByInvalidType()
    {
        $this->createDummyPayout();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?type=x';

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchByContactName()
    {
        $this->createDummyPayout();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?contact_name=' . $this->contact['name'];

        // Sets ES fetch expected return values.
        $this->testData[__FUNCTION__ . 'ExpectedSearchParams']['body']['query']['bool']['filter']['bool']['must'][0]['term']['balance_id']['value'] = $this->bankingBalance->getId();
        $this->testData[__FUNCTION__ . 'ExpectedSearchResponse']['hits']['hits'][0]['_id'] = str_after($this->transaction['id'], 'txn_');
        $this->createEsMockAndSetExpectations(__FUNCTION__);

        $this->ba->proxyAuth();
        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchByContactNameWithPrivateAuth()
    {
        $this->createDummyPayout();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?contact_name=' . $this->contact['name'];

        // Sets ES fetch expected return values.
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
        $this->createDummyReversal();

        //Create another Payout with different Contact Details
        $this->createDummyPayout([], [
            'id' => '1000015contact',
            'email' => 'contact2@razorpay.com',
            'contact' => '8888888888',
            'name' => 'test user'
        ]);

        //Fetch the Transactions of the first Contact
        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?contact_email=contact@razorpay.com';

        // Sets ES fetch expected return values.
        $this->testData[__FUNCTION__ . 'ExpectedSearchParams']['body']['query']['bool']['filter']['bool']['must'][1]['term']['balance_id']['value'] = $this->bankingBalance->getId();
        $this->testData[__FUNCTION__ . 'ExpectedSearchResponse']['hits']['hits'][0]['_id'] = str_after($this->transaction['id'], 'txn_');
        $this->createEsMockAndSetExpectations(__FUNCTION__);

        $this->ba->proxyAuth();
        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchByContactEmailPartial()
    {
        $this->createDummyReversal();

        //Create another Payout with different Contact Details
        $this->createDummyPayout([], [
            'id' => '1000015contact',
            'email' => 'contact2@razorpay.com',
            'contact' => '8888888888',
            'name' => 'test user'
        ]);

        //Fetch the Transactions of the first Contact
        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?contact_email_ps=contact@';

        // Sets ES fetch expected return values.
        $this->testData[__FUNCTION__ . 'ExpectedSearchParams']['body']['query']['bool']['filter']['bool']['must'][1]['term']['balance_id']['value'] = $this->bankingBalance->getId();
        $this->testData[__FUNCTION__ . 'ExpectedSearchResponse']['hits']['hits'][0]['_id'] = str_after($this->transaction['id'], 'txn_');
        $this->createEsMockAndSetExpectations(__FUNCTION__);

        $this->ba->proxyAuth();
        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchByContactPhone()
    {
        $this->createDummyPayout();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?contact_phone=' . $this->contact['contact'];

        $this->ba->proxyAuth();
        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchByContactPhonePartial()
    {
        $this->createDummyReversal();

        //Create another Payout with different Contact Details
        $this->createDummyPayout([], [
            'id' => '1000015contact',
            'email' => 'contact2@razorpay.com',
            'contact' => '9888888888',
            'name' => 'test user'
        ]);

        //Fetch the Transactions of the first Contact
        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?contact_phone_ps=988888';

        // Sets ES fetch expected return values.
        $this->testData[__FUNCTION__ . 'ExpectedSearchParams']['body']['query']['bool']['filter']['bool']['must'][1]['term']['balance_id']['value'] = $this->bankingBalance->getId();
        $this->testData[__FUNCTION__ . 'ExpectedSearchResponse']['hits']['hits'][0]['_id'] = str_after($this->transaction['id'], 'txn_');
        $this->createEsMockAndSetExpectations(__FUNCTION__);

        $this->ba->proxyAuth();
        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchByFundAccountNumber()
    {
        $esTxnResult = [];

        $this->createDummyPayout();

        $esTxnResult[] = ['_id' => str_after($this->transaction['id'], 'txn_')];

        $this->createDummyPayout([], [
            'id' => '1000011contact',
            'email' => 'contact@razorpay.com',
            'contact' => '8888888888',
            'name' => 'test user'
        ]);

        $esTxnResult[] = ['_id' => str_after($this->transaction['id'], 'txn_')];

        $this->testData[__FUNCTION__]['request']['url'] =
            '/transactions?fund_account_number=' . $this->fundAccount->account->getAccountNumber();

        // Sets ES fetch expected return values.
        $this->testData[__FUNCTION__ . 'ExpectedSearchParams']['body']['query']['bool']['filter']['bool']['must'][0]['term']['balance_id']['value'] = $this->bankingBalance->getId();
        $this->testData[__FUNCTION__ . 'ExpectedSearchResponse']['hits']['hits'] = $esTxnResult;
        $this->createEsMockAndSetExpectations(__FUNCTION__);

        $this->ba->proxyAuth();
        $response = $this->startTest();

        $this->assertEquals(2, $response['count']);
    }

    public function testFetchByNotes()
    {
        $this->createDummyPayout(['notes' => ['text' => 'testPayout']]);

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?notes=testPayout';
        // Sets ES fetch expected return values.
        $this->testData[__FUNCTION__ . 'ExpectedSearchParams']['body']['query']['bool']['filter']['bool']['must'][0]['term']['balance_id']['value'] = $this->bankingBalance->getId();
        $this->testData[__FUNCTION__ . 'ExpectedSearchResponse']['hits']['hits'][0]['_id'] = str_after($this->transaction['id'], 'txn_');
        $this->createEsMockAndSetExpectations(__FUNCTION__);

        $this->ba->proxyAuth();
        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);
    }

    public function testFetchByFundAccountId()
    {
        $this->createDummyPayout();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?fund_account_id=' . $this->fundAccount->getPublicId();

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testActionFilter()
    {
        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->createDummyPayout();

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

        $this->createDummyReversal();

        // test debit filter after reversal
        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?action=debit';
        $response = $this->startTest();

        $this->assertEquals(2, $response['count']);
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
        $this->createDummyPayout();

        $this->ba->privateAuth();
        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?action=debit';

        $this->startTest();
    }

    public function testFetchByPayoutMode()
    {
        $contactInput = [
            'id' => '1000011contact',
            'email' => 'contact@razorpay.com',
            'contact' => '8888888888',
            'name' => 'test user'
        ];

        $this->createDummyPayout(['mode' => 'NEFT'], $contactInput);

        $this->createDummyReversal(['mode' => 'IMPS']);

        $this->createDummyPayout(['mode' => 'IMPS']);

        $this->createDummyExternal();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?mode=IMPS';

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchByContactType()
    {
        $contactInput = [
            'id' => '1000011contact',
            'email' => 'contact@razorpay.com',
            'contact' => '8888888888',
            'name' => 'test user',
            'type' => 'customer',
        ];

        $this->createDummyPayout(['mode' => 'NEFT'], $contactInput);

        $contactInput['id']   = '1000010contact';

        $this->createDummyReversal(['mode' => 'IMPS'], $contactInput);

        $contactInput['id']   = '1000012contact';
        $contactInput['type'] = 'employee';

        $this->createDummyPayout(['mode' => 'IMPS'], $contactInput);

        $this->createDummyExternal();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?contact_type=employee';

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchByPayoutPurpose()
    {
        $contactInput = [
            'id' => '1000011contact',
            'email' => 'contact@razorpay.com',
            'contact' => '8888888888',
            'name' => 'test user',
            'type' => 'customer',
        ];

        $this->createDummyPayout([], $contactInput);

        $contactInput['id']   = '1000012contact';
        $contactInput['type'] = 'employee';

        $this->createDummyPayout(['purpose' => 'salary'], $contactInput);

        $this->createDummyPayout(['purpose' => 'payout']);

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?payout_purpose=payout';

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchMultipleByTransactionId()
    {
        $this->createDummyPayout();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?id=' . $this->transaction->getPublicId();

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchMultipleByBasId()
    {
        $this->createDummyPayout();

        $bas = $this->getDbLastEntity('banking_account_statement');

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?id=' . $bas->getPublicId();

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchMultipleByBasIdWithPrivateAuth()
    {
        $this->createDummyPayout();

        $bas = $this->getDbLastEntity('banking_account_statement');

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?id=' . $bas->getPublicId();

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchMultipleWhenBasHasNoTxnId()
    {
        $this->createDummyPayout();

        $bas = $this->getDbLastEntity('banking_account_statement');

        $this->fixtures->edit('banking_account_statement', $bas->getId(), ['transaction_id' => null]);

        $bas = $this->getDbLastEntity('banking_account_statement');

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?id=' . $bas->getPublicId();

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $this->assertEquals($bas->getPublicId(), $response['items'][0]['id']);

        $stmt = $response['items'][0];

        $this->assertNotEquals($this->transaction->getPublicId(), $stmt['id']);
        $this->assertEquals($bas['amount'], $stmt['amount']);
        $this->assertEquals($bas->getEntityId(), str_after($stmt['source']['id'], 'pout_'));
        $this->assertEquals($bas->getEntityType(), 'payout');
    }

    public function testFetchMultipleWhenBasHasNoTxnIdWithPrivateAuth()
    {
        $this->createDummyPayout();

        $bas = $this->getDbLastEntity('banking_account_statement');

        $this->fixtures->edit('banking_account_statement', $bas->getId(), ['transaction_id' => null]);

        $bas = $this->getDbLastEntity('banking_account_statement');

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?id=' . $bas->getPublicId();

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $this->assertEquals($bas->getPublicId(), $response['items'][0]['id']);

        $stmt = $response['items'][0];

        $this->assertNotEquals($this->transaction->getPublicId(), $stmt['id']);
        $this->assertEquals($bas['amount'], $stmt['amount']);
        $this->assertEquals($bas->getEntityId(), str_after($stmt['source']['id'], 'pout_'));
        $this->assertEquals($bas->getEntityType(), 'payout');
    }

    // --------------------- Tests for fetch by single transaction ID API follow below ----------------------------

    // This scenario shouldn't happen in prod. This test assumes that the BAS entity has a transaction entity
    // linked, but the merchant is still trying to query the txn fetch by ID API using BAS public ID.
    // Ideally, the merchant should have received a transaction public ID in the first place.
    // This shows that in this contradictory situation, the response will return a txn based public ID.
    // We HAVE made sure that a BAS linked to a transaction ID should show txn based public ID everywhere.
    public function testFetchTransactionByPublicBasIdForPayout()
    {
        $this->createDummyPayout();

        $bas = $this->getDbLastEntity('banking_account_statement');

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions/' . $bas->getPublicId();

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals('txn_' . $bas->getTransactionId(), $response['id']);
        $this->assertEquals($bas->getAmount(), $response['amount']);
        $this->assertEquals($bas->getEntityId(), str_after($response['source']['id'], 'pout_'));
        $this->assertEquals($bas->getEntityType(), $response['source']['entity']);
    }

    public function testFetchTransactionByPublicBasIdForPayoutWhenBasHasNoTxnLinked()
    {
        $this->createDummyPayout();

        $bas = $this->getDbLastEntity('banking_account_statement');

        $this->fixtures->edit('banking_account_statement', $bas->getId(), ['transaction_id' => null]);

        $bas = $this->getDbLastEntity('banking_account_statement');

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions/' . $bas->getPublicId();

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals($bas->getPublicId(), $response['id']);

        $this->assertEquals($bas->getPublicId(), $response['id']);
        $this->assertEquals($bas->getAmount(), $response['amount']);
        $this->assertEquals($bas->getEntityId(), str_after($response['source']['id'], 'pout_'));
        $this->assertEquals($bas->getEntityType(), $response['source']['entity']);
    }

    public function testFetchTransactionByPublicBasIdForReversalWhenBasHasNoTxnLinked()
    {
        $this->createDummyReversal();

        $bas = $this->getDbLastEntity('banking_account_statement');

        $this->fixtures->edit('banking_account_statement', $bas->getId(), ['transaction_id' => null]);

        $bas = $this->getDbLastEntity('banking_account_statement');

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions/' . $bas->getPublicId();

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals($bas->getPublicId(), $response['id']);

        $this->assertEquals($bas->getPublicId(), $response['id']);
        $this->assertEquals($bas->getAmount(), $response['amount']);
        $this->assertEquals($bas->getEntityId(), str_after($response['source']['id'], 'rvrsl_'));
        $this->assertEquals($bas->getEntityType(), $response['source']['entity']);
    }

    public function testFetchTransactionByPublicBasIdForExternalEntityWhenBasHasNoTxnLinked()
    {
        $this->createDummyExternal();

        $bas = $this->getDbLastEntity('banking_account_statement');

        $this->fixtures->edit('banking_account_statement', $bas->getId(), ['transaction_id' => null]);

        $bas = $this->getDbLastEntity('banking_account_statement');

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions/' . $bas->getPublicId();

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals($bas->getPublicId(), $response['id']);

        $this->assertEquals($bas->getPublicId(), $response['id']);
        $this->assertEquals($bas->getAmount(), $response['amount']);
        $this->assertEquals($bas->getEntityId(), str_after($response['source']['id'], 'ext_'));
        $this->assertEquals($bas->getEntityType(), $response['source']['entity']);
    }
}
