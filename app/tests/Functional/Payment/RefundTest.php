<?php

namespace Tests\Functional\Payment;

use Tests\Functional\TestCase;

/**
 * Tests for refund payments
 *
 * For refund payments, first we need to create a
 * captured payment. By default, an captured txn entity
 * is provided. However, it doesn't have a corresponding record
 * in hdfc gateway.
 *
 * So refund tests which supposedly hit hdfc gateway for refund,
 * should first call for a normal hdfc authorized + captured payment
 * instead of utilizing the default created payment entity.
 */

class RefundTest extends TestCase
{
    use PaymentAuthFlowTrait;

    protected $txn = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/refund.php';

        parent::setUp();

        $this->testData = include(__DIR__.'/helpers/refund.php');

        $this->txn = $this->createCapturedPaymentEntity();

        $this->setupPrivateBasicAuthParams();
    }

    public function testRefund()
    {
        $txn = $this->defaultAuthPayment();
        $txn = $this->capturePayment($txn['id'], $txn['amount']);

        $refund = $this->startTest($txn['id'], (string)$txn['amount']);

        $this->assertEquals(substr($refund['id'], 0, 5), 'rfnd-');

        $this->assertGreaterThan(time() - 30, $refund['created_at']);
    }

    public function testMultipleRefunds()
    {
        $txn = $this->defaultAuthPayment();
        $txn = $this->capturePayment($txn['id'], $txn['amount']);

        $this->refundPayment($txn['id'], '10000');
        $this->refundPayment($txn['id'], '20000');
        $this->refundPayment($txn['id'], '12000');
        $this->refundPayment($txn['id'], '8000');

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/'.$txn['id'];

        return $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testRefundWithHigherAmount()
    {
        $this->startTest($this->txn['id'], 50001);
    }

    public function testMultipleRefundsWithHigherAmount()
    {
        $txn = $this->defaultAuthPayment();
        $txn = $this->capturePayment($txn['id'], $txn['amount']);

        $this->refundPayment($txn['id'], 10000);
        $this->refundPayment($txn['id'], 20000);

        $this->startTest($txn['id'], 30000);
    }

    public function testRefundOnRefundedPayment()
    {
        $txn = $this->defaultAuthPayment();
        $txn = $this->capturePayment($txn['id'], $txn['amount']);

        $this->refundPayment($txn['id']);

        $this->startTest($txn['id'], 100);
    }

    public function testRefundOnAuthorizedPayment()
    {
        $this->txn = $this->defaultAuthPayment();

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

        $url = '/payments/'.$id.'/refund';

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