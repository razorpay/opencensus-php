<?php

namespace RZP\Tests\Functional\Payment;

use DB;
use Mockery;
use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Batch\Status;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

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
        $createdAt = time() - 180;

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
            'url'    => '/payments/verify/'. $filter,
            'method' => 'post'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $resultData =[
            'success' => 1,
            'filter'  => 'payments_failed'
        ];

        $this->assertContent($content, $resultData);

        $content = $this->makeRequestAndGetContent($request);

        $this->assertContent($content, $resultData);

        $this->ba->cronAuth();

        $content = $this->makeRequestAndGetContent($request);

        $this->assertContent($content, $resultData);

        $content = $this->makeRequestAndGetContent($request);

        $resultData =[
            'success' => 0,
            'filter'  => 'payments_failed'
        ];

        $this->assertContent($content, $resultData);
    }

    public function testVerifyForPaymentsWithNullBucket()
    {
        $createdAt = time() - 180;

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', [
                'created_at'    => $createdAt,
                'verify_bucket' => null,
            ]);

        $this->testVerifySingleFailedPayments();
    }

    public function testVerifySingleFailedPayments()
    {
        $createdAt = time() - 180;

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $this->runVerifyForMaxPeriod();
    }

    public function testVerifyMultipleFailedPayments()
    {
        $createdAt = time() - 180;

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $createdAt = time() - 240;

        $payment2 = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $result = [
            'filter'  => 'payments_failed',
            'all'     => 2,
            'none'    => 0,
        ];

        $this->runVerifyForMaxPeriod($result);
    }

    public function testVerifyWithLockedPayments()
    {
        $createdAt = time() - 180;

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $createdAt = time() - 240;

        $payment2 = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $result = [
            'filter'  => 'payments_failed',
            'all'     => 2,
            'none'    => 0,
        ];

        // Lock payment for 10 days, No verify should run on this payment
        $this->app['api.mutex']->acquire($payment2['id'].'_verify', 864000);

        $result = [
            'filter'  => 'payments_failed',
            'all'     => 1,
            'none'    => 0,
        ];

        $this->runVerifyForMaxPeriod($result);
    }

    public function testNewlyCreatedPayment()
    {
        $createdAt = time();

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
            'url'    => '/payments/verify/'. $filter,
            'method' => 'post'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'success' => 0,
            'filter'  => $filter,
        ];

        $this->assertContent($content, $resultData);

        $time = Carbon::now('Asia/Kolkata');

        Carbon::setTestNow($time->addSeconds(180));

        $this->runCreateVerify();
    }

    public function testVerifySingleCreatedPayments()
    {
        $createdAt = time() - 180;

        $payment = $this->fixtures->create(
            'payment:netbanking_created', ['created_at' => $createdAt]);

        $this->runCreateVerify();
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

        $resultData = ['filter' => $filter, 'success' => 1];

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
            'timeout' => 1,
            'filter'  => 'payments_failed',
        ];

        $this->assertContent($content, $resultData);

        $request = [
            'url'    => '/payments/verify/verify_error',
            'method' => 'post'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'timeout' => 1,
            'filter'  => 'verify_error'
        ];

        $this->assertContent($content, $resultData);

        $time->addMinutes(60);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'timeout' => 1,
            'filter'  => 'verify_error'
        ];

        $this->assertContent($content,  $resultData);

        $time->addDay();

        Carbon::setTestNow($time);

        $this->resetMockServer();

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'authorized' => 1,
            'filter'     => 'verify_error'
        ];

        $this->assertContent($content, $resultData);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['status'], 'authorized');

        Carbon::setTestNow();
    }

    public function testVerifyFailedWithZeroValidPaymnets()
    {
        $createdAt = time() - 3600;

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $request = array(
            'url'    => '/payments/verify/verify_failed',
            'method' => 'post'
        );

        $this->ba->cronAuth();

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'authorized' => 0,
            'filter'     => 'verify_failed'
        ];

        $this->assertContent($content, $resultData);
    }

    public function testInvalidFilter()
    {
        $data = $this->testData['testInvalidFilter'];

        $createdAt = time() - 3600;

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $request = array(
            'url'    => '/payments/verify/invalid',
            'method' => 'post'
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

    protected function runCreateVerify()
    {
        $verifiedResultArray = [
            'filter'  => 'payments_created',
            'all'     => 1,
            'none'    => 0,
        ];

        $filter = $verifiedResultArray['filter'];

        $time = Carbon::now('Asia/Kolkata');

        $request = [
            'url'    => '/payments/verify/'. $filter,
            'method' => 'post'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'success' => $verifiedResultArray['all'],
            'filter'  => $filter,
        ];

        $this->assertContent($content, $resultData);

        foreach (range(0, 5) as $index)
        {
            $time->addSeconds(150);

            Carbon::setTestNow($time);

            $content = $this->makeRequestAndGetContent($request);

            $resultData = [
                'success' => $verifiedResultArray['all'],
                'filter'  => $filter,
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
            'url'    => '/payments/verify/'. $filter,
            'method' => 'post'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'success' => $verifiedResultArray['all'],
            'filter'  => $filter,
        ];

        $this->assertContent($content, $resultData);

        $time->addMinutes(15);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'success' => $verifiedResultArray['all'],
            'filter'  => $filter,
        ];

        $this->assertContent($content, $resultData);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'success' => $verifiedResultArray['none'],
            'filter'  => $filter,
        ];

        $this->assertContent($content, $resultData);

        $time->addMinutes(45);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'success' => $verifiedResultArray['all'],
            'filter'  => $filter,
        ];

        $this->assertContent($content, $resultData);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'success' => $verifiedResultArray['none'],
            'filter'  => $filter,
        ];

        $this->assertContent($content, $resultData);

        foreach (range(1, 7) as $day)
        {
            $time->addDay(1);

            Carbon::setTestNow($time);

            $content = $this->makeRequestAndGetContent($request);

            $resultData = [
                'success' => $verifiedResultArray['all'],
                'filter'  => $filter,
            ];

            $this->assertContent($content, $resultData);

            $content = $this->makeRequestAndGetContent($request);

            $resultData = [
                'success' => $verifiedResultArray['none'],
                'filter'  => $filter,
            ];

            $this->assertContent($content, $resultData);
        }

        $time->addDay(1);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'success' => $verifiedResultArray['none'],
            'filter'  => $filter,
        ];

        $this->assertContent($content, $resultData);

        Carbon::setTestNow();
    }

    protected function assertContent(array $content, array $param)
    {
        // We dont want to check time taken for payments
        unset($content['total_time']);

        unset($content['authorize_time']);

        // TODO : built it from previous values
        unset($content['max_count']);

        $defaultParams = [
            'success'       => 0,
            'authorized'    => 0,
            'timeout'       => 0,
            'error'         => 0,
        ];

        $total = array_sum($defaultParams);


        $defaultParams = array_merge($defaultParams, $param);

        $defaultParams['total_payments'] = $defaultParams['success'] +
            $defaultParams['authorized'] + $defaultParams['timeout'] + $defaultParams['error'];

        $this->assertEquals($defaultParams, $content);
    }

    public function testTimeoutOldPaymentAndVerify()
    {
        $payment = $this->fixtures->create('payment:status_created', ['created_at' => time() - 60*100, 'method'=>'netbanking']);

        $filter = 'payments_failed';

        $request = [
            'url'    => '/payments/verify/'. $filter,
            'method' => 'post'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'success' => 0,
            'filter'  => $filter,
        ];

        $this->assertContent($content, $resultData);

        $content = $this->timeoutOldPayment();

        $this->assertEquals($content['count'], 1);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'success' => 1,
            'filter'  => $filter,
        ];

        $this->assertContent($content, $resultData);
    }
}
