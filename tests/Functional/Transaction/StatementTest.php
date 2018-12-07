<?php

namespace RZP\Tests\Functional\Transaction;

use RZP\Models\Feature;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class StatementTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/StatementTestData.php';

        parent::setUp();

        $this->fixtures->merchant->createBalanceOfBankingType();

        $this->ba->proxyAuth();
    }

    public function testFetchStatements()
    {
        $bankTransfers = $this->createMultipleBankTransfer();
        $payouts       = $this->createMultiplePayouts();

        $testData = & $this->testData[__FUNCTION__];
        $testData['response']['content']['items'] = array_merge($payouts, $bankTransfers);

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testFetchStatementPayout()
    {
        $payouts = $this->createMultiplePayouts();

        $payout = current($payouts);
        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url']      = '/transactions/' . $payout['id'];
        $testData['response']['content'] = $payout;

        $this->ba->proxyAuth();
        $this->startTest();
    }


    public function testFetchStatementBankTransfer()
    {
        $bankTransfers = $this->createMultipleBankTransfer();

        $bankTransfer = current($bankTransfers);
        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url']      = '/transactions/' . $bankTransfer['id'];
        $testData['response']['content'] = $bankTransfer;

        $this->ba->proxyAuth();
        $this->startTest();
    }


    // ---------------- Not test/protected methods follows. -------------------

    protected function createMultiplePayouts()
    {
        $payoutArray = [];

        $payouts = $this->fixtures->times(2)->create(
            'payout',[
                'merchant_id' => '10000000000000',
                'amount' => '10000'
            ]);

        foreach ($payouts as $payout)
        {
            $payoutArray[] = $this->getPayoutEntry($payout);
        }

        return array_reverse($payoutArray);
    }

    protected function getPayoutEntry($payout)
    {
        // just an hack since payout fixtures doesn't call update balance method of transaction
        $txn = $payout->transaction;

        $oldBalance = $this->getEntityById('balance', '100000000000BB', true)['balance'];

        $newBalance = $oldBalance - $payout->getAmount();

        $this->fixtures->edit('balance', '100000000000BB', ['balance' => $newBalance]);

        $this->fixtures->edit('transaction', $txn->getId(), ['balance' => $newBalance, 'balance_id' => '100000000000BB']);

        $txn->reload();

        $customer = $payout->customer;
        $destination = $payout->destination;

        $payoutEntry['id']        = 'txn_' . $txn->getId();
        $payoutEntry['entity']    = 'transaction';
        $payoutEntry['source_id'] = $payout->getPublicId();
        $payoutEntry['UTR']       = $payout->getUtr();
        $payoutEntry['amount']    = $txn->getAmount();
        $payoutEntry['credit']    = $txn->getCredit();
        $payoutEntry['debit']     = $txn->getDebit();
        $payoutEntry['balance']   = $txn->getBalance();

        $source['id']          = $payout->getPublicId();
        $source['entity']      = 'payout';
        $source['customer_id'] = $customer->getPublicId();
        $source['destination'] = $destination->getPublicId();
        $source['method']      = $payout->getMethod();
        $source['currency']    = $payout->getCurrency();
        $source['notes']       = $payout->getNotes()->toArray();

        $customer = $payout->customer->toArrayPublic();
        $account = $payout->destination->toArrayPublic();

        $source['customer'] = $customer;
        $source['account']  = $account;

        $payoutEntry['source'] = $source;
        $payoutEntry['created_at'] = $txn->getCreatedAt();
        $payoutEntry['updated_at'] = $txn->getUpdatedAt();

        return $payoutEntry;
    }

    protected function createMultipleBankTransfer()
    {
        $bankAccount = $this->createVirtualAccount();

        $bankTransferArray = [];

        foreach(range(1,3) as $i)
        {
            $bankTransferEntity = $this->processBankTransfer($bankAccount);

            $bankTransferArray[] = $this->getBankTransferEntry($bankTransferEntity);
        }
        return array_reverse($bankTransferArray);
    }

    protected function getBankTransferEntry($bankTransferEntity)
    {
        $txn = $bankTransferEntity->transaction;

        $btEntry['id'] = 'txn_' . $txn->getId();
        $btEntry['entity'] = 'transaction';
        $btEntry['source_id'] = $bankTransferEntity->getPublicId();
        $btEntry['UTR'] = $bankTransferEntity->getUtr();
        $btEntry['amount'] = $txn->getAmount();
        $btEntry['credit'] = $txn->getCredit();
        $btEntry['debit'] = $txn->getDebit();
        $btEntry['balance'] = $txn->getBalance();

        $source['id'] = $bankTransferEntity->getPublicId();
        $source['mode'] = strtoupper($bankTransferEntity->getMode());
        $source['amount'] = $bankTransferEntity->getAmount();
        $source['bank_reference'] = $bankTransferEntity->getUtr();
        $source['payer_name'] = $bankTransferEntity->getPayerName();
        $source['payer_account'] = $bankTransferEntity->getPayerAccount();
        $source['payer_ifsc'] = $bankTransferEntity->getPayerIfsc();

        $btEntry['source'] = $source;
        $btEntry['created_at'] = $txn->getCreatedAt();
        $btEntry['updated_at'] = $txn->getUpdatedAt();

        return $btEntry;
    }

    protected function createVirtualAccount()
    {
        $terminalAttributes = [
            'id' => 'SHRDBANKACC3DS',
            'gateway' => 'bt_dashboard',
            'gateway_merchant_id' => '111222',
            'gateway_merchant_id2' => '00',
            'bank_transfer' => 1,
            'recurring' => 0,
            'type' => [
                'non_recurring' => '1',
                'numeric_account' => '1',
                'business_banking' => '1',
            ],
            'bank_transfer'=> 1,
            'enabled' => true
        ];

        $this->fixtures->create('terminal:shared_bank_account_terminal', $terminalAttributes);

        $this->fixtures->merchant->addFeatures(['virtual_accounts', 'bharat_qr']);
        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->ba->proxyAuth();

        $request = $this->testData[__FUNCTION__];

        $response = $this->makeRequestAndGetContent($request);

        $bankAccount = $response['receivers'][0];

        return $bankAccount;
    }

    protected function processBankTransfer($bankAccount)
    {
        $request = $this->testData[__FUNCTION__];

        $request['content']['payee_account'] = $bankAccount['account_number'];

        $request['content']['payee_ifsc'] = $bankAccount['ifsc'];

        $utr = strtoupper(random_alphanum_string(22));

        $request['content']['transaction_id'] = $utr;

        $request['content']['amount'] = 50000;

        $this->ba->appAuth();

        $response = $this->makeRequestAndGetContent($request);

        $bankTransfer = $this->getLastEntity('bank_transfer', true);

        $this->assertEquals($utr, $response['transaction_id']);

        return $this->getDbEntityById('bank_transfer', $bankTransfer['id'], true);
    }

    protected function startTest($testDataToReplace = array())
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->replaceValuesRecursively($testData, $testDataToReplace);

        return $this->runRequestResponseFlow($testData);
    }
}
