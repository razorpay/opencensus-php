<?php

namespace RZP\Tests\Functional\Payment;

use Carbon\Carbon;
use Mockery;
use Dashboard\Payment;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

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
    use PaymentTrait;

    protected $testData = null;

    protected $payment = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/capture.php';

        parent::setUp();

        $payment = $this->fixtures->create('payment:authorized');
        $this->payment = $payment->toArrayPublic();

        $this->ba->privateAuth();
    }

    public function testCapture()
    {
        $this->payment = $this->defaultAuthPayment();

        $this->ba->privateAuth();

        $this->mockDashboardRequest();

        $this->startTest();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['gateway_captured']);
    }

    public function testCaptureTwice()
    {
        $payment = $this->fixtures->create('payment:captured')->toArrayPublic();

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

    public function testAutoCapture()
    {
        $this->app['config']->set('gateway.mock_hdfc', true);
        $this->app['config']->set('gateway.mock_atom', true);

        $created_at = time() - rand(0, 23) * 60 * 60;
        $updated_at = $created_at;

        $payment = $this->fixtures->create(
            'payment:authorized', ['created_at' => $created_at, 'updated_at' => $updated_at]);

        $payment = $this->fixtures->create(
            'payment:netbanking_authorized', ['created_at' => $created_at, 'updated_at' => $updated_at]);

        $created_at = time() - (24 + rand(0, 23)) * 60 * 60 - rand(0, 3600);
        $updated_at = $created_at;

        $payment = $this->fixtures->create(
            'payment:status_created', ['created_at' => $created_at, 'updated_at' => $updated_at]);

        $payment = $this->fixtures->create(
            'payment:captured', ['created_at' => $created_at, 'updated_at' => $updated_at]);

        $payment = $this->fixtures->create(
            'payment:netbanking_captured', ['created_at' => $created_at, 'updated_at' => $updated_at]);

        $x = range(1,3);

        foreach ($x as $i)
        {
            $created_at = time() - (24 + rand(0, 23)) * 60 * 60 - rand(0, 3600);
            $updated_at = $created_at;

            $payment = $this->fixtures->create(
                'payment:authorized',
                ['created_at' => $created_at,
                 'updated_at' => $updated_at]);
        }

        foreach ($x as $i)
        {
            $created_at = time() - (24 + rand(0, 23)) * 60 * 60 - rand(0, 3600);
            $updated_at = $created_at;

            $payment = $this->fixtures->create(
                'payment:netbanking_authorized',
                ['created_at' => $created_at,
                 'updated_at' => $updated_at]);
        }

        $payment = $this->fixtures->create('payment:netbanking_authorized');

        $content = $this->doAutoCapture();

        $this->assertSame(6, $content['count']);
    }

    public function testAutoCaptureEmail()
    {
        $time = Carbon::today('Asia/Kolkata')->timestamp;
        $created_at = $time - rand(0, 23) * 60 * 60;
        $updated_at = $created_at;

        // The following two payments have been captured but not auto-captured
        $payment = $this->fixtures->create(
            'payment:captured', ['created_at' => $created_at, 'updated_at' => $updated_at]);
        $payment = $this->fixtures->create(
            'payment:netbanking_captured', ['created_at' => $created_at, 'updated_at' => $updated_at]);

        $created_at = $time - rand(0, 23) * 60 * 60 - rand(0, 3600);
        $updated_at = $created_at;

        $payment = $this->fixtures->create(
            'payment:status_created', ['created_at' => $created_at, 'updated_at' => $updated_at]);

        $payment = $this->fixtures->create(
            'payment:authorized', ['created_at' => $created_at, 'updated_at' => $updated_at]);

        $payment = $this->fixtures->create(
            'payment:netbanking_authorized', ['created_at' => $created_at, 'updated_at' => $updated_at]);

        $x = range(1,3);

        $merchant = $this->fixtures->create('merchant_fluid')->get();

        // Only the following 6 payments are actually auto-captured. The above rest is just noise
        foreach ($x as $i)
        {
            $created_at = $time - rand(0, 23) * 60 * 60 - rand(0, 3600);
            $updated_at = $created_at;

            $payment = $this->fixtures->create(
                'payment:captured',
                ['created_at' => $created_at,
                 'updated_at' => $updated_at,
                 'auto_captured' => 1]);
        }

        foreach ($x as $i)
        {
            $created_at = $time - rand(0, 23) * 60 * 60 - rand(0, 3600);
            $updated_at = $created_at;

            $payment = $this->fixtures->create(
                'payment:netbanking_captured',
                ['created_at' => $created_at,
                 'updated_at' => $updated_at,
                 'auto_captured' => 1,
                 'merchant_id' => $merchant->getId()]);
        }

        $payment = $this->fixtures->create('payment:netbanking_authorized');

        $mock = Mockery::mock('RZP\Services\Mailgun')->makePartial()->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('sendMessage')->times(2);
        $mock->shouldReceive('getMode')->andReturn('test');

        $this->app->instance('mailgun', $mock);

        $content = $this->sendAutoCaptureEmails();

        $this->assertSame(6, $content['payments_count']);
        $this->assertSame(2, $content['emails_count']);
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

        $dashboard = Mockery::mock('RZP\Dashboard\DashboardServiceProvider');

        $this->app->instance('dashboard', $dashboard);

        $dashboard->shouldReceive('queueRecord')
              ->times($times)
              ->with('payment', Mockery::type('RZP\Models\\Base\\PublicEntity'));
    }
}
