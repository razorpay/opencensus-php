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

class DirectAccountStatementRearchTest extends TestCase
{
    use PaymentTrait;
    use FundAccountTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/DirectAccountStatementRearchTestData.php';

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

    public function testFetchMultipleStatementsForDirectAccountRearch()
    {
        $this -> createDummyPayout();

        $this -> createDummyReversal();

        $this -> createDummyExternal();

        // This BAS won't show up in the results.
        $this -> createDummyBasRecordsWithoutLinkingEntity();

        // Creates one normal payment transaction on primary balance.
        $this -> doAuthAndCapturePayment(null, 50000);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchMultipleStatementsForDirectAccountWithPrivateAuthRearch()
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

    public function testFetchMultipleStatementsWithMerchantRulesRearch()
    {
        $this->createDummyPayout();

        $this->createDummyReversal();

        $user =  (new User())->createBankingUserForMerchant('10000000000000', [
            'contact_mobile' => '8888888888',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $this->startTest();
    }

    public function testFetchWithDateFilterRearch()
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

    public function testFetchByContactIdRearch()
    {
        $this->createDummyPayout();

        $this->createDummyReversal();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking?contact_id=cont_' . $this->contact['id'];

        $this->ba->proxyAuth();
        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchByContactIdWithPrivateAuthRearch()
    {
        $this->createDummyPayout();

        $this->createDummyReversal();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking?contact_id=cont_' . $this->contact['id'];

        $this->ba->privateAuth();
        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchByPayoutIdRearch()
    {
        $this->createDummyReversal();

        $this->createDummyPayout();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking?payout_id=' . $this->payout->getPublicId();

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchByUtrRearch()
    {
        $this->createDummyPayout(['utr' => 'Dq3XuFEay83Zlo']);

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking?utr=' . $this->payout['utr'];

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchByTypeRearch()
    {
        $this->createDummyPayout();

        $this->createDummyReversal();

        $this->createDummyExternal();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking?type=payout';

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(2, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchByInvalidTypeRearch()
    {
        $this->createDummyPayout();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking?type=x';

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchByContactNameRearch()
    {
        $this->createDummyPayout();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking?contact_name=' . $this->contact['name'];

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

    public function testFetchByContactNameWithPrivateAuthRearch()
    {
        $this->createDummyPayout();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking?contact_name=' . $this->contact['name'];

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

    public function testFetchByContactEmailRearch()
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
        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking?contact_email=contact@razorpay.com';

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

    public function testFetchByContactEmailPartialRearch()
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
        $this->testData[__FUNCTION__ . 'ExpectedSearchParams']['body']['query']['bool']['filter']['bool']['must'][1]['term']['balance_id']['value'] = $this->bankingBalance->getId();
        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking?contact_email_ps=contact@razorpay.com';

        // Sets ES fetch expected return values.
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

    public function testFetchByContactPhoneRearch()
    {
        $this->createDummyPayout();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking?contact_phone=' . $this->contact['contact'];

        $this->ba->proxyAuth();
        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchByContactPhonePartialRearch()
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
        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking?contact_phone_ps=988888';

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

    public function testFetchByFundAccountNumberRearch()
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
            '/transactions_banking?fund_account_number=' . $this->fundAccount->account->getAccountNumber();

        // Sets ES fetch expected return values.
        $this->testData[__FUNCTION__ . 'ExpectedSearchParams']['body']['query']['bool']['filter']['bool']['must'][0]['term']['balance_id']['value'] = $this->bankingBalance->getId();
        $this->testData[__FUNCTION__ . 'ExpectedSearchResponse']['hits']['hits'] = $esTxnResult;
        $this->createEsMockAndSetExpectations(__FUNCTION__);

        $this->ba->proxyAuth();
        $response = $this->startTest();

        $this->assertEquals(2, $response['count']);
    }

    public function testFetchByNotesRearch()
    {
        $this->createDummyPayout(['notes' => ['text' => 'testPayout']]);

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking?notes=testPayout';
        // Sets ES fetch expected return values.
        $this->testData[__FUNCTION__ . 'ExpectedSearchParams']['body']['query']['bool']['filter']['bool']['must'][0]['term']['balance_id']['value'] = $this->bankingBalance->getId();
        $this->testData[__FUNCTION__ . 'ExpectedSearchResponse']['hits']['hits'][0]['_id'] = str_after($this->transaction['id'], 'txn_');
        $this->createEsMockAndSetExpectations(__FUNCTION__);

        $this->ba->proxyAuth();
        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);
    }

    public function testFetchByFundAccountIdRearch()
    {
        $this->createDummyPayout();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking?fund_account_id=' . $this->fundAccount->getPublicId();

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testActionFilterFailedPrivateAuthRearch()
    {
        $this->createDummyPayout();

        $this->ba->privateAuth();
        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking?action=debit';

        $this->startTest();
    }

    public function testFetchByPayoutModeRearch()
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

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking?mode=IMPS';

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchByContactTypeRearch()
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

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking?contact_type=employee';

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchByPayoutPurposeRearch()
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

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking?payout_purpose=payout';

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchMultipleByTransactionIdRearch()
    {
        $this->createDummyPayout();

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking?id=' . $this->transaction->getPublicId();

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchMultipleByBasIdRearch()
    {
        $this->createDummyPayout();

        $bas = $this->getDbLastEntity('banking_account_statement');

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking?id=' . $bas->getPublicId();

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchMultipleByBasIdWithPrivateAuthRearch()
    {
        $this->createDummyPayout();

        $bas = $this->getDbLastEntity('banking_account_statement');

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking?id=' . $bas->getPublicId();

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals(1, $response['count']);

        $txn = $response['items'][0];

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
    }

    public function testFetchMultipleWhenBasHasNoTxnIdRearch()
    {
        $this->createDummyPayout();

        $bas = $this->getDbLastEntity('banking_account_statement');

        $this->fixtures->edit('banking_account_statement', $bas->getId(), ['transaction_id' => null]);

        $bas = $this->getDbLastEntity('banking_account_statement');

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking?id=' . $bas->getPublicId();

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

    public function testFetchMultipleWhenBasHasNoTxnIdWithPrivateAuthRearch()
    {
        $this->createDummyPayout();

        $bas = $this->getDbLastEntity('banking_account_statement');

        $this->fixtures->edit('banking_account_statement', $bas->getId(), ['transaction_id' => null]);

        $bas = $this->getDbLastEntity('banking_account_statement');

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions_banking?id=' . $bas->getPublicId();

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
}
