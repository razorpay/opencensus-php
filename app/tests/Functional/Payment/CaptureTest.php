<?php

namespace Tests\Functional\Payment;

use Tests\Functional\TestCase;
use Mockery;
use Dashboard\Payment;

/**
 * Tests for capture payments
 *
 * For capture payments, first we need to create an
 * authorized payment. By default, an authorized payment entity
 * is provided. However, it doesn't have a corresponding record
 * in hdfc gateway.
 *
 * So capture tests which supposedly hit hdfc gateway for capture,
 * should first call for a normal hdfc authorized payment instead
 * of utilizing the default created payment entity.
 */

class CaptureTest extends TestCase
{
    use PaymentAuthFlowTrait;

    protected $testData = null;

    protected $payment = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/capture.php';

        parent::setUp();

        $payment = $this->fixtures->createPaymentAuthorizedEntity();
        $this->payment = $payment->toArrayPublic();

        $this->ba->privateAuth();
    }

    public function testCapture()
    {
        $this->payment = $this->defaultAuthPayment();

        $this->ba->privateAuth();

        $this->mockDashboardRequest();

        $this->startTest();
    }

    public function testCaptureTwice()
    {
        $payment = $this->fixtures->createPaymentCapturedEntity()->toArrayPublic();

        $this->payment = $payment;

        $this->startTest();
    }

    public function testCaptureWithDifferentAmount()
    {
        $amount = $this->payment['amount'] - 1000;

        $this->startTest(null, $amount);
    }

    // public function testCaptureWithLessAmountThanAuth()
    // {
    //     $amount = 10000;

    //     $this->payment = $this->defaultAuthPayment();

    //     $this->ba->privateAuth();

    //     $this->startTest(null, $amount);
    // }

    // public function testCaptureWithMoreAmountThanAuth()
    // {
    //     $amount = $this->payment['amount'] + 1000;

    //     $this->startTest(null, $amount);
    // }

    // public function testCaptureWithNoAmount()
    // {
    //     unset($this->payment['amount']);

    //     $this->startTest();
    // }

    // public function testCaptureWithZeroAmount()
    // {
    //     $this->payment['amount'] = 0;

    //     $this->startTest();
    // }

    // public function testCaptureWithMinAmountAllowedMinusOne()
    // {
    //     //
    //     // Minium amount allowed for capture
    //     //
    //     $this->payment['amount'] = 99;

    //     $this->startTest();
    // }

    // public function testCaptureWithMinAmountAllowed()
    // {
    //     $this->payment = $this->defaultAuthPayment();

    //     $this->payment['amount'] = 100;

    //     $this->ba->privateAuth();

    //     $this->startTest();
    // }

    // public function testCaptureWithOverflowingAmount()
    // {
    //     $this->payment['amount'] = 100000000000000000000000000000000000;

    //     $this->startTest();
    // }

    // public function testCaptureWithNegativeAmount()
    // {
    //     $this->payment['amount'] = -10000;

    //     $this->startTest();
    // }

    public function testCaptureWithRandomId()
    {
        $this->payment['id'] = '2fe34ae575104c0a95c3';

        $this->startTest();
    }

    public function testCaptureAfterRefund()
    {
        $payment = $this->defaultAuthPayment();

        $payment = $this->capturePayment($payment['id'], $payment['amount']);

        $refund = $this->refundPayment($payment['id']);

        $this->payment = $payment;

        $this->startTest();
    }

    public function startTest($id = null, $amount = null)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->setRequestData($testData['request'], $id, $amount);

        return $this->runRequestResponseFlow($testData);
    }

    protected function setRequestData(& $request, $id = null, $amount = null)
    {
        $this->checkAndSetIdAndAmount($id, $amount);

        $request['content']['amount'] = $amount;

        $url = '/payments/'.$id.'/capture';

        $this->setRequestUrlAndMethod($request, $url, 'POST');
    }

    protected function checkAndSetIdAndAmount(& $id = null, & $amount = null)
    {
        if ($id === null)
        {
            $id = $this->payment['id'];
        }

        if ($amount === null)
        {
            if (isset($this->payment['amount']))
                $amount = $this->payment['amount'];
        }
    }

    protected function mockDashboardRequest($times = 1)
    {
        $config = $this->config->get('applications.dashboard');

        if ($config['pretend'] === false)
        {
            return;
        }

        $dashboard = Mockery::mock('Dashboard\DashboardServiceProvider');

        $this->app->instance('dashboard', $dashboard);

        $dashboard->shouldReceive('queueRecord')
              ->times($times)
              ->with('payment', Mockery::type('Models\\Base\\PublicEntity'));
    }
}