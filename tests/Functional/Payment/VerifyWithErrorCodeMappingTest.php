<?php

namespace RZP\Tests\Functional\Payment;

use DB;
use Redis;
use Carbon\Carbon;
use RZP\Diag\Traits\PaymentEvent;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Verify\Action;
use RZP\Exception\BadRequestException;
use RZP\Exception\PaymentVerificationException;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentVerifyTrait;
use Razorpay\IFSC\Bank;

class VerifyWithErrorCodeMappingTest extends TestCase
{
    use PaymentTrait;
    use PaymentVerifyTrait;
    use DbEntityFetchTrait;
    use PaymentEvent;

    protected $payment = null;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/VerifyTestData.php';

        parent::setUp();

        $this->gateway = 'ebs';

        $this->ba->cronAuth();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_ebs_terminal');
    }

    //--------------- New Verify Cron tests -------------------------------//

    public function testPaymentVerifyWithFinalErrorCode()
    {
        $filter = 'new_cron';

        $expectedContent = [
            'authorized' => 0,
            'success' => 0,
            'timeout' => 0,
            'error' => 0,
            'unknown' => 0,
            'not_applicable' => 0,
            'locked_count' => 0,
            'request_error' => 0,
            'total_payments' => 0,
            'attempted_payments' => 0,
            'must_attempt' => 0,
        ];

        $this->authPaymentTimeout('netbanking', true);

        $this->mockServerContentFunction(function (& $content)
        {
            throw new PaymentVerificationException(
                ['test' => 'test'],
                null,
                Action::RETRY,
                ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED);
        }, 'ebs');

        $request = $this->verifyTestSetUp($filter, ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED, $expectedContent, null);

        $this->afterVerifyTest($filter, $request);
    }

    public function testPaymentVerifyWithTemporaryErrorCode()
    {
        $filter = 'new_cron';

        $expectedContent = [
            'authorized' => 0,
            'success' => 0,
            'timeout' => 0,
            'error' => 1,
            'unknown' => 0,
            'not_applicable' => 0,
            'locked_count' => 0,
            'request_error' => 0,
            'total_payments' => 1,
            'attempted_payments' => 1,
            'must_attempt' => 1,
        ];

        $this->authPaymentTimeout('netbanking');

        $this->mockServerContentFunction(function (& $content)
        {
            throw new PaymentVerificationException(
                ['test' => 'test'],
                null,
                Action::RETRY,
                ErrorCode::GATEWAY_ERROR_CALLBACK_EMPTY_INPUT);
        }, 'ebs');

        $request = $this->verifyTestSetUp($filter, '', $expectedContent, 0);

        $this->afterVerifyTest($filter, $request);
    }

    public function testPaymentVerifyWithInvalidErrorCode()
    {
        $filter = 'new_cron';

        $expectedContent = [
            'authorized' => 0,
            'success' => 0,
            'timeout' => 0,
            'error' => 1,
            'unknown' => 0,
            'not_applicable' => 0,
            'locked_count' => 0,
            'request_error' => 0,
            'total_payments' => 1,
            'attempted_payments' => 1,
            'must_attempt' => 1,
        ];

        $this->authPaymentTimeout('netbanking', false, true);

        $this->mockServerContentFunction(function (& $content)
        {
            throw new PaymentVerificationException(
                ['test' => 'test'],
                null,
                Action::RETRY,
                ErrorCode::GATEWAY_ERROR_CALLBACK_EMPTY_INPUT);
        }, 'ebs');

        $request = $this->verifyTestSetUp($filter, "404", $expectedContent, 0);

        $this->afterVerifyTest($filter, $request);
    }

    public function testPaymentVerifyRetryTest()
    {
        $filter = 'new_cron';

        $expectedContent = [
            'authorized' => 0,
            'success' => 0,
            'timeout' => 0,
            'error' => 1,
            'unknown' => 0,
            'not_applicable' => 0,
            'locked_count' => 0,
            'request_error' => 0,
            'total_payments' => 1,
            'attempted_payments' => 1,
            'must_attempt' => 1,
        ];

        $this->authPaymentTimeout('netbanking');

        $this->mockServerContentFunction(function (& $content)
        {
            throw new PaymentVerificationException(
                ['test' => 'test'],
                null,
                Action::RETRY);
        }, 'ebs');

        $request = $this->verifyTestSetUp($filter, '', $expectedContent, 0);

        $this->afterVerifyTest($filter, $request);
    }

    public function testPaymentVerifyBlockTest()
    {
        $filter = 'new_cron';

        $expectedContent = [
            'authorized' => 0,
            'success' => 0,
            'timeout' => 0,
            'error' => 1,
            'unknown' => 0,
            'not_applicable' => 0,
            'locked_count' => 0,
            'request_error' => 0,
            'total_payments' => 1,
            'attempted_payments' => 1,
            'must_attempt' => 1,
        ];

        $this->authPaymentTimeout('netbanking');

        $this->mockServerContentFunction(function (& $content)
        {
            throw new PaymentVerificationException(
                ['test' => 'test'],
                null,
                Action::BLOCK);
        }, 'ebs');

        $request = $this->verifyTestSetUp($filter, '', $expectedContent, 0);

        $this->afterVerifyTest($filter, $request);
    }

    public function testPaymentVerifyNullTest()
    {
        $filter = 'new_cron';

        $expectedContent = [
            'authorized' => 0,
            'success' => 0,
            'timeout' => 0,
            'error' => 1,
            'unknown' => 0,
            'not_applicable' => 0,
            'locked_count' => 0,
            'request_error' => 0,
            'total_payments' => 1,
            'attempted_payments' => 1,
            'must_attempt' => 1,
        ];

        $this->authPaymentTimeout('netbanking');

        $this->mockServerContentFunction(function (& $content)
        {
            throw new PaymentVerificationException(
                ['test' => 'test'],
                null,
                null);
        }, 'ebs');

        $request = $this->verifyTestSetUp($filter, '', $expectedContent, 2);

        $this->afterVerifyTest($filter, $request);
    }

    public function testPaymentVerifyFinishActionWithErrorCode()
    {
        $filter = 'new_cron';

        $expectedErrorCode = ErrorCode::BAD_REQUEST_NETBANKING_USER_NOT_REGISTERED;

        $expectedContent = [
            'authorized' => 0,
            'success' => 0,
            'timeout' => 0,
            'error' => 0,
            'unknown' => 1,
            'not_applicable' => 0,
            'locked_count' => 0,
            'request_error' => 0,
            'total_payments' => 1,
            'attempted_payments' => 1,
            'must_attempt' => 1,
        ];

        $this->authPaymentTimeout('netbanking');

        $this->mockServerContentFunction(function (& $content)
        {
            throw new PaymentVerificationException(
                ['test' => 'test'],
                null,
                Action::FINISH,
                ErrorCode::BAD_REQUEST_NETBANKING_USER_NOT_REGISTERED);
        }, 'ebs');

        $request = $this->verifyTestSetUp($filter, $expectedErrorCode, $expectedContent);

        $this->afterVerifyTest($filter, $request);
    }

    public function testPaymentVerifyFinishByErrorCodeNetbanking()
    {
        $this->gateway = 'ebs';

        $filter = 'new_cron';

        $expectedErrorCode = ErrorCode::BAD_REQUEST_USER_ACCOUNT_LOCKED;

        $expectedContent = [
            'authorized' => 0,
            'success' => 0,
            'timeout' => 0,
            'error' => 0,
            'unknown' => 1,
            'not_applicable' => 0,
            'locked_count' => 0,
            'request_error' => 0,
            'total_payments' => 1,
            'attempted_payments' => 1,
            'must_attempt' => 1,
        ];

        $this->authPaymentTimeout('netbanking');

        $this->mockServerContentFunction(function (& $content)
        {
            throw new PaymentVerificationException(
                ['test' => 'test'],
                null,
                null,
                ErrorCode::BAD_REQUEST_USER_ACCOUNT_LOCKED);
        }, 'ebs');

        $request = $this->verifyTestSetUp($filter, $expectedErrorCode, $expectedContent);

        $this->afterVerifyTest($filter, $request);
    }

    public function testPaymentVerifyContinueByErrorCodeNetbanking()
    {
        $this->gateway = 'ebs';

        $filter = 'new_cron';

        $expectedErrorCode = ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER;

        $this->authPaymentTimeout('netbanking');

        $this->mockServerContentFunction(function (& $content)
        {
            throw new PaymentVerificationException(
                ['test' => 'test'],
                null,
                null,
                ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER);
        }, 'ebs');

        $expectedContent = [
            'authorized' => 0,
            'success' => 0,
            'timeout' => 0,
            'error' => 1,
            'unknown' => 0,
            'not_applicable' => 0,
            'locked_count' => 0,
            'request_error' => 0,
            'total_payments' => 1,
            'attempted_payments' => 1,
            'must_attempt' => 1,
        ];

        $request = $this->verifyTestSetUp($filter, '', $expectedContent, 2);

        $this->afterVerifyTest($filter, $request);
    }

    public function testPaymentVerifyBulkWithFinalErrorCode()
    {
        $filter = 'new_bulk';

        $expectedContent = [
            'authorized' => 0,
            'success' => 0,
            'timeout' => 0,
            'error' => 0,
            'unknown' => 1,
            'request_error' => 0,
        ];

        $this->authPaymentTimeout('netbanking', true);

        $this->mockServerContentFunction(function (& $content)
        {
            throw new PaymentVerificationException(
                ['test' => 'test'],
                null,
                Action::RETRY,
                ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED);
        }, 'ebs');

        $request = $this->verifyTestSetUp($filter, ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED, $expectedContent, 0);

        $expectedData = [
            'success'            => 0,
            'timeout'            => 0,
            'authorized'          => 0,
            'error'               => 0,
            'unknown'             => 1,
            'request_error'       => 0,
        ];

        $this->afterVerifyTest($filter, $request, $expectedData);
    }

    public function testPaymentVerifyBulkRetryTest()
    {
        $filter = 'new_bulk';

        $expectedContent = [
            'authorized' => 0,
            'success' => 0,
            'timeout' => 0,
            'error' => 1,
            'unknown' => 0,
            'request_error' => 0,
        ];

        $this->authPaymentTimeout('netbanking');

        $this->mockServerContentFunction(function (& $content)
        {
            throw new PaymentVerificationException(
                ['test' => 'test'],
                null,
                Action::RETRY);
        }, 'ebs');

        $request = $this->verifyTestSetUp($filter, '', $expectedContent, 0);

        $expectedData = [
            'success'            => 0,
            'timeout'            => 0,
            'authorized'          => 0,
            'error'               => 1,
            'unknown'             => 0,
            'request_error'       => 0,
        ];

        $this->afterVerifyTest($filter, $request, $expectedData);
    }

    public function testPaymentVerifyBulkBlockTest()
    {
        $this->markTestSkipped("The payments/verify/{filter} route is not live in production");

        $filter = 'new_bulk';

        $expectedContent = [
            'authorized' => 0,
            'success' => 0,
            'timeout' => 0,
            'error' => 1,
            'unknown' => 0,
            'request_error' => 0,
        ];

        $this->authPaymentTimeout('netbanking');

        $this->mockServerContentFunction(function (& $content)
        {
            throw new PaymentVerificationException(
                ['test' => 'test'],
                null,
                Action::BLOCK);
        }, 'ebs');

        $request = $this->verifyTestSetUp($filter, '', $expectedContent, null);

        $expectedData = [
            'success'            => 0,
            'timeout'            => 0,
            'authorized'          => 0,
            'error'               => 1,
            'unknown'             => 0,
            'request_error'       => 0,
        ];

        $this->afterVerifyTest($filter, $request, $expectedData);
    }

    public function testPaymentVerifyBulkNullTest()
    {
        $filter = 'new_bulk';

        $expectedContent = [
            'authorized' => 0,
            'success' => 0,
            'timeout' => 0,
            'error' => 1,
            'unknown' => 0,
            'request_error' => 0,
        ];

        $this->authPaymentTimeout('netbanking');

        $this->mockServerContentFunction(function (& $content)
        {
            throw new PaymentVerificationException(
                ['test' => 'test'],
                null,
                null);
        }, 'ebs');

        $request = $this->verifyTestSetUp($filter, '', $expectedContent, null);

        $expectedData = [
            'success'            => 0,
            'timeout'            => 0,
            'authorized'          => 0,
            'error'               => 1,
            'unknown'             => 0,
            'request_error'       => 0,
        ];

        $this->afterVerifyTest($filter, $request, $expectedData);
    }

    public function testPaymentVerifyBulkFinishActionWithErrorCode()
    {
        $filter = 'new_bulk';

        $expectedErrorCode = ErrorCode::BAD_REQUEST_NETBANKING_USER_NOT_REGISTERED;

        $expectedContent = [
            'authorized' => 0,
            'success' => 0,
            'timeout' => 0,
            'error' => 0,
            'unknown' => 1,
            'request_error' => 0,
        ];

        $this->authPaymentTimeout('netbanking');

        $this->mockServerContentFunction(function (& $content)
        {
            throw new PaymentVerificationException(
                ['test' => 'test'],
                null,
                Action::FINISH,
                ErrorCode::BAD_REQUEST_NETBANKING_USER_NOT_REGISTERED);
        }, 'ebs');

        $request = $this->verifyTestSetUp($filter, $expectedErrorCode, $expectedContent, null);

        $expectedData = [
            'success'            => 0,
            'timeout'            => 0,
            'authorized'          => 0,
            'error'               => 0,
            'unknown'             => 1,
            'request_error'       => 0,
        ];

        $this->afterVerifyTest($filter, $request, $expectedData);
    }

    public function testPaymentVerifyBulkFinishByErrorCodeNetbanking()
    {
        $this->gateway = 'ebs';

        $filter = 'new_bulk';

        $expectedErrorCode = ErrorCode::BAD_REQUEST_USER_ACCOUNT_LOCKED;

        $expectedContent = [
            'authorized' => 0,
            'success' => 0,
            'timeout' => 0,
            'error' => 0,
            'unknown' => 1,
            'request_error' => 0,
        ];

        $this->authPaymentTimeout('netbanking');

        $this->mockServerContentFunction(function (& $content)
        {
            throw new PaymentVerificationException(
                ['test' => 'test'],
                null,
                null,
                ErrorCode::BAD_REQUEST_USER_ACCOUNT_LOCKED);
        }, 'ebs');

        $request = $this->verifyTestSetUp($filter, $expectedErrorCode, $expectedContent, null);

        $expectedData = [
            'success'            => 0,
            'timeout'            => 0,
            'authorized'          => 0,
            'error'               => 0,
            'unknown'             => 1,
            'request_error'       => 0,
        ];

        $this->afterVerifyTest($filter, $request, $expectedData);
    }

    public function testPaymentVerifyBulkContinueByErrorCodeNetbanking()
    {
        $this->gateway = 'ebs';

        $filter = 'new_bulk';

        $expectedErrorCode = ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER;

        $this->authPaymentTimeout('netbanking');

        $this->mockServerContentFunction(function (& $content)
        {
            throw new PaymentVerificationException(
                ['test' => 'test'],
                null,
                null,
                ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER);
        }, 'ebs');

        $expectedContent = [
            'authorized' => 0,
            'success' => 0,
            'timeout' => 0,
            'error' => 1,
            'unknown' => 0,
            'request_error' => 0,
        ];

        $request = $this->verifyTestSetUp($filter, '', $expectedContent, null);

        $expectedData = [
            'success'            => 0,
            'timeout'            => 0,
            'authorized'          => 0,
            'error'               => 1,
            'unknown'             => 0,
            'request_error'       => 0,
        ];

        $this->afterVerifyTest($filter, $request, $expectedData);
    }

    public function testCapturedPaymentVerify()
    {
        $this->setupRedisMock();

        $data = $this->testData['testCapturedPaymentVerify'];

        $payment = $this->getDefaultNetbankingPaymentArray(Bank::MAHB);

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getDbLastEntityPublic('payment');

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/all',
            'method' => 'post'
        ];

        $time = Carbon::now(Timezone::IST);

        $time->addMinutes(15);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'authorized' => 0,
            'success' => 0,
            'timeout' => 0,
            'error' => 0,
            'unknown' => 0,
            'request_error' => 0,
            'not_applicable' => 0,
            'locked_count' => 0,
        ];

        $this->assertVerifyNewRouteContent($resultData, $content);
    }

    public function testCapturedPaymentVerifyNewRoute()
    {
        $this->setupRedisMock();

        $data = $this->testData['testCapturedPaymentVerify'];

        $payment = $this->getDefaultNetbankingPaymentArray(Bank::MAHB);

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getDbLastEntityPublic('payment');

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/new_cron',
            'method' => 'post'
        ];

        $time = Carbon::now(Timezone::IST);

        $time->addMinutes(15);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $resultData = [
            'authorized' => 0,
            'success' => 0,
            'timeout' => 0,
            'error' => 0,
            'locked_count' => 0,
            'unknown' => 0,
            'request_error' => 0,
            'not_applicable' => 0,
            'attempted_payments' => 0,
            'total_payments' => 0,
            'must_attempt' => 0
        ];

        $this->assertVerifyNewRouteContent($resultData, $content);
    }

    protected function authPaymentTimeout(string $method, bool $terminalFailure = false, bool $invalidErrorCode = false)
    {
        $this->setMockGatewayTrue();

        $this->registerPaymentTimeoutMocks($terminalFailure, $invalidErrorCode);

        $paymentAttributes = $this->getPaymentArrayByMethod($method);

        $this->authPayment($paymentAttributes);

        $pay = $this->getDbLastPayment();

        $prevVerifyAt = $pay['verify_at'];
    }

    protected function verifyTestSetUp(string $filter, string $errorCode = '',
                                       array $expectedContent = [], $expectedBucket = 9) : array
    {

        $this->setMockGatewayTrue();

        $this->ba->cronAuth();

        $request = [
            'url'    => '/payments/verify/' . $filter,
            'method' => 'post',
        ];

        $pay = $this->getDbLastPayment();

        if ($filter === 'new_cron')
        {
            $request['content'] = [
                    'delay' => 600,
                ];
        }
        else if ($filter === 'new_bulk')
        {
            $request['content'] = [
                'payment_ids' => [
                    'pay_'.$pay->getId(),
                    ],
            ];
        }

        $time = Carbon::now(Timezone::IST);

        $time->addMinutes(20);

        Carbon::setTestNow($time);

        $content = $this->makeRequestAndGetContent($request);

        $pay = $this->getDbLastPayment();

        $this->assertEquals($errorCode, $pay['internal_error_code']);

        $newBucket = $pay['verify_bucket'];

        $this->assertEquals($expectedBucket, $newBucket);

        $this->assertVerifyNewRouteContent($expectedContent, $content);

        return $request;
    }

    protected function afterVerifyTest(string $filter, array $request, array $expectedData = [])
    {
        $time = Carbon::now(Timezone::IST);

        $time->addMinutes(30);

        Carbon::setTestNow($time);

        $this->ba->cronAuth();

        $content = $this->makeRequestAndGetContent($request);

        $resultData = array();

        if ($filter === 'new_cron')
        {
            $resultData = [
                'success'            => 0,
                'total_payments'     => 0,
                'timeout'            => 0,
                'not_applicable'     => 0,
                'locked_count'       => 0,
                'attempted_payments' => 0,
                'must_attempt'       => 0,
                'authorized'          => 0,
                'error'               => 0,
                'unknown'             => 0,
                'request_error'       => 0,
            ];
        }
        else if( $filter === 'new_bulk')
        {
            $resultData = $expectedData;
        }

        $this->assertVerifyNewRouteContent($resultData, $content);

        Carbon::setTestNow();
    }

    protected function registerPaymentTimeoutMocks(bool $terminalFailure = false, bool $invalidErrorCode = false)
    {
        if ( $terminalFailure === true)
        {

            $this->mockServerContentFunction(function (& $content)
            {
                throw new BadRequestException(ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED);
            }, 'ebs');

            return;
        }

        if ($invalidErrorCode === true)
        {
            $this->mockServerContentFunction(function (& $content)
            {
                throw new \Exception('', 404);
            }, 'ebs');

            return;
        }

        $this->mockServerContentFunction(function (& $content)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT);
        }, 'ebs');
    }

    protected function getPaymentArrayByMethod(string $method)
    {
        $paymentArray = [];
        switch($method)
        {
            case 'netbanking':
                $paymentArray = $this->getDefaultNetbankingPaymentArray(Bank::MAHB);
                break;
            case 'card':
                $paymentArray = $this->getDefaultPaymentArray();
                break;
            case 'cardless_emi':
                $paymentArray = $this->getDefaultCardlessEmiPaymentArray('earlysalary');
                break;
            case 'upi':
                $paymentArray = $this->getDefaultUpiPaymentArray();
                break;
            case 'wallet':
                $paymentArray = $this->getDefaultWalletPaymentArray();
                break;
            case 'emandate':
                $paymentArray = $this->getEmandatePaymentArray();
                break;
            case 'paylater':
                $paymentArray = $this->getDefaultPayLaterPaymentArray('icic');
                break;
        }

        return $paymentArray;
    }

    protected function authPayment($payment = null, $server = null, $key = null)
    {
        $request = $this->buildAuthPaymentRequest($payment, $server);

        $this->ba->publicAuth($key);

        try
        {
            $content = $this->makeRequestAndGetContent($request);

            return $content;
        }
        catch(\Exception $e)
        {
            $this->app['trace']->debug(TraceCode::PAYMENT_AUTH_FAILURE, [
                'exception' => $e,
            ]);

            if( ($e->getCode() === ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED) or
                ($e->getCode() === 404))
            {
                $pay = $this->getDbLastPayment();

                $pay->setInternalErrorCode($e->getCode());

                $this->fixtures->edit('payment',
                    $pay->getId(),
                    [
                       'internal_error_code' =>  $e->getCode(),
                    ]);


                $pay = $this->getDbEntityById('payment', $pay->getId());

                $this->assertEquals($e->getCode(), $pay['internal_error_code']);
            }

        }

        return null;
    }

    protected function assertVerifyNewRouteContent(array $expectedContent, array $actualContent)
    {
        // We dont want to check time taken for payments
        unset($actualContent['total_time']);
        unset($actualContent['authorize_time']);
        unset($actualContent['fetch_time']);
        unset($actualContent['verifiable_count']);
        unset($actualContent['verified_payments']);
        unset($actualContent['bucket_filter']);
        unset($actualContent['filter']);
        unset($actualContent['start_time']);
        unset($actualContent['end_time']);

        $this->assertEquals($expectedContent, $actualContent);
    }
}
