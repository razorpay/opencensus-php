<?php

namespace Tests\Functional\Payment;

use Tests\Functional\TestCase;
use Mockery;

/**
 * Tests for refund payments
 *
 * For refund payments, first we need to create a
 * captured payment. By default, an captured payment entity
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

    protected $payment = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/refund.php';

        parent::setUp();

        $this->payment = $this->createCapturedPaymentEntity();

        $this->setupPrivateBasicAuthParams();
    }

    public function testRefund()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->mockDashboardRequest();

        $refund = $this->startTest($payment['id'], (string)$payment['amount']);

        $this->assertEquals(substr($refund['id'], 0, 5), 'rfnd-');

        $this->assertGreaterThan(time() - 30, $refund['created_at']);
    }

    public function testMultipleRefunds()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->mockDashboardRequest(4);

        $this->refundPayment($payment['id'], '10000');
        $this->refundPayment($payment['id'], '20000');
        $this->refundPayment($payment['id'], '12000');
        $this->refundPayment($payment['id'], '8000');

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/'.$payment['id'];

        return $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testRefundWithHigherAmount()
    {
        $this->startTest($this->payment['id'], 50001);
    }

    public function testMultipleRefundsWithHigherAmount()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->refundPayment($payment['id'], 10000);
        $this->refundPayment($payment['id'], 20000);

        $this->startTest($payment['id'], 30000);
    }

    public function testRefundOnRefundedPayment()
    {
        $payment = $this->defaultAuthPayment();
        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $this->refundPayment($payment['id']);

        $this->startTest($payment['id'], 100);
    }

    public function testRefundOnAuthorizedPayment()
    {
        $this->payment = $this->defaultAuthPayment();

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

    public function startTest($paymentId = null, $amount = null)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->setRequestData($testData['request'], $paymentId, $amount);

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
            $id = $this->payment['id'];
        }
    }

    protected function mockDashboardRequest($times = 1)
    {
        $config = $this->config->get('applications.dashboard');

        if ($config['pretend'] === false)
        {
            return;
        }

        $dashboard = Mockery::mock('Services\Dashboard');

        $this->app->instance('dashboard', $dashboard);

        $dashboard->shouldReceive('queueRecord')
              ->times($times)
              ->with('refund', Mockery::type('Models\\Base\\PublicEntity'));
    }
}