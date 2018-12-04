<?php

namespace RZP\Tests\Functional\Transaction;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class StatementTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/StatementData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function test1FetchStatementsPayouts()
    {
        $payoutsArray = $this->createMultiplePayouts();

        $testData = $this->testData[__FUNCTION__];

        $testData['response']['content']['items'] = $payoutsArray;

        $this->ba->proxyAuth();

        $this->startTest($testData);
    }

    public function testFetchStatement()
    {
        $this->ba->proxyAuth();

        $payoutsArray = $this->createMultiplePayouts();

        $payout = $payoutsArray[0];

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/statements/transaction/' . $payout['id'];
        $testData['response']['content'] = $payout;

        $this->ba->proxyAuth();

        $this->startTest($testData);
    }

    protected function createMultipleTransactions()
    {
        $payments = $this->fixtures->times(1)->create(
                'payment:captured',
                [
                    'merchant_id' => '10000000000000',
                    'amount' => '10000',
                ]
            );
    }

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
            // just an hack since payout fixtures doesn't call update balance method of transaction
            $txn = $payout->transaction;

            $oldBalance = $this->getEntityById('balance', '10000000000000', true)['balance'];

            $newBalance = $oldBalance - $payout->getAmount();

            $this->fixtures->edit('balance', '10000000000000', ['balance' => $newBalance]);

            $this->fixtures->edit('transaction', $txn->getId(), ['balance' => $newBalance, 'balance_id' => '10000000000000']);

            $txn->reload();

            $customer = $payout->customer;
            $destination = $payout->destination;

            $payoutEntry['id']        = 'stmt_' . $txn->getId();
            $payoutEntry['entity']    = 'statement';
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

            $payoutArray[] = $payoutEntry;
        }

        return array_reverse($payoutArray);
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
