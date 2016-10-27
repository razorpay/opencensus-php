<?php

namespace RZP\Tests\Functional\Payment;

use DB;
use Mockery;
use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Batch\Status;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

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

class VerifyTest extends TestCase
{
    use PaymentTrait;

    protected $payment = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/VerifyTestData.php';

        parent::setUp();

        $this->payment = $this->fixtures->create('payment:captured');

        $this->ba->cronAuth();
    }

    public function testNonCronCaller()
    {
        $createdAt = time() - 3*60;

        $this->ba->appAuth();

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $verifiedResultArray = [
            'filter'  => 'payments_failed',
            'all'     => 1,
            'none'    => 0,
        ];

        $filter = $verifiedResultArray['filter'];

        $request = [
            'url' => '/payments/verify/'. $filter,
            'method' => 'post'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $resultData =[
            'verified' => 1,
            'filter'   => 'payments_failed'
        ];

        $this->assertContent($content, $resultData);

        $content = $this->makeRequestAndGetContent($request);

        $this->assertContent($content, $resultData);

        $this->ba->cronAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertContent($content, $resultData);

        $content = $this->makeRequestAndGetContent($request);

        $resultData =[
            'verified' => 0,
            'filter'   => 'payments_failed'
        ];

        $this->assertContent($content, $resultData);
    }

    public function testVerifyForPaymentsWithNullBucket()
    {
        $createdAt = time() - 3*60;

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', [
                'created_at'    => $createdAt,
                'verify_bucket' => null,
            ]);

        $this->testVerifySingleFailedPayments();
    }

    public function testVerifySingleFailedPayments()
    {
        $createdAt = time() - 3*60;

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $this->runVerifyForMaxPeriod();
    }

    public function testVerifyMultipleFailedPayments()
    {
        $createdAt = time() - 3 * 60;

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $createdAt = time() - 4 * 60;

        $payment2 = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $result = [
            'filter'  => 'payments_failed',
            'all'     => 2,
            'none'    => 0,
        ];

        $this->runVerifyForMaxPeriod($result);
    }

    public function testVerifySingleCreatedPayments()
    {
        $createdAt = time() - 3*60;

        $payment = $this->fixtures->create(
            'payment:netbanking_created', ['created_at' => $createdAt]);

        $verifiedResultArray = [
            'filter'  => 'payments_created',
            'all'     => 1,
            'none'    => 0,
        ];

        $filter = $verifiedResultArray['filter'];

        $time = Carbon::now('Asia/Kolkata');

        $request = [
            'url' => '/payments/verify/'. $filter,
            'method' => 'post'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'verified' => $verifiedResultArray['all'],
            'filter'   => $filter,
        ];

        $this->assertContent($content, $resultData);

        foreach (range(0, 5) as $index)
        {
            $time->addSeconds(150);

            Carbon::setTestNow($time);

            $content = $this->makeRequestAndGetContent($request);

            $resultData = [
                'verified' => $verifiedResultArray['all'],
                'filter'   => $filter,
            ];

            $this->assertContent($content, $resultData);
        }

        Carbon::setTestNow();
    }

    protected function runVerifyForMaxPeriod($verifiedResultArray = null)
    {
        if ($verifiedResultArray === null)
        {
            $verifiedResultArray = [
                'filter'  => 'payments_failed',
                'all'     => 1,
                'none'    => 0,
            ];
        }

        $filter = $verifiedResultArray['filter'];

        $time = Carbon::now('Asia/Kolkata');

        $request = [
            'url' => '/payments/verify/'. $filter,
            'method' => 'post'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'verified' => $verifiedResultArray['all'],
            'filter'   => $filter,
        ];

        $this->assertContent($content, $resultData);

        $time->addMinutes(15);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'verified' => $verifiedResultArray['all'],
            'filter'   => $filter,
        ];

        $this->assertContent($content, $resultData);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'verified' => $verifiedResultArray['none'],
            'filter'   => $filter,
        ];

        $this->assertContent($content, $resultData);

        $time->addMinutes(45);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'verified' => $verifiedResultArray['all'],
            'filter'   => $filter,
        ];

        $this->assertContent($content, $resultData);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'verified' => $verifiedResultArray['none'],
            'filter'   => $filter,
        ];

        $this->assertContent($content, $resultData);

        foreach (range(1, 7) as $day)
        {
            $time->addDay(1);

            Carbon::setTestNow($time);

            $content = $this->makeRequestAndGetContent($request);

            $resultData = [
                'verified' => $verifiedResultArray['all'],
                'filter'   => $filter,
            ];

            $this->assertContent($content, $resultData);

            $content = $this->makeRequestAndGetContent($request);

            $resultData = [
                'verified' => $verifiedResultArray['none'],
                'filter'   => $filter,
            ];

            $this->assertContent($content, $resultData);
        }

        $time->addDay(1);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'verified' => $verifiedResultArray['none'],
            'filter'   => $filter,
        ];

        $this->assertContent($content, $resultData);

        Carbon::setTestNow();
    }

    protected function assertContent(array $content, array $param)
    {
        // We dont want to check time taken for payments
        unset($content['total_time']);

        unset($content['authorized_time']);

        $defaultParams = [
            'verified'          => 0,
            'authorized/failed' => 0,
            'timed_out'         => 0,
            'error'             => 0,
        ];

        $defaultParams = array_merge($defaultParams, $param);

        $this->assertEquals($defaultParams, $content);
    }

    public function testTimeoutPaymentVerifyFailure()
    {
        $this->gateway = 'ebs';

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_ebs_terminal');

        $data = $this->testData['testTimeoutPaymentVerify'];

        $this->getErrorInCallback();

        $payment = $this->getDefaultNetbankingPaymentArray('ANDB');

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $payment = $this->doAuthAndCapturePayment($payment);
            }
        );

        $payment = $this->getLastEntity('payment', true);

        $this->resetMockServer();

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/payments_failed',
            'method' => 'post'
        ];

        $time = Carbon::now('Asia/Kolkata');

        Carbon::setTestNow($time->addMinutes(5));

        $content = $this->makeRequestAndGetContent($request);

        $filter = 'verify_failed';

        $request = [
            'url'    => '/payments/verify/'. $filter,
            'method' => 'post'
        ];

        Carbon::setTestNow($time->addMinutes(60));

        $content = $this->makeRequestAndGetContent($request);

        $resultData = ['filter' => $filter, 'verified' => 1];

        $this->assertContent($content, $resultData);

        Carbon::setTestNow();
    }

    public function testTimeoutPaymentVerify()
    {
        $this->gateway = 'ebs';

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_ebs_terminal');

        $data = $this->testData['testTimeoutPaymentVerify'];

        $this->getErrorInCallback();

        $payment = $this->getDefaultNetbankingPaymentArray('ANDB');

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $payment = $this->doAuthAndCapturePayment($payment);
            }
        );

        $payment = $this->getLastEntity('payment', true);

        $this->getTimeoutInVerify();

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/payments_failed',
            'method' => 'post'
        ];

        $time = Carbon::now('Asia/Kolkata');

        $time->addMinutes(15);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'timed_out' => 1,
            'filter'    => 'payments_failed',
        ];

        $this->assertContent($content, $resultData);

        $request = [
            'url'    => '/payments/verify/verify_error',
            'method' => 'post'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $resultData = ['filter' => 'verify_error'];

        $this->assertContent($content, $resultData);

        $time->addMinutes(60);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'timed_out' => 1,
            'filter'    => 'verify_error'
        ];

        $this->assertContent($content,  $resultData);

        $time->addDay();

        Carbon::setTestNow($time);

        $this->resetMockServer();

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'authorized/failed' => 1,
            'filter'            => 'verify_error'
        ];

        $this->assertContent($content, $resultData);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');

        Carbon::setTestNow();
    }

    public function testVerifyFailedWithZeroValidPaymnets()
    {
        $createdAt = time() - 60 * 60;

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $request = array(
            'url' => '/payments/verify/verify_failed',
            'method' => 'post'
        );

        $this->ba->cronAuth();

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'authorized/failed' => 0,
            'filter'            => 'verify_failed'
        ];

        $this->assertContent($content, $resultData);
    }

    public function testInvalidFilter()
    {
        $data = $this->testData['testInvalidFilter'];

        $createdAt = time() - 60 * 60;

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $request = array(
            'url' => '/payments/verify/invalid',
            'method' => 'get'
        );

        $this->ba->cronAuth();

        $this->runRequestResponseFlow(
            $data,
            function() use ($request)
            {
                $content = $this->makeRequestAndGetContent($request);
            }
        );
    }

    public function testVerifyAllPayments()
    {
        $createdAt = time() - 60 * 60;

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $request = array(
            'url' => '/payments/verify/all',
            'method' => 'get'
        );

        $this->ba->cronAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals(
            [
                'filter'            => 'all',
                'verified'          => 1,
                'authorized/failed' => 0,
                'timed_out'         => 0,
                'error'             => 0,
                'authorized_time'   => 0,
                'total_time'        => '0 secs',
            ],
            $content);
    }
}
