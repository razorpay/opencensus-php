<?php

namespace RZP\Tests\Functional\Transaction;

use RZP\Models\Feature;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;

class StatementTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use VirtualAccountTrait;
    use TestsBusinessBanking;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/StatementTestData.php';

        parent::setUp();

        $this->setUpMerchantForBusinessBanking(false, 100000);
    }

    public function testFetchMultipleStatements()
    {
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

        $this->assertEquals($this->transaction->getPublicId(), $txn['id']);
        $this->assertEquals($this->transaction['amount'], $txn['amount']);
        $this->assertEquals($this->transaction->getSignedEntityId(), $txn['source']['id']);
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

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions?contact_email=' . $this->contact['email'];

        // Sets ES fetch expected retunr values.
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


    protected function createBankTransferTransaction()
    {
        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->payVirtualAccount($this->virtualAccount->getPublicId(), ['amount' => 25]);
    }
}
