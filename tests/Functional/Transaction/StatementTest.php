<?php

namespace RZP\Tests\Functional\Transaction;

use RZP\Models\Feature;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\InvalidArgumentException;
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

    public function testFetchStatementForPayoutFromLedger()
    {
        $this->createPayout([
            'id' => 'payout00000001'
        ]);

        $transaction = $this->getDbLastEntity('transaction');

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions/' . $transaction->getPublicId();

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_JOURNAL_READS]);

        $this->ba->privateAuth();
        $response = $this->startTest();

        // Asserts other keys existence in response.
        $this->assertNotEmpty($response['id']);
        $this->assertNotEmpty($response['created_at']);
        $this->assertNotEmpty($response['source']['id']);
        $this->assertNotEmpty($response['source']['entity']);
        $this->assertNotEmpty($response['source']['amount']);
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
