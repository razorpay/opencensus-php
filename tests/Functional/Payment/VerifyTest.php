<?php

namespace RZP\Tests\Functional\Payment;

use DB;
use Mockery;
use Redis;
use Carbon\Carbon;
use RZP\Constants\Timezone;
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

        $this->gateway = 'ebs';

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_ebs_terminal');
    }

    public function testNonCronCaller()
    {
        $this->setupRedisMock();

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
        $this->setupRedisMock();

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
        $this->setupRedisMock();

        $createdAt = time() - 180;

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $this->runVerifyForMaxPeriod();
    }

    public function testVerifyMultipleFailedPayments()
    {
        $this->setupRedisMock();

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

    public function testVerifySingleFailedPaymentsWithBucketFilter()
    {
        $this->setupRedisMock();

        $createdAt = time() - 180;

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $filter = 'payments_failed';

        $time = Carbon::now(Timezone::IST);

        $request = [
            'url'    => '/payments/verify/'. $filter,
            'method' => 'post'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'success' => 1,
            'filter'  => $filter,
        ];

        $this->assertContent($content, $resultData);

        $time->addMinutes(15);

        Carbon::setTestNow($time);

        $payment = $this->getLastEntity('payment', true);

        $request = [
            'url'     => '/payments/verify/'. $filter,
            'method'  => 'post',
            'content' => ['bucket'=> [$payment['verify_bucket'] - 1]]
        ];

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'success'       => 0,
            'filter'        => $filter,
            'bucket_filter' => [$payment['verify_bucket'] - 1]
        ];

        $this->assertContent($content, $resultData);

        $request = [
            'url'     => '/payments/verify/'. $filter,
            'method'  => 'post',
            'content' => ['bucket'=> [$payment['verify_bucket'] + 1]]
        ];

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'success' => 0,
            'filter'  => $filter,
            'bucket_filter' => [$payment['verify_bucket'] + 1]
        ];

        $this->assertContent($content, $resultData);

        $request = [
            'url'     => '/payments/verify/'. $filter,
            'method'  => 'post',
            'content' => ['bucket'=> [$payment['verify_bucket']] ]
        ];

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'success' => 1,
            'filter'  => $filter,
            'bucket_filter' => [$payment['verify_bucket']]
        ];

        $this->assertContent($content, $resultData);

        Carbon::setTestNow();
    }

    public function testVerifyWithLockedPayments()
    {
        $createdAt = time() - 180;

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $createdAt = time() - 240;

        $payment2 = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $this->setupRedisMock([$payment2]);

        // Lock payment for 10 days, No verify should run on this payment
        $this->app['api.mutex']->acquire($payment2['id'].'_verify', 864000);

        $result = [
            'filter'  => 'payments_failed',
            'all'     => 1,
            'none'    => 0,
        ];

        $this->runVerifyForMaxPeriod($result);
    }

    public function testVerifyWithLockedPaymentsExceedingThreshold()
    {
        $createdAt = time() - 180;

        $payment = $this->fixtures->times(102)->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $result = [
            'filter'  => 'payments_failed',
            'all'     => 100,
            'none'    => 0,
        ];

        $this->setupRedisMock([$payment[0], $payment[101]]);

        $this->app['api.mutex']->acquire($payment[0]['id'].'_verify', 864000);

        $this->app['api.mutex']->acquire($payment[101]['id'].'_verify', 864000);

        $this->runVerifyForMaxPeriod($result);
    }

    public function testNewlyCreatedPayment()
    {
        $this->setupRedisMock();

        $createdAt = Carbon::now()->getTimestamp();

        $payment = $this->fixtures->create(
            'payment:netbanking_created', ['created_at' => $createdAt-1]);

        $verifiedResultArray = [
            'filter'  => 'payments_created',
            'all'     => 1,
            'none'    => 0,
        ];

        $filter = $verifiedResultArray['filter'];

        $time = Carbon::now(Timezone::IST);

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

        $time = Carbon::now(Timezone::IST);

        Carbon::setTestNow($time->addSeconds(180));

        $this->runCreateVerify();
    }

    public function testVerifySingleCreatedPayments()
    {
        $this->setupRedisMock();

        Carbon::setTestNow();

        $createdAt = time() - 180;

        $payment = $this->fixtures->create(
            'payment:netbanking_created', ['created_at' => $createdAt]);

        $this->runCreateVerify();
    }

    public function testTimeoutPaymentVerifyFailure()
    {
        $this->setupRedisMock();

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

        $time = Carbon::now(Timezone::IST);

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

    public function testErrorPaymentVerify()
    {
        $this->setupRedisMock();

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

        $prevBucket = $payment['verify_bucket'];

        $this->getFatalErrorInVerify();

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/payments_failed',
            'method' => 'post'
        ];

        $time = Carbon::now(Timezone::IST);

        $time->addMinutes(15);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $payment = $this->getLastEntity('payment', true);

        $newBucket = $payment['verify_bucket'];

        $this->assertNotEquals($prevBucket, $newBucket);
        $request = [
            'url'    => '/payments/verify/verify_error',
            'method' => 'post'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'authorized' => 0,
            'error'      => 1,
            'filter'     => 'verify_error'
        ];

        $this->assertContent($content, $resultData);

        $this->resetMockServer();

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'authorized' => 1,
            'filter'     => 'verify_error'
        ];

        $this->assertContent($content, $resultData);
    }

    public function testTimeoutPaymentVerify()
    {
        $this->setupRedisMock();

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

        $time = Carbon::now(Timezone::IST);

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

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['verify_bucket'], 0);

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

    public function testVerifyFailedWithZeroValidPayments()
    {
        $this->setupRedisMock();

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

        Carbon::setTestNow();
    }

    public function testInvalidFilter()
    {
        $this->setupRedisMock();

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

    public function testTimeoutOldPaymentAndVerify()
    {
        $this->setupRedisMock();

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

    public function testPaymentVerifySkip()
    {
        $this->setupRedisMock();

        $filter = 'payments_failed';

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

        $prevBucket = $payment['verify_bucket'];

        $this->getVerificationSkipError();

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/' . $filter,
            'method' => 'post'
        ];

        $time = Carbon::now('Asia/Kolkata');

        $time->addMinutes(15);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $payment = $this->getLastEntity('payment', true);

        $newBucket = $payment['verify_bucket'];

        $this->assertEquals(9, $newBucket);

        $time = Carbon::now('Asia/Kolkata');

        $time->addMinutes(30);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'success' => 0,
            'filter'  => $filter,
        ];

        $this->assertContent($content, $resultData);

        Carbon::setTestNow();
    }

    public function testPaymentVerifyRetry()
    {
        $this->setupRedisMock();

        $filter = 'payments_failed';

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

        $prevBucket = $payment['verify_bucket'];

        $this->getVerificationRetryError();

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/' . $filter,
            'method' => 'post'
        ];

        $time = Carbon::now('Asia/Kolkata');

        $time->addMinutes(15);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $payment = $this->getLastEntity('payment', true);

        $newBucket = $payment['verify_bucket'];

        $this->assertEquals(0, $newBucket);

        $time = Carbon::now('Asia/Kolkata');

        $time->addMinutes(30);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'error' => 1,
            'filter'  => $filter,
        ];

        $this->assertContent($content, $resultData);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'error' => 1,
            'filter'  => $filter,
        ];

        $this->assertContent($content, $resultData);

        Carbon::setTestNow();
    }

    public function testPaymentVerifyBlock()
    {
        $this->setupRedisMockForBlockedPayments();

        $filter = 'payments_failed';

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

        $prevBucket = $payment['verify_bucket'];

        $this->getVerificationBlockError();

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/' . $filter,
            'method' => 'post'
        ];

        $time = Carbon::now('Asia/Kolkata');

        $time->addMinutes(14);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'error' => 1,
            'filter'  => $filter,
        ];

        $this->assertContent($content, $resultData);

        $payment = $this->getLastEntity('payment', true);

        $newBucket = $payment['verify_bucket'];

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'filter'  => $filter,
        ];

        $this->assertContent($content, $resultData);

        Carbon::setTestNow();
    }

    public function testTimeoutPaymentVerifyAndBlockGateway()
    {
        $this->setupRedisMockForBlockedGateway();

        $data = $this->testData['testTimeoutPaymentVerify'];

        $payment = $this->createFailedPayment($data);

        $this->getTimeoutInVerify();

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/payments_failed',
            'method' => 'post'
        ];

        $time = Carbon::now(Timezone::IST);

        $time->addMinutes(14);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'timeout' => 1,
            'filter'  => 'payments_failed',
        ];

        $this->assertContent($content, $resultData);

        $payment = $this->createFailedPayment($data);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = ['filter'  => 'payments_failed'];

        $this->assertContent($content, $resultData);

        $time->addMinutes(25);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'authorized' => 2,
            'filter'     => 'payments_failed',
        ];

        $this->assertContent($content, $resultData);

        Carbon::setTestNow();
    }

    protected function setupRedisMock($paymentArray = [])
    {
        Redis::shouldReceive('hGetAll')
            ->andReturn([]);

        Redis::shouldReceive('hDel')
            ->andReturn([]);

        Redis::shouldReceive('hSet')
            ->andReturn(null);

        Redis::shouldReceive('incr')
            ->andReturn(1);

        Redis::shouldReceive('set')
            ->andReturnUsing(
                function ($arg) use ($paymentArray)
                {
                    foreach ($paymentArray as $payment)
                    {
                        if ($payment['id'] . '_verify' === $arg)
                        {
                            return null;
                        }
                    }
                    return true;
                });

        Redis::shouldReceive('get')
            ->andReturn(true);
    }

    protected function setupRedisMockForBlockedGateway($paymentArray = [])
    {
        Redis::shouldReceive('hGetAll')
            ->andReturn(
                [],
                [
                    'ebs'=> Carbon::now()->getTimestamp() + 900
                ]);
        Redis::shouldReceive('hDel')
            ->andReturn([]);

        Redis::shouldReceive('hSet')
            ->andReturn(null);

        Redis::shouldReceive('incr')
            ->andReturn(101);

        Redis::shouldReceive('set')
            ->andReturnUsing(
                function ($arg) use ($paymentArray)
                {
                    foreach ($paymentArray as $payment)
                    {
                        if ($payment['id'] . '_verify' === $arg)
                        {
                            return null;
                        }
                    }
                    return true;
                });

        Redis::shouldReceive('get')
            ->andReturn(true);
    }

    protected function setupRedisMockForBlockedPayments()
    {
        Redis::shouldReceive('hGetAll')
            ->andReturn(
                [],
                [
                    'ebs'=> Carbon::now()->getTimestamp() + 900
                ]);

        Redis::shouldReceive('hDel')
            ->andReturn([]);

        Redis::shouldReceive('hSet')
            ->andReturn(null);

        Redis::shouldReceive('set')
            ->andReturn(true);

        Redis::shouldReceive('get')
            ->andReturn(true);
    }

    protected function runCreateVerify()
    {
        $this->setupRedisMock();

        $verifiedResultArray = [
            'filter'  => 'payments_created',
            'all'     => 1,
            'none'    => 0,
        ];

        $filter = $verifiedResultArray['filter'];

        $time = Carbon::now(Timezone::IST);

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

        foreach (range(0, 3) as $index)
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

        $minutesArray = [15, 30, 60, 6*60, 60*24, 2*60*24, 3*60*24, 4*60*24];

        foreach ($minutesArray as $minutes)
        {
            Carbon::setTestNow();

            $time = Carbon::now(Timezone::IST);

            $time->addMinutes($minutes);

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

        Carbon::setTestNow();
    }

    protected function runVerifyForMaxPeriod($verifiedResultArray = null)
    {
        $this->setupRedisMock();

        if ($verifiedResultArray === null)
        {
            $verifiedResultArray = [
                'filter'  => 'payments_failed',
                'all'     => 1,
                'none'    => 0,
            ];
        }

        $filter = $verifiedResultArray['filter'];

        $request = [
            'url'    => '/payments/verify/'. $filter,
            'method' => 'post'
        ];

        $minutesArray = [0, 15, 30, 60, 6*60, 60*24, 2*60*24, 3*60*24, 4*60*24];

        foreach ($minutesArray as $minutes)
        {
            $time = Carbon::now(Timezone::IST);

            $time->addMinutes($minutes);

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

            Carbon::setTestNow();
        }

        $time->addDay(5);

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

        unset($content['fetch_time']);

        // TODO : built it from previous values
        unset($content['verifiable_count']);

        $defaultParams = [
            'success'       => 0,
            'authorized'    => 0,
            'timeout'       => 0,
            'error'         => 0,
            'bucket_filter' => [],
        ];

        $total = array_sum($defaultParams);

        $defaultParams = array_merge($defaultParams, $param);

        $defaultParams['verified_payments'] = $defaultParams['success'] +
            $defaultParams['authorized'] + $defaultParams['timeout'] + $defaultParams['error'];

        $this->assertEquals($defaultParams, $content);
    }

    protected function createFailedPayment($data)
    {

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

        return $payment;
    }
}
