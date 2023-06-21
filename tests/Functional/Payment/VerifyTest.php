<?php

namespace RZP\Tests\Functional\Payment;

use DB;
use Carbon\Carbon;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\MocksRedisTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentVerifyTrait;
use Razorpay\IFSC\Bank;

class VerifyTest extends TestCase
{
    use PaymentTrait;
    use MocksRedisTrait;
    use DbEntityFetchTrait;
    use PaymentVerifyTrait;

    protected $payment = null;

    protected function setUp(): void
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
        $createdAt = Carbon::now()->subMinutes(3)->getTimestamp();

        $this->ba->cronAuth();

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
            'status'        => 'captured',
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
            'status'        => 'captured',
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
            'status'        => 'captured',
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
            'status'        => 'captured',
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
            'status'        => 'captured',
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
            'status'        => 'captured',
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
        $this->markTestSkipped("The payments/verify/{filter} route is not live in production");

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
        $this->markTestSkipped("The payments/verify/{filter} route is not live in production");

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

        $payment = $this->getDefaultNetbankingPaymentArray(Bank::UBIN);

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

        $payment = $this->getDefaultNetbankingPaymentArray(Bank::UBIN);

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

        $payment = $this->getDefaultNetbankingPaymentArray(Bank::UBIN);

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

    public function testVerifyGooglePayCardPayments()
    {
        $payment = $this->fixtures->create('payment', [
            'authentication_gateway' => 'google_pay',
            'status' => Payment\Status::AUTHORIZED
        ]);

        $this->ba->expressAuth();

        $request = array(
            'url'     => '/gateway/google_pay/verify',
            'method'  => 'post',
            'content' => [
                'pgTransactionRefId' => $payment['id']
            ],
        );

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response['status'], 'success');
        $this->assertEquals($response['error']['reason_code'], '');
    }

    public function testVerifyGooglePayCardFailedPayments()
    {
        $payment = $this->fixtures->create('payment', [
            'authentication_gateway' => 'google_pay',
            'status' => Payment\Status::FAILED,
            'reference13' => 'PRAZR004'
        ]);

        $this->ba->expressAuth();

        $request = array(
            'url'     => '/gateway/google_pay/verify',
            'method'  => 'post',
            'content' => [
                'pgTransactionRefId' => $payment['id']
            ],
        );

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response['status'], 'failed');
        $this->assertEquals($response['error']['reason_code'], 'PRAZR004');
    }

    public function testVerifyGooglePayCardPaymentNotFound()
    {
        $payment = $this->fixtures->create('payment', [
            'authentication_gateway' => 'google_pay',
            'status' => Payment\Status::AUTHORIZED
        ]);

        $this->ba->expressAuth();

        $request = array(
            'url'     => '/gateway/google_pay/verify',
            'method'  => 'post',
            'content' => [
                'pgTransactionRefId' => '10000000000000',
            ],
        );

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($request)
            {
                $this->makeRequestAndGetContent($request);
            }
        );
    }


    public function testVerifyGooglePayCardPaymentNotOfGooglePay()
    {
        $payment = $this->fixtures->create('payment', ['status' => Payment\Status::AUTHORIZED]);

        $this->ba->expressAuth();

        $request = array(
            'url'     => '/gateway/google_pay/verify',
            'method'  => 'post',
            'content' => [
                'pgTransactionRefId' => $payment['id'],
            ],
        );

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow(
            $data,
            function() use ($request)
            {
                $this->makeRequestAndGetContent($request);
            }
        );
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

        $payment = $this->getDefaultNetbankingPaymentArray(Bank::UBIN);

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

        $payment = $this->getDefaultNetbankingPaymentArray(Bank::UBIN);

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
        $this->markTestSkipped("The payments/verify/{filter} route is not live in production");

        $filter = 'payments_failed';

        $data = $this->testData['testTimeoutPaymentVerify'];

        $this->getErrorInCallback();

        $payment = $this->getDefaultNetbankingPaymentArray(Bank::UBIN);

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
        $this->markTestSkipped("The payments/verify/{filter} route is not live in production");

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

    public function testTimeoutUpiPaymentIciciVerifyAndBlockGateway()
    {
        // set the terminal to upi icici and enable upi for the merchant
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_icici_terminal');

        $this->gateway = 'upi_icici';

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $now  = Carbon::now();

        Carbon::setTestNow(Carbon::parse('15 minutes ago'));

        // do payment transaction and fail it so that it can be picked during verify.
        $this->doAuthPaymentViaAjaxRoute(array_except($this->payment, 'description'));

        $payment = $this->getDbLastPayment();
        $upi     = $this->getDbLastEntity('upi');

        $this->assertSame('created', $payment->getStatus());
        $this->assertSame('92', $upi->status_code);

        Carbon::setTestNow($now);

        $this->timeoutOldPayment();

        $payment->reload();
        $upi->reload();

        $this->assertSame('failed', $payment->getStatus());
        $this->assertSame('92', $upi->status_code);

        $this->resetMockServer();

        $this->getTimeoutInVerify();

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/payments_failed',
            'method' => 'post'
        ];
        // redis increment should be done once for upi icici

        $this->makeRequestAndGetContent($request);

        Carbon::setTestNow();
    }

    public function testLateAuthMerchantDefaultConfigVerifyPayment()
    {
        $this->setMockGatewayTrue();

        $this->fixtures->merchant->addFeatures(['disable_amount_check','excess_order_amount']);

        $this->fixtures->create('config', ['type' => 'late_auth', 'is_default' => true,
            'config'     => '{
                "capture": "automatic",
                "capture_options": {
                    "manual_expiry_period": 20,
                    "automatic_expiry_period": 13,
                    "refund_speed": "normal"
                }
            }']);

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

    public function testLateAuthMerchantOrderConfigVerifyPaymentBeforeTimeout()
    {
        $this->setMockGatewayTrue();

        $this->fixtures->merchant->addFeatures(['disable_amount_check']);

        $data = $this->testData['testTimeoutPaymentVerify'];

        $configArr = [
                "capture"=> 'automatic',
                "capture_options"=> [
                    "manual_expiry_period"=> 20,
                    "automatic_expiry_period"=> 13,
                    "refund_speed"=> "normal"
                ]
        ];

        $payments = $this->createFailedPaymentWithOrderWithConfig($data, $configArr);

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

        $order = $this->getDbLastEntityPublic('order');

        $this->assertEquals('paid', $order['status']);
    }

    public function testLateAuthMerchantOrderConfigVerifyPaymentAfterTimeout()
    {
        $this->setMockGatewayTrue();

        $this->fixtures->merchant->addFeatures(['disable_amount_check']);

        $data = $this->testData['testTimeoutPaymentVerify'];

        $configArr = [
            "capture"=> 'automatic',
            "capture_options"=> [
                "manual_expiry_period"=> 14,
                "automatic_expiry_period"=> 13,
                "refund_speed"=> "normal"
            ]
        ];

        $payments = $this->createFailedPaymentWithOrderWithConfig($data, $configArr);

        $payment = $this->getEntityById('payment', $payments[0], true);

        $verifyAt = Carbon::now(Timezone::IST)->addMinutes(11)->getTimestamp();

        $this->fixtures->edit('payment', $payment['id'], ['verify_at' => $verifyAt]);

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/all',
            'method' => 'post'
        ];

        $time = Carbon::now(Timezone::IST);

        $time->addMinutes(15);

        Carbon::setTestNow($time);

        $this->makeRequestAndGetContent($request);

        $payment = $this->getEntityById('payment', $payments[0], true);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals($payment['refund_at'], Carbon::createFromTimestamp($payment['created_at'])->addMinutes(14)->getTimestamp());

        $order = $this->getDbLastEntityPublic('order');

        $this->assertEquals('attempted', $order['status']);
    }

    public function testLateAuthGooglePayPaymentVerifyAfterTimeout()
    {
        $this->setMockGatewayTrue();

        $data = $this->testData['testTimeoutPaymentVerify'];

        $configArr = [
            "capture"=> 'automatic',
            "capture_options"=> [
                "manual_expiry_period"=> 14,
                "automatic_expiry_period"=> 13,
                "refund_speed"=> "normal"
            ]
        ];

        $payments = $this->createFailedPaymentWithOrderWithConfig($data, $configArr);

        $verifyAt = Carbon::now(Timezone::IST)->addMinutes(11)->getTimestamp();

        // Changing payment to GooglePay payment, by setting method and authentication_gateway
        $this->fixtures->edit('payment', $payments[0], [
            'verify_at' => $verifyAt,
            'method' => Payment\Method::UNSELECTED,
            'authentication_gateway' => Payment\Entity::GOOGLE_PAY
        ]);

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/new_cron',
            'method' => 'post'
        ];

        $time = Carbon::now(Timezone::IST);

        $time->addMinutes(15);

        Carbon::setTestNow($time);

        $this->makeRequestAndGetContent($request);

        $payment = $this->getEntityById('payment', $payments[0], true);

        // Payment is authorized in verify call
        $this->assertEquals('authorized', $payment['status']);

        // Payment method has changed to UPI in successful authorization
        $this->assertEquals(Payment\Method::UPI, $payment['method']);

        $order = $this->getDbLastEntityPublic('order');

        $this->assertEquals('attempted', $order['status']);
    }

    public function testLateAuthGooglePayPaymentVerifyFailed()
    {
        $this->setMockGatewayTrue();

        $data = $this->testData['testTimeoutPaymentVerify'];

        $configArr = [
            "capture"=> 'automatic',
            "capture_options"=> [
                "manual_expiry_period"=> 14,
                "automatic_expiry_period"=> 13,
                "refund_speed"=> "normal"
            ]
        ];

        $payments = $this->createFailedPaymentWithOrderWithConfig($data, $configArr);

        $this->mockVerifyFailed();

        $verifyAt = Carbon::now(Timezone::IST)->addMinutes(11)->getTimestamp();

        // Changing payment to GooglePay payment, by setting method and authentication_gateway
        $this->fixtures->edit('payment', $payments[0], [
            'verify_at' => $verifyAt,
            'method' => Payment\Method::UNSELECTED,
            'authentication_gateway' => Payment\Entity::GOOGLE_PAY
        ]);

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/new_cron',
            'method' => 'post'
        ];

        $time = Carbon::now(Timezone::IST);

        $time->addMinutes(15);

        Carbon::setTestNow($time);

        $this->makeRequestAndGetContent($request);

        $payment = $this->getEntityById('payment', $payments[0], true);

        $this->assertEquals(Payment\Status::FAILED, $payment['status']);

        // Payment method has not changed because no authorization happened.
        $this->assertEquals(Payment\Method::UNSELECTED, $payment['method']);
    }

    public function testVerifyBlockGatewayRoute()
    {
        $this->ba->appAuth('rzp_test', \Config::get('applications.admin_dashboard')['secret']);

        $request = [
            'url'    => '/payments/verify/disabled/gateway',
            'method' => 'post',
            'content' => ["gateways" => ["upi_icici"],
                "ttl" => 45]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response);
    }

    public function testUpdatePaymentReference6ToNull()
    {
        $this->ba->appAuth('rzp_test', 'RANDOM_DASH_PASSWORD');

        $payment = $this->fixtures->create(
            'payment:netbanking_failed', ['reference6' => 1]);

        $request = [
            'url'    => '/payments/'.$payment->id.'/updateReference6',
            'method' => 'patch',
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response['reference6_updated']);
    }

    public function testUpdatePaymentReference6ToNullForWrongId()
    {
        $this->ba->appAuth('rzp_test', 'RANDOM_DASH_PASSWORD');

        $request = [
            'url'    => '/payments/123456789asdfg/updateReference6',
            'method' => 'patch',
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(false, $response['reference6_updated']);
    }

    public function testUpdateB2BInvoiceDetailsToPaymentInReference2()
    {
        $payment = $this->fixtures->create('payment:authorized', [
            'method' => 'intl_bank_transfer',
            'gateway' => 'currency_cloud',
        ]);

        $merchantID = $payment->merchant->getId();
        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantID);

        $this->ba->proxyAuth('rzp_test_' . $merchantID , $merchantUser['id']);

        $request = [
            'url'    => '/payment/'.$payment->getPublicId().'/update_b2b_invoice_details',
            'method' => 'patch',
            'content' => [
                'document_id' => "doc_1234567890"
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(true, $response['b2b_invoice_updated']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals("doc_1234567890",$payment['reference2']);
    }

    public function testCbWorkflowCallback_WorkflowApproved()
    {
        $payment = $this->fixtures->create('payment:authorized', [
            'method' => 'intl_bank_transfer',
            'gateway' => 'currency_cloud',
        ]);

        $merchantID = $payment->merchant->getId();
        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantID);

        $this->ba->workflowsAppAuth();
        s($payment->merchant->isFeatureEnabled(Feature\Constants::ENABLE_SETTLEMENT_FOR_B2B));

        $request = [
            'url'    => '/internal/cb-invoice-workflow/callback',
            'method' => 'post',
            'content' => [
                'payment_id' => $payment->getId(),
                'merchant_id' => $merchantID,
                'workflow_status' => 'approved',
                'priority' => 'P0'
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);
        s($response);
        $merchant = $this->getDbEntityById('merchant', $merchantID);
        $this->assertEquals(true, $merchant->isFeatureEnabled(Feature\Constants::ENABLE_SETTLEMENT_FOR_B2B));
    }

    public function testCbWorkflowCallback_WorkflowRejected()
    {
        $payment = $this->fixtures->create('payment:authorized', [
            'method' => 'intl_bank_transfer',
            'gateway' => 'currency_cloud',
        ]);

        $merchantID = $payment->merchant->getId();
        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantID);

        $this->ba->workflowsAppAuth();

        $request = [
            'url'    => '/internal/cb-invoice-workflow/callback',
            'method' => 'post',
            'content' => [
                'payment_id' => $payment->getId(),
                'merchant_id' => $merchantID,
                'workflow_status' => 'rejected',
                'priority' => 'P0'
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);
        $merchant = $this->getDbEntityById('merchant', $merchantID);
        $this->assertEquals(false, $merchant->isFeatureEnabled(Feature\Constants::ENABLE_SETTLEMENT_FOR_B2B));
    }
}
