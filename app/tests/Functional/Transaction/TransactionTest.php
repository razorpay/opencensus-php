<?php

namespace Tests\Functional\Transaction;

use Tests\Functional\TestCase;
use Tests\Functional\Helpers\Payment\PaymentTrait;

class TransactionTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/TransactionData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testDummy()
    {
        ;// apparently you need to have a test per test file!
    }

    public function testAddAdjustment()
    {
        $adj = $this->startTest();

        $testData = $this->testData['testGetAdjustment'];
        $testData['request']['url'] = '/adjustments/'.$adj['id'];

        $adj = $this->runRequestResponseFlow($testData);

        $txn = $this->getLastTransaction();

        $txnData = array(
            'entity' => 'transaction',
            'entity_id' => $adj['id'],
            'type' => 'adjustment',
            'amount' => 100,
            'currency' => 'INR',
            'debit' => 0,
            'credit' => 100,
            'escrow_balance' => 1000100,
            'balance' => 1000100,
            'gateway_fee' => 0,
            'fee' => 0,
            'api_fee' => 0,
            'merchant_id' => '10000000000000',
            'pricing_rule_id' => null,
        );

        $this->assertArraySelectiveEquals($txnData, $txn);
    }

    protected function getLastTransaction()
    {
        $this->ba->proxyAuth();

        $request = array(
            'method' => 'GET',
            'url' => '/transactions?count=1');

        $content = $this->makeRequestAndGetContent($request);

        $this->assertSame('collection', $content['entity']);
        $this->assertSame(1, $content['count']);

        return $content['items'][0];
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