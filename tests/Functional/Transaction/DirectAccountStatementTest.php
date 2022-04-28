<?php

namespace RZP\Tests\Functional\Transaction;

use RZP\Models\Feature;
use RZP\Constants\Mode;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Exception\InvalidArgumentException;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\FundAccount\FundAccountTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;
use RZP\Tests\Functional\Helpers\FundAccount\FundAccountValidationTrait;

class DirectAccountStatementTest extends TestCase
{
    use PaymentTrait;
    use FundAccountTrait;
    use DbEntityFetchTrait;
    use VirtualAccountTrait;
    use TestsBusinessBanking;
    use FundAccountValidationTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/DirectAccountStatementTestData.php';

        parent::setUp();

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->setUpMerchantForBusinessBanking(false, 10000000, 'direct', 'rbl');

        $this->fixtures->merchant->addFeatures([Feature\Constants::DA_LEDGER_REVERSE_SHADOW]);

        $this->setMockRazorxTreatment([RazorxTreatment::RX_DA_ACC_STMT_EXPERIMENT => 'on',]);

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

        $this->reversePayout($payout);

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

    public function testFetchMultipleStatementsForDirectAccount()
    {
        $this->createDummyPayout();

        $this->createDummyReversal();

        $this->createDummyExternal();

        // Creates one normal payment transaction on primary balance.
        $this->doAuthAndCapturePayment(null, 50000);

        // One the first two transactions should appear in response.
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testFetchMultipleStatementsForBankingForDirectAccount()
    {
        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->fixtures->edit('merchant', '10000000000000', ['business_banking' => true]);

        $this->createDummyPayout();

        $this->createDummyReversal();

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
}
