<?php

namespace RZP\Tests\Functional\Payment;

use DB;
use Redis;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class VerifyTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

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

        $createdAt = Carbon::now()->subMinutes(3)->getTimestamp();

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

        $createdAt = Carbon::now()->subMinutes(3)->getTimestamp();

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

        $createdAt = Carbon::now()->subMinutes(3)->getTimestamp();

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $this->runVerifyForMaxPeriod();
    }

    public function testVerifyMultipleFailedPaymentsByVerifyAt()
    {
        // These tests are always so much easier to debug
        // if they aren't affected by the current time.
        $time = Carbon::create(2018, 1, 1, 12, 0, 0, Timezone::IST);
        // It's 12:00pm now
        Carbon::setTestNow($time);

        $this->setupRedisMock();

        // A payment created at 11.57am, verify_at 12.07pm
        $createdAt = Carbon::now()->subMinutes(3);
        $verifyAt = $createdAt->addMinutes(10);
        $this->fixtures->create('payment:netbanking_failed', [
            'id'         => 'verifyPayment1',
            'created_at' => $createdAt->getTimestamp(),
            'verify_at'  => $verifyAt->getTimestamp(),
        ]);

        // A payment created at 11.56pm, verify_at 12.06pm
        $createdAt = Carbon::now()->subMinutes(4);
        $verifyAt = $createdAt->addMinutes(10);
        $this->fixtures->create('payment:netbanking_failed', [
            'id'         => 'verifyPayment2',
            'created_at' => $createdAt->getTimestamp(),
            'verify_at'  => $verifyAt->getTimestamp(),
        ]);

        // A third payment was created in `setUp`, it's outside our verify window

        // It's 12:14pm now
        $time = Carbon::create(2018, 1, 1, 12, 14, 0, Timezone::IST);
        Carbon::setTestNow($time);

        // Verify is called with delay=300. This means payments with
        // verify_at between now-300 and now-3*300 will be considered.
        // For this test, that's 11:59pm to 12:09pm, so both payments.
        $this->startTest();
    }

    public function testIciciBqrVerify()
    {
        $createdAt = Carbon::now()->getTimestamp() - 180;

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal', [
            'id'                   => 'AqdfGh5460opVt',
            'merchant_id'          => '10000000000000',
            'gateway'              => 'upi_icici',
            'gateway_merchant_id'  => '250000002',
            'gateway_merchant_id2' => 'abc@icici',
            'enabled'              => 1,
        ]);

        $payment = $this->fixtures->create('payment', [
            'method'        => 'upi',
            'gateway'       => 'upi_icici',
            'otp_attempts'  => 0,
            'terminal_id'   => 'AqdfGh5460opVt',
            'created_at'    => $createdAt,
            'authorized_at' => $createdAt,
            'verify_at'     => $createdAt,
            'captured_at'   => $createdAt,
            'amount'        => 100,
            'status'        => 'authorized',
            'receiver_type' => 'qr_code',
        ]);

        $this->fixtures->create('upi', [
            'id'            => 1,
            'payment_id'    => $payment->getId(),
            'amount'        => 100,
            'gateway'       => 'upi_icici',
            'action'        => 'authorize',
        ]);

        $this->startTest();
    }


    public function testNotVerifiablePaymentForCaptureVerify()
    {
        $createdAt = Carbon::now()->getTimestamp() - 173000; // more than 2 days old timestamp

        $verifyAt = Carbon::now()->getTimestamp() - 180;

        $this->setMockGatewayTrue();

        $this->fixtures->create(
            'terminal', [
                'id'                   => 'AqdfGh5460opVt',
                'merchant_id'          => '10000000000000',
                'gateway'              => 'hitachi',
                'gateway_merchant_id'  => '250000002',
                'gateway_merchant_id2' => 'abc@icici',
                'enabled'              => 1,
            ]);

        $card = $this->fixtures->create('card' , ['network'=>'RuPay']);

        // Not applicable as hitachi[rupay] payment and more than 2 days old
        $this->fixtures->create('payment', [
            'method'        => 'card',
            'gateway'       => 'hitachi',
            'otp_attempts'  => 0,
            'terminal_id'   => 'AqdfGh5460opVt',
            'created_at'    => $createdAt,
            'authorized_at' => $createdAt,
            'verify_at'     => $verifyAt,
            'captured_at'   => $createdAt,
            'card_id'       => $card->getId(),
            'amount'        => 100,
            'status'        => 'authorized',
        ]);

        // Not applicable since reconciled
        $payment = $this->fixtures->create('payment', [
            'method'        => 'card',
            'gateway'       => 'hitachi',
            'otp_attempts'  => 0,
            'terminal_id'   => 'AqdfGh5460opVt',
            'created_at'    => $createdAt,
            'authorized_at' => $verifyAt,
            'verify_at'     => $verifyAt,
            'captured_at'   => $createdAt,
            'card_id'       => $card->getId(),
            'amount'        => 1000,
            'status'        => 'authorized',
        ]);

        $transaction = $this->fixtures->create('transaction', [
            'entity_id' => $payment->getId(), 'merchant_id' => '10000000000000',
            'reconciled_at' => Carbon::now()->getTimestamp()]);

        $this->fixtures->edit('payment',$payment->getId(), ['transaction_id'=> $transaction->getId()]);

        $this->startTest();
    }

    public function testIsgBqrVerify()
    {
        $createdAt = Carbon::now()->getTimestamp() - 180;

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal', [
            'id'                   => 'AqdfGh5460opVt',
            'merchant_id'          => '10000000000000',
            'gateway'              => 'upi_icici',
            'gateway_merchant_id'  => '250000002',
            'gateway_terminal_id'  => '12345678',
            'enabled'              => 1,
        ]);

        $payment = $this->fixtures->create('payment', [
            'method'        => 'upi',
            'gateway'       => 'isg',
            'otp_attempts'  => 0,
            'terminal_id'   => 'AqdfGh5460opVt',
            'created_at'    => $createdAt,
            'authorized_at' => $createdAt,
            'verify_at'     => $createdAt,
            'captured_at'   => $createdAt,
            'amount'        => 100,
            'status'        => 'authorized',
            'receiver_type' => 'qr_code',
        ]);

        $this->fixtures->create('isg', [
            'id'                    => 1,
            'payment_id'            => $payment->getId(),
            'amount'                => 100,
            'action'                => 'authorize',
            'merchant_pan'          => 'test_pan',
            'merchant_reference'    =>'testRef',
            'bank_reference_no'     => '1234abc',
            'transaction_date_time' => '2019-05-16 08:19:38',
        ]);

        $this->startTest();
    }

    public function testAmexVerify()
    {
        $createdAt = Carbon::now()->getTimestamp() - 180;

        $this->setMockGatewayTrue();

        $card = $this->fixtures->create('card', ['name' => 'Test Name']);

        $this->fixtures->create(
            'terminal',
            [
                'id'                    => 'AqdfGh5460opVt',
                'merchant_id'           => '10000000000000',
                'gateway'               => 'amex',
                'gateway_merchant_id'   => '250000002',
                'gateway_secure_secret' => 'abckjicici',
                'gateway_access_code'   => 'abcdef',
                'gateway_terminal_id'   => '12345',
                'enabled'               => 1,
            ]);

        $payment = $this->fixtures->create('payment', [
            'method'        => 'card',
            'gateway'       => 'amex',
            'otp_attempts'  => 0,
            'terminal_id'   => 'AqdfGh5460opVt',
            'card_id'       => $card->getId(),
            'created_at'    => $createdAt,
            'authorized_at' => $createdAt,
            'verify_at'     => $createdAt,
            'captured_at'   => $createdAt,
            'amount'        => 100,
            'status'        => 'authorized',
        ]);

        $this->fixtures->create('axis_migs', [
            'id'                  => 1,
            'payment_id'          => $payment->getId(),
            'vpc_amount'          => 100,
            'vpc_command'         => 'pay',
            'vpc_MerchTxnRef'     => $payment->getId(),
            'vpc_TxnResponseCode' => 0,
            'amex'                => true,
            'action'              => 'authorize',
        ]);

        $this->startTest();
    }

    public function testHitachiUpiVerifyShdFail()
    {
        $createdAt = Carbon::now()->getTimestamp() - 180;

        $this->setMockGatewayTrue();

        $this->fixtures->create(
            'terminal',
            [
                'id'                   => 'AqdfGh5460opVt',
                'merchant_id'          => '10000000000000',
                'gateway'              => 'hitachi',
                'gateway_merchant_id'  => '250000002',
                'gateway_merchant_id2' => 'abc@icici',
                'enabled'              => 1,
            ]);

        $payment = $this->fixtures->create('payment', [
            'method'        => 'upi',
            'gateway'       => 'hitachi',
            'otp_attempts'  => 0,
            'terminal_id'   => 'AqdfGh5460opVt',
            'created_at'    => $createdAt,
            'authorized_at' => $createdAt,
            'verify_at'     => $createdAt,
            'captured_at'   => $createdAt,
            'amount'        => 100,
            'status'        => 'authorized',
            'receiver_type' => 'qr_code',

        ]);

        $this->fixtures->create('upi',
            [
                'id'                => 1,
                'payment_id'        => $payment->getId(),
                'amount'            => 100,
                'gateway'           => 'hitachi',
                'action'            => 'authorize',
            ]);

        $this->startTest();
    }

    public function testVerifyMultipleFailedPayments()
    {
        $this->setupRedisMock();

        $createdAt = Carbon::now()->subMinutes(3)->getTimestamp();

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $createdAt = Carbon::now()->subMinutes(4)->getTimestamp();

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

        $createdAt = Carbon::now()->subMinutes(3)->getTimestamp();

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

        $payment = $this->getDbLastEntityPublic('payment');

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
        $createdAt = Carbon::now()->subMinutes(3)->getTimestamp();

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['created_at' => $createdAt]);

        $createdAt = Carbon::now()->subMinutes(4)->getTimestamp();

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
        $createdAt = Carbon::now()->subMinutes(3)->getTimestamp();

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

        $createdAt = Carbon::now()->subMinutes(3)->getTimestamp();

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

        $payment = $this->getDbLastEntityPublic('payment');

        $this->resetMockServer();

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/payments_failed',
            'method' => 'post'
        ];

        $time = Carbon::now(Timezone::IST);

        Carbon::setTestNow($time->addMinutes(5));

        $content = $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastEntityPublic('payment');

        $resultData = ['filter' => 'payments_failed', 'success' => 0, 'authorized' => 1];

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

        $payment = $this->getDbLastEntityPublic('payment');

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

        $payment = $this->getDbLastEntityPublic('payment');

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

        $payment = $this->getDbLastEntityPublic('payment');

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

        $payment = $this->getDbLastEntityPublic('payment');

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

        $payment = $this->getDbLastEntityPublic('payment');

        $this->assertEquals($payment['status'], 'authorized');

        Carbon::setTestNow();
    }

    public function testVerifyFailedWithZeroValidPayments()
    {
        $this->setupRedisMock();

        $createdAt = Carbon::now()->subHour()->getTimestamp();

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

        $createdAt = Carbon::now()->subHour()->getTimestamp();

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

        $createdAt = Carbon::now()->subMinutes(100)->getTimestamp();

        $payment = $this->fixtures->create('payment:status_created', [
            'created_at' => $createdAt,
            'method'=>'netbanking'
        ]);

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

        $payment = $this->getDbLastEntityPublic('payment');

        $prevBucket = $payment['verify_bucket'];

        $this->getVerificationSkipError();

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/' . $filter,
            'method' => 'post'
        ];

        $time = Carbon::now(Timezone::IST);

        $time->addMinutes(15);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastEntityPublic('payment');

        $newBucket = $payment['verify_bucket'];

        $this->assertEquals(9, $newBucket);

        $time = Carbon::now(Timezone::IST);

        $time->addMinutes(30);

        Carbon::setTestNow($time);

        $this->ba->cronAuth();

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

        $payment = $this->getDbLastEntityPublic('payment');

        $prevBucket = $payment['verify_bucket'];

        $this->getVerificationRetryError();

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/' . $filter,
            'method' => 'post'
        ];

        $time = Carbon::now(Timezone::IST);

        $time->addMinutes(15);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $payment = $this->getDbLastEntityPublic('payment');

        $newBucket = $payment['verify_bucket'];

        $this->assertEquals(0, $newBucket);

        $time = Carbon::now(Timezone::IST);

        $time->addMinutes(30);

        Carbon::setTestNow($time);

        $this->ba->cronAuth();

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

        $payment = $this->getDbLastEntityPublic('payment');

        $prevBucket = $payment['verify_bucket'];

        $this->getVerificationBlockError();

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/' . $filter,
            'method' => 'post'
        ];

        $time = Carbon::now(Timezone::IST);

        $time->addMinutes(14);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'not_applicable' => 1,
            'filter'         => $filter,
        ];

        $this->assertContent($content, $resultData);

        $payment = $this->getDbLastEntityPublic('payment');

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
            'not_applicable' => 1,
            'filter'         => 'payments_failed',
        ];

        $this->assertContent($content, $resultData);

        $payment = $this->createFailedPayment($data);

        $this->ba->cronAuth();

        $content = $this->makeRequestAndGetContent($request);

        $resultData = ['filter'  => 'payments_failed'];

        $this->assertContent($content, $resultData);

        $time->addMinutes(25);

        Carbon::setTestNow($time);

        $this->ba->cronAuth();

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'authorized' => 2,
            'filter'     => 'payments_failed',
        ];

        $this->assertContent($content, $resultData);

        Carbon::setTestNow();
    }

    public function testCaptureVerifyHoldPayment()
    {
        $this->markTestSkipped("Feature not enabled atm.");

        $this->setMockGatewayTrue();

        $this->fixtures->merchant->addFeatures(['payment_onhold']);

        $payment = $this->fixtures->create('payment:captured');

        $transaction = $payment->transaction;

        $this->assertFalse($payment->getOnHold());
        $this->assertFalse($transaction->getOnHold());

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/all',
            'method' => 'post'
        ];

        $time = Carbon::now(Timezone::IST);

        $time->addMinutes(5);

        Carbon::setTestNow($time);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content = [
                    'error_code_tag' => 'GW00201',
                    'error_service_tag' => 'null',
                    'result' => '!ERROR!-GW00201-Transaction not found.',
                ];
            }

            return $content;
        }, 'hdfc');

        $content = $this->makeRequestAndGetContent($request);

        $payment->reload();

        $transaction->reload();

        $this->assertTrue($payment->getOnHold());
        $this->assertTrue($transaction->getOnHold());

        // unset hold Payment
        $this->ba->adminAuth();

        $request = [
            'url' => '/payments/on_hold/bulk_update',
            'method' => 'POST',
            'content' => [
                'payment_ids' => [$payment->getPublicId()],
                'on_hold'     => 0,
            ],
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $payment->reload();

        $transaction->reload();

        $this->assertFalse($payment->getOnHold());
        $this->assertFalse($transaction->getOnHold());

    }

    public function testCaptureVerifyExcessOrderPayment()
    {
        $this->setMockGatewayTrue();

        $this->fixtures->merchant->edit(
            '10000000000000',
            [
                'auto_capture_late_auth' => true,
            ]);

        $this->fixtures->merchant->addFeatures(['disable_amount_check']);

        $data = $this->testData['testTimeoutPaymentVerify'];

        $payments = $this->createMultipleFailedPaymentWithOrder($data);

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/all',
            'method' => 'post'
        ];

        $time = Carbon::now(Timezone::IST);

        $time->addMinutes(5);

        Carbon::setTestNow($time);

        $this->makeRequestAndGetContent($request);

        $payment = $this->getEntityById('payment', $payments[0], true);

        $this->assertEquals('captured', $payment['status']);

        $payment = $this->getEntityById('payment', $payments[1], true);

        $this->assertEquals('captured', $payment['status']);

        $order = $this->getDbLastEntityPublic('order');

        $this->assertEquals('paid', $order['status']);
    }

    protected function setupRedisMock($paymentArray = [])
    {
        $redisMock = $this->getMockBuilder(Redis::class)->setMethods(['set', 'get', 'setex', 'client'])
                          ->getMock();

        Redis::shouldReceive('connection')
               ->andReturn($redisMock);

        Redis::shouldReceive('hGetAll')
            ->andReturn([]);

        Redis::shouldReceive('hDel')
            ->andReturn([]);

        Redis::shouldReceive('hSet')
            ->andReturn(null);

        Redis::shouldReceive('incr')
            ->andReturn(1);

         Redis::shouldReceive('expire')
            ->andReturn(true);

        $redisMock->method('set')->will($this->returnCallback(function ($resourceId, $requestId) use ($paymentArray)
        {
            foreach ($paymentArray as $payment)
            {
                if ('mutex:' . $payment['id'] . '_verify' === $resourceId)
                {
                        return null;
                }
            }
            return true;
        }));

        $store = \Cache::store();

        \Cache::shouldReceive('store')
                ->withAnyArgs()
                ->andReturn($store);


        \Cache::shouldReceive('get')
                ->andReturn([]);

        // Assertions for call to cache in getCachedTreatment() method.
        \Cache::shouldReceive('remember')
                ->zeroOrMoreTimes()
                ->andReturn("control");

        $redisMock->method('get')->will($this->returnValue(''));
    }

    protected function setupRedisMockForBlockedGateway($paymentArray = [])
    {
        $conn = Redis::connection();

        Redis::shouldReceive('connection')
             ->andReturnUsing(function() use($conn){
                return $conn;
             });

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
        $conn = Redis::connection();

        Redis::shouldReceive('connection')
             ->andReturnUsing(function() use($conn){
                return $conn;
             });

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

        foreach (range(0, 2) as $index)
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

        $minutesArray = [12, 30, 2*60, 6*60, 60*24, 2*60*24, 3*60*24, 4*60*24];

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

        $minutesArray = [0, 12, 30, 2*60, 6*60, 60*24, 2*60*24, 3*60*24, 4*60*24];

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
            'success'        => 0,
            'authorized'     => 0,
            'timeout'        => 0,
            'error'          => 0,
            'not_applicable' => 0,
            'unknown'        => 0,
            'bucket_filter'  => [],
            'request_error'  => 0
        ];

        $total = array_sum($defaultParams);

        $defaultParams = array_merge($defaultParams, $param);

        $defaultParams['verified_payments'] = $defaultParams['success'] +
            $defaultParams['authorized'] + $defaultParams['timeout'] +
            $defaultParams['error'] + $defaultParams['not_applicable'] + $defaultParams['unknown'];

        if ($defaultParams['not_applicable'] === 0)
        {
            unset($defaultParams['not_applicable']);
        }

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

        $payment = $this->getDbLastEntityPublic('payment');

        $this->resetMockServer();

        return $payment;
    }

    protected function createMultipleFailedPaymentWithOrder($data)
    {
        $payments = [];

        $this->getErrorInCallback();

        $order = $this->fixtures->create('order', ['id' => '100000000order', 'amount' => 50000, 'payment_capture' => true]);

        $this->assertEquals('created', $order['status']);

        $payment = $this->getDefaultNetbankingPaymentArray('ANDB');

        $payment["order_id"] = 'order_' . $order["id"];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthAndCapturePayment($payment);
            }
        );
        $paymentdata = $this->getDbLastEntityPublic('payment');

        $payments[] = $paymentdata['id'];


        $this->runRequestResponseFlow(
            $data,
            function() use ($payment)
            {
                $this->doAuthAndCapturePayment($payment);
            }
        );

        $paymentdata = $this->getDbLastEntityPublic('payment');

        $payments[] = $paymentdata['id'];

        $this->resetMockServer();

        return $payments;

    }
}
