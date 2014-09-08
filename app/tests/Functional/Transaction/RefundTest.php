<?php

namespace Tests\Functional\Transaction;

use Tests\Functional\TestCase;

/**
 * Tests for refund transactions
 *
 * For refund transactions, first we need to create a
 * captured transaction. By default, an captured txn entity
 * is provided. However, it doesn't have a corresponding record
 * in hdfc gateway.
 *
 * So refund tests which supposedly hit hdfc gateway for refund,
 * should first call for a normal hdfc authorized + captured transaction
 * instead of utilizing the default created transaction entity.
 */

class RefundTest extends TestCase
{
    use TransactionAuthFlowTrait;

    protected $txn = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/refund.php';

        parent::setUp();

        $this->testData = include(__DIR__.'/helpers/refund.php');

        $this->txn = $this->createCapturedTransactionEntity();

        $this->setupPrivateBasicAuthParams();
    }

    public function testRefund()
    {
        $refund = $this->startTest();

        $this->assertEquals(substr($refund['id'], 0, 5), 'rfnd-');

        $this->assertGreaterThan(time() - 30, $refund['created_at']);
    }

    public function testMultipleRefunds()
    {
        $this->refundTransaction($this->txn['id'], 10000);
        $this->refundTransaction($this->txn['id'], 20000);
        $this->refundTransaction($this->txn['id'], 12000);
        $this->refundTransaction($this->txn['id'], 8000);

        $this->testData[__FUNCTION__]['request']['url'] = '/transactions/'.$this->txn['id'];

        return $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testRefundWithHigherAmount()
    {
        $this->startTest($this->txn['id'], 50001);
    }

    public function testMultipleRefundsWithHigherAmount()
    {
        $this->refundTransaction($this->txn['id'], 10000);
        $this->refundTransaction($this->txn['id'], 20000);

        $this->startTest($this->txn['id'], 30000);
    }

    public function testRefundOnRefundedTransaction()
    {
        $this->refundTransaction($this->txn['id']);

        $this->startTest($this->txn['id'], 100);
    }

    public function testRefundOnAuthorizedTransaction()
    {
        $this->txn = $this->defaultAuthTransaction();

        $this->setupPrivateBasicAuthParams();

        $this->startTest();
    }

    public function testRefundWithNegativeAmount()
    {
        $this->startTest(null, -1);
    }

    public function testRefundWithZeroAmount()
    {
        $this->startTest(null, 0);
    }

    public function startTest($txnId = null, $amount = null)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->setRequestData($testData['request'], $txnId, $amount);

        return $this->runRequestResponseFlow($testData);
    }

    protected function setRequestData(& $request, $id = null, $amount = null)
    {
        $this->checkAndSetId($id);

        if ($amount !== null)
        {
            $request['content']['amount'] = $amount;
        }

        $url = '/transactions/'.$id.'/refund';

        $this->setRequestUrlAndMethod($request, $url, 'POST');
    }

    protected function checkAndSetId(& $id = null)
    {
        if ($id === null)
        {
            $id = $this->txn['id'];
        }

    }
}