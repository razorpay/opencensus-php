<?php

namespace RZP\Tests\Functional\PaymentsUpi\Service;

use Mockery;
use Illuminate\Http\UploadedFile;

use RZP\Exception;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Services\RazorXClient;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class UpiPaymentServiceTest extends TestCase
{
    use ReconTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $payment;

    protected $terminal;

    protected $upiPaymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['rzp.mode'] = Mode::TEST;

        // Enable UPI payment service in config
        $this->app['config']->set(['applications.upi_payment_service.enabled' => true]);

        $this->upiPaymentService =  Mockery::mock('RZP\Services\UpiPayment\Mock\Service', [$this->app])->makePartial();

        $this->app->instance('upi.payments', $this->upiPaymentService);

        // We have Airtel Gateway Enabled for Service
        $this->terminal = $this->fixtures->create('terminal:shared_upi_airtel_terminal');

        $this->fixtures->merchant->enableMethod(Account::TEST_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_airtel_v1', 'upips');
        });
    }

    /**
     * Test Successful Collect Payment Creation
     *
     * @return void
     */
    public function testCollectPaymentCreateSuccess($description = 'create_collect_success')
    {
        $payment = $this->payment;

        $payment['description'] = $description;

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $this->assertEquals('async', $response['type']);

        $this->assertArrayHasKey('vpa', $response['data']);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
            Entity::STATUS          => 'created',
            Entity::GATEWAY         => 'upi_airtel',
            Entity::TERMINAL_ID     => $this->terminal->getId(),
            Entity::REFUND_AT       => null,
            Entity::CPS_ROUTE       => Entity::UPI_PAYMENT_SERVICE,
            ], $payment->toArray()
        );

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $this->assertNull($upiEntity);
    }

    /**
     * Test Successful Intent Payment Creation
     *
     * @return void
     */
    public function testIntentPaymentCreateSuccess()
    {
        $this->terminal = $this->fixtures->create('terminal:shared_upi_airtel_intent_terminal');

        $payment = $this->payment;

        $payment['description'] = 'create_intent_success';

        unset($payment['vpa']);
        $payment['upi']['flow'] = 'intent';

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $this->assertEquals('intent', $response['type']);

        $this->assertArrayHasKey('intent_url', $response['data']);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
            Entity::STATUS          => 'created',
            Entity::GATEWAY         => 'upi_airtel',
            Entity::TERMINAL_ID     => $this->terminal->getId(),
            Entity::REFUND_AT       => null,
            Entity::CPS_ROUTE       => Entity::UPI_PAYMENT_SERVICE,
            ], $payment->toArray()
        );

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $this->assertNull($upiEntity);
    }

    /**
     * Test Validation failure for collect payment
     *
     * @return void
     */
    public function testCollectPaymentCreateValidationFailure()
    {
        $payment = $this->payment;

        $payment['description'] = 'validation_failure_collect_vpa';

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPaymentViaAjaxRoute($payment);
            },
            Exception\BadRequestException::class,
            'Vpa is required for UPI collect request');

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
            Entity::STATUS              => 'failed',
            Entity::GATEWAY             => 'upi_airtel',
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::REFUND_AT           => null,
            Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
            Entity::ERROR_CODE          => 'BAD_REQUEST_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'BAD_REQUEST_INPUT_VALIDATION_FAILURE'
            ], $payment->toArray()
        );
    }

    /**
     * test UPS service failure
     *
     * @return void
     */
    public function testPaymentServiceFailure()
    {
        $payment = $this->payment;

        $payment['description'] = 'service_failure';

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPaymentViaAjaxRoute($payment);
            },
            Exception\ServerErrorException::class,
            'internal server error');

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
            Entity::STATUS              => 'failed',
            Entity::GATEWAY             => 'upi_airtel',
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::REFUND_AT           => null,
            Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
            Entity::ERROR_CODE          => 'SERVER_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'SERVER_ERROR_UPI_PAYMENT_SERVICE_FAILURE'
            ], $payment->toArray());
    }

    /**
     * Test Mozart failure for collect payment
     *
     * @return void
     */
    public function testMozartFailure()
    {
        $payment = $this->payment;

        $payment['description'] = 'mozart_failure';

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPaymentViaAjaxRoute($payment);
            },
            Exception\GatewayErrorException::class);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
            Entity::STATUS              => 'failed',
            Entity::GATEWAY             => 'upi_airtel',
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::REFUND_AT           => null,
            Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
            Entity::ERROR_CODE          => 'GATEWAY_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'GATEWAY_ERROR_ENCRYPTION_ERROR'
            ], $payment->toArray()
        );
    }

    /**
     * Test Mozart failure for collect payment
     *
     * @return void
     */
    public function testMozartValidationFailure()
    {
        $payment = $this->payment;

        $payment['description'] = 'mozart_validation_failure';

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPaymentViaAjaxRoute($payment);
            },
            Exception\BadRequestException::class);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
            Entity::STATUS              => 'failed',
            Entity::GATEWAY             => 'upi_airtel',
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::REFUND_AT           => null,
            Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
            Entity::ERROR_CODE          => 'BAD_REQUEST_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'BAD_REQUEST_VALIDATION_FAILURE'
            ], $payment->toArray()
        );
    }

    /**
     * Test Failed Collect Payment with pre-process through UPS
     * @return void
     */
    public function testCollectPaymentFailure()
    {
        $this->testCollectPaymentCreateSuccess('payment_failed');

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                    ->setConstructorArgs([$this->app])
                    ->onlyMethods(['getTreatment'])
                    ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'ups_upi_airtel_pre_process_v1', 'upi_airtel');
        });

        $this->mockServerContentFunction(
            function (&$error)
            {
                $responseError = [
                'internal' => [
                    'code'          => 'GATEWAY_ERROR_DEBIT_FAILED',
                    'description'   => 'GATEWAY_ERROR',
                    'metadata'      => [
                        'description'               => $error['description'],
                        'gateway_error_code'        => $error['gateway_error_code'],
                        'gateway_error_description' => $error['gateway_error_description'],
                        'internal_error_code'       => $error['internal_error_code']
                    ]
                ]
                ];

                return $responseError;
            }
        );

        $payment = $this->getDbLastpayment();

        $content = $this->mockServer('upi_airtel')->getAsyncCallbackContent($payment->toArray(),
            $this->terminal->toArray());

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_airtel');

        $payment = $this->getDbLastPayment();

        // We should have received a successful response
        $this->assertEquals(['success' => false], $response);

        $this->assertArraySubset(
            [
            Entity::STATUS              => Status::FAILED,
            Entity::GATEWAY             => 'upi_airtel',
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
            Entity::ERROR_CODE          => 'GATEWAY_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'GATEWAY_ERROR_DEBIT_FAILED',
            ], $payment->toArray()
        );

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $this->assertNull($upiEntity);
    }

    /**
     * Test Successful Collect Payment with pre-process through UPS
     * @return void
     */
    public function testCollectPaymentSuccess($description = 'create_collect_success')
    {
        $this->testCollectPaymentCreateSuccess($description);

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'ups_upi_airtel_pre_process_v1', 'upi_airtel');
        });

        $payment = $this->getDbLastpayment();

        $content = $this->mockServer('upi_airtel')->getAsyncCallbackContent($payment->toArray(),
            $this->terminal->toArray());

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_airtel');

        $payment = $this->getDbLastPayment();

        // We should have received a successful response
        $this->assertEquals(['success' => true], $response);

        $content = json_decode($content, true);

        $this->assertArraySubset(
            [
            Entity::STATUS          => Status::AUTHORIZED,
            Entity::GATEWAY         => 'upi_airtel',
            Entity::TERMINAL_ID     => $this->terminal->getId(),
            Entity::CPS_ROUTE       => Entity::UPI_PAYMENT_SERVICE,
            Entity::REFERENCE16     => $content['rrn'],
            ], $payment->toArray()
        );

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $this->assertNull($upiEntity);
    }

    /**
     * Test Successful Collect Payment with pre-process through UPS
     *
     * @return void
     */
    public function testCollectPaymentSuccesWithApiPreProcess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'upi_airtel';

        $this->testCollectPaymentCreateSuccess();

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_airtel_pre_process_v1', 'upi_airtel');
        });

        $payment = $this->getDbLastpayment();

        $content = $this->mockServer('upi_airtel')->getAsyncCallbackContent($payment->toArray(),
            $this->terminal->toArray());

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_airtel');

        $payment = $this->getDbLastPayment();

        // We should have received a successful response
        $this->assertEquals(['success' => true], $response);

        $this->assertArraySubset(
            [
            Entity::STATUS          => Status::AUTHORIZED,
            Entity::GATEWAY         => 'upi_airtel',
            Entity::TERMINAL_ID     => $this->terminal->getId(),
            Entity::CPS_ROUTE       => Entity::UPI_PAYMENT_SERVICE,
            ], $payment->toArray()
        );

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $this->assertNull($upiEntity);
    }

    /**
     * Test Failed Collect Payment with pre-process through API
     *
     * @return void
     */
    public function testCollectPaymentFailureWithApiPreProcess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'upi_airtel';

        $this->testCollectPaymentCreateSuccess('payment_failed');

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_airtel_pre_process_v1', 'upi_airtel');
        });

        $this->mockServerContentFunction(
            function (&$error)
            {
                $responseError = [
                'internal' => [
                    'code'          => 'GATEWAY_ERROR_DEBIT_FAILED',
                    'description'   => 'GATEWAY_ERROR',
                    'metadata'      => [
                        'description'               => $error['description'],
                        'gateway_error_code'        => $error['gateway_error_code'],
                        'gateway_error_description' => $error['gateway_error_description'],
                        'internal_error_code'       => $error['internal_error_code']
                    ]
                ]
                ];

                return $responseError;
            }
        );

        $payment = $this->getDbLastpayment();

        $content = $this->mockServer('upi_airtel')->getAsyncCallbackContent($payment->toArray(),
            $this->terminal->toArray());

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_airtel');

        $payment = $this->getDbLastPayment();

        // We should have received a successful response
        $this->assertEquals(['success' => false], $response);

        $this->assertArraySubset(
            [
            Entity::STATUS              => Status::FAILED,
            Entity::GATEWAY             => 'upi_airtel',
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
            Entity::ERROR_CODE          => 'GATEWAY_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'GATEWAY_ERROR_DEBIT_FAILED',
            ], $payment->toArray()
        );

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $this->assertNull($upiEntity);
    }

    /**
     * Test successful verification
     *
     * @return void
     */
    public function testVerifySuccess()
    {
        $this->testCollectPaymentSuccess();

        $payment = $this->getDbLastPayment();

        $payment = $this->verifyPayment($payment->getPublicId());

        $this->assertSame($payment['payment']['verified'], 1);
    }

    /**
     * Test verify amount mismatch
     *
     * @return void
     */
    public function testVerifyAmountMisMatch()
    {
        $this->testCollectPaymentSuccess('verify_amount_mismatch');

        $payment = $this->getDbLastPayment();

        $this->assertSame(Status::AUTHORIZED, $payment->getStatus());

        $this->makeRequestAndCatchException(function() use ($payment)
        {
            $this->verifyPayment($payment->getPublicId());
        }, Exception\RuntimeException::class, 'Payment verification failed due to amount mismatch.');
    }

    /**
     * Test Late Auth Payments
     *
     * @return void
     */
    public function testVerifyLateAuth()
    {
        $this->testMozartFailure();

        $payment = $this->getDbLastPayment();

        $time = Carbon::now(Timezone::IST)->addMinutes(4);

        Carbon::setTestNow($time);

        $this->verifyAllPayments();

        $payment->reload();

        $this->assertTrue($payment->isLateAuthorized());

        $this->assertArraySubset(
            [
            Entity::STATUS          => Status::AUTHORIZED,
            Entity::GATEWAY         => 'upi_airtel',
            Entity::TERMINAL_ID     => $this->terminal->getId(),
            Entity::CPS_ROUTE       => Entity::UPI_PAYMENT_SERVICE,
            ], $payment->toArray()
        );

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $this->assertNull($upiEntity);
    }

    public function testPaymentReconciliationMultipleRrn()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '22712135190';

        $this->gateway = 'upi_airtel';

        $this->makeUpiAirtelPaymentsSince($createdAt, $rrn, 1);

        $payment = $this->getDbLastPayment();

        // Changes a rrn of entity fetch response
        $this->mockServerContentFunction(function (&$content)
        {
            $content['customer_reference'] = '1234567109';

            return $content;
        });

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiAirtel');

        $this->paymentReconAsserts($payment->toArray());

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'            => 'reconciliation',
                'gateway'         => 'UpiAirtel',
                'status'          => 'processed',
                'total_count'     => 1,
                'success_count'   => 1,
                'processed_count' => 1,
                'failure_count'   => 0,
            ],
            $batch
        );
    }

    public function testPaymentReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '22712135190';

        $this->gateway = 'upi_airtel';

        $this->makeUpiAirtelPaymentsSince($createdAt, $rrn, 1);

        $payment = $this->getDbLastPayment();

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiAirtel');

        $this->paymentReconAsserts($payment->toArray());

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'            => 'reconciliation',
                'gateway'         => 'UpiAirtel',
                'status'          => 'processed',
                'total_count'     => 1,
                'success_count'   => 1,
                'processed_count' => 1,
                'failure_count'   => 0,
            ],
            $batch
        );
    }

    public function testUpiAirtelForceAuthorizePayment()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        // rrn to be used
        $rrn = '22712135190';

        $this->gateway = 'upi_airtel';

        $this->makeUpiAirtelPaymentsSince($createdAt, $rrn, 1);

        $payment = $this->getDbLastPayment();

        $this->fixtures->payment->edit($payment['id'],
            [
                'status'                => 'failed',
                'authorized_at'         => null,
                'error_code'            => 'BAD_REQUEST_ERROR',
                'internal_error_code'   => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'     => 'Payment was not completed on time.',
            ]);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('failed', $payment['status']);

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiAirtel');

        $payments = $this->getEntities('payment', [], true);

        $payment = $payments['items'][0];

        $this->assertArraySelectiveEquals(
            [
                'cps_route'       => Entity::UPI_PAYMENT_SERVICE,
                'gateway'         => 'upi_airtel',
                'vpa'             => 'forceauth@upi',
                'reference16'     => '22712135190',
            ],
            $payment
        );

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        $this->assertNotNull($transaction['reconciled_at']);

        $this->assertNotNull($payment['reference16']);
    }

    /**
     * test unexpected payment with pre_process through UPS
     */
    public function testUnexpectedPaymentSuccessWithUpsPreProcess()
    {
        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);

        $this->fixtures->merchant->enableMethod(Account::DEMO_ACCOUNT, Method::UPI);

        $this->fixtures->merchant->activate();

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'ups_upi_airtel_pre_process_v1', 'upi_airtel');
        });

        $content = $this->mockServer('upi_airtel')->getUnexpectedAsyncCallbackContentForAirtel();

        $this->makeS2SCallbackAndGetContent($content, 'upi_airtel');

        $paymentEntity = $this->getLastEntity('payment', true);

        $authorizeUpiEntity = $this->getLastEntity('upi', true);

        $this->assertNotNull($authorizeUpiEntity['merchant_reference']);

        $paymentTransactionEntity = $this->getLastEntity('transaction', true);

        $assertEqualsMap = [
            'authorized'                           => $paymentEntity['status'],
            'authorize'                            => $authorizeUpiEntity['action'],
            'pay'                                  => $authorizeUpiEntity['type'],
            $paymentEntity['id']                   => 'pay_' . $authorizeUpiEntity['payment_id'],
            $paymentTransactionEntity['id']        => 'txn_' . $paymentEntity['transaction_id'],
            $paymentTransactionEntity['entity_id'] => $paymentEntity['id'],
            $paymentTransactionEntity['type']      => 'payment',
            $paymentTransactionEntity['amount']    => $paymentEntity['amount'],
            Account::DEMO_ACCOUNT                  => $paymentEntity['merchant_id'],
            $authorizeUpiEntity['gateway']         => 'upi_airtel',
            $authorizeUpiEntity['gateway']         => $paymentEntity['gateway'],
        ];

        foreach ($assertEqualsMap as $matchLeft => $matchRight)
        {
            $this->assertEquals($matchLeft, $matchRight);
        }
    }

    public function testUpiAirtelRefundRecon()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '22712135190';

        $this->gateway = 'upi_airtel';

        $this->makeUpiAirtelPaymentsSince($createdAt, $rrn, 1);

        $payment = $this->getDbLastPayment();

        $refund = $this->createDependentEntitiesForRefund($payment);

        $this->mockReconContentFunction(function (&$content) use ($refund)
        {
            if ($content['Till ID'] === $refund['id'])
            {
                $content = [];
            }
        });

        $fileContents = $this->generateReconFile(
            [
                'gateway' => $this->gateway,
                'type'    => 'refund',
            ]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiAirtel');

        $this->refundReconAsserts($refund);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'            => 'reconciliation',
                'gateway'         => 'UpiAirtel',
                'status'          => 'processed',
                'total_count'     => 1,
                'success_count'   => 1,
                'processed_count' => 1,
                'failure_count'   => 0,
            ],
            $batch
        );
    }

    public function testPaymentYesbankReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->gateway = 'upi_yesbank';

        $this->makeUpiYesbankPaymentsSince($createdAt, 1);

        $payment = $this->getDbLastPayment();

        $this->mockReconContentFunction(
            function(& $content, $action = null)
           {
                if ($action === 'yesbank_recon')
                {
                    $content[0]['Customer Ref No']          = '227121351902';
                }
            });

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiYesBank');

        $this->paymentReconAsserts($payment->toArray());

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
                [
                        'type'            => 'reconciliation',
                        'gateway'         => 'UpiYesBank',
                        'status'          => 'processed',
                        'total_count'     => 1,
                        'success_count'   => 1,
                        'processed_count' => 1,
                       'failure_count'   => 0,
                    ],
                $batch
           );
   }

    public function testUpiYesBankUnexpectedPaymentRecon()
    {
        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);
        $this->fixtures->merchant->enableUpi(Account::DEMO_ACCOUNT);

        $terminal = $this->fixtures->create('terminal:shared_upi_yesbank_terminal');

        $this->gateway = 'upi_yesbank';

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $payments = $this->makeUpiYesBankPaymentsSince($createdAt,1);

        $paymentEntity = $this->getDbLastpayment();

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'yesbank_recon')
                {
                    $content[0]['PG Merchant ID']           = 'vpa_merchantsVpaId';
                    $content[0]['Order No']                 = 'YESB12WE34RDSQ187';
                    $content[0]['Customer Ref No.']         = '123456789013'; // rrn is used to mock for unexpected payment
                }
            });

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiYesBank');

        $unexpectedPayment = $this->getLastEntity('payment', true);

        $this->assertNotEquals($unexpectedPayment['id'], $paymentEntity['id']);

        $this->assertNotNull($unexpectedPayment['reference16']);

        $transaction = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transaction['reconciled_at']);

        $unexpectedUpiEntity = $this->getLastEntity('upi', true);

        $this->assertEquals('YESB12WE34RDSQ187', $unexpectedUpiEntity['merchant_reference']);

        $this->assertEquals('123456789013', $unexpectedUpiEntity['npci_reference_id']);

        $this->assertNotNull($unexpectedUpiEntity['reconciled_at']);
    }

    public function testUpsYesBankDuplicateUnexpectedPayment()
    {
        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);
        $this->fixtures->merchant->enableUpi(Account::DEMO_ACCOUNT);

        $terminal = $this->fixtures->create('terminal:shared_upi_yesbank_terminal');

        $this->gateway = 'upi_yesbank';

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $payments = $this->makeUpiYesBankPaymentsSince($createdAt,1);

        $paymentEntity = $this->getDbLastEntityToArray('payment');

        // Changes a rrn of entity fetch response
        $this->mockServerContentFunction(function (&$content) use ($paymentEntity)
        {
            $content['payment_id'] = $paymentEntity['id'];
        });

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'yesbank_recon')
                {
                    $content[0]['PG Merchant ID']           = 'vpa_merchantsVpaId';
                    $content[0]['Order No']                 = 'YESB12WE34RDSQ187';
                    $content[0]['Customer Ref No.']         = '123456789013'; // rrn is used to mock for unexpected payment
                }
            });

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiYesBank');

        $unexpectedPayment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals($unexpectedPayment['id'], $paymentEntity['id']);

        $this->assertNotNull($unexpectedPayment['reference16']);

        $transaction = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transaction['reconciled_at']);
    }

    public function testUpsYesBankMultipleRrn()
    {
        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);
        $this->fixtures->merchant->enableUpi(Account::DEMO_ACCOUNT);

        $terminal = $this->fixtures->create('terminal:shared_upi_yesbank_terminal');

        $this->gateway = 'upi_yesbank';

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $payments = $this->makeUpiYesBankPaymentsSince($createdAt,1);

        $paymentEntity = $this->getDbLastEntityToArray('payment');

        // Mark reconciled_at of entity fetch response for multiple rrn scenario
        $this->mockServerContentFunction(function (&$content)
        {
            $content['reconciled_at'] = Carbon::now(Timezone::IST)->getTimestamp();
        });

        $this->mockReconContentFunction(
            function(& $content, $action = null) use ($paymentEntity)
            {
                if ($action === 'yesbank_recon')
                {
                    $content[0]['PG Merchant ID']           = 'vpa_merchantsVpaId';
                    $content[0]['Order No']                 = $paymentEntity['id'];
                    $content[0]['Customer Ref No']          = '123456789012';
                }
            });

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiYesBank');

        $unexpectedPayment = $this->getDbLastEntityToArray('payment');

        $this->assertNotEquals($unexpectedPayment['id'], $paymentEntity['id']);

        $this->assertNotNull($unexpectedPayment['reference16']);

        $transaction = $this->getDbLastEntityToArray('transaction');

        $this->assertNotNull($transaction['reconciled_at']);

        $unexpectedUpiEntity = $this->getDbLastEntityToArray('upi');

        $this->assertEquals($paymentEntity['id'], $unexpectedUpiEntity['merchant_reference']);

        $this->assertEquals('123456789012', $unexpectedUpiEntity['npci_reference_id']);

        $this->assertNotNull($unexpectedUpiEntity['reconciled_at']);
    }

    protected function createDependentEntitiesForRefund($payment, $status = 'authorized')
    {
        $refundArray = [
            'payment_id'  => $payment['id'],
            'merchant_id' => '10000000000000',
            'amount'      => $payment['amount'],
            'base_amount' => $payment['amount'],
            'status'      => 'processed',
            'gateway'     => 'upi_airtel',
        ];

        $refund = $this->fixtures->create('refund', $refundArray)->toArray();

        $this->fixtures->create(
            'mozart',
            array(
                'payment_id' => $payment['id'],
                'action'     => 'refund',
                'refund_id'  => $refund['id'],
                'gateway'    => 'upi_airtel',
                'amount'     => $payment['amount'],
                'raw'        => json_encode(
                    [
                        'status'                => 'refund_initiated_successfully',
                        'apiStatus'             => 'SUCCESS',
                        'merchantId'            => '',
                        'refundAmount'          => $payment['amount'],
                        'responseCode'          => 'SUCCESS',
                        'responseMessage'       => 'SUCCESS',
                        'merchantRequestId'     => $payment['id'],
                        'transactionAmount'     => $payment['amount'],
                        'gatewayResponseCode'   => '00',
                        'gatewayTransactionId'  => 'FT2022712537204137',
                    ]
                )
            )
        );

        return $refund;
    }

    protected function refundReconAsserts(array $refund)
    {
        $updatedRefund = $this->getDbEntity('refund', ['id' => $refund['id']]);

        $this->assertNotNull($updatedRefund['reference1']);

        $gatewayEntity = $this->getDbEntity(
            'mozart',
            [
                'payment_id' => $updatedRefund['payment_id'],
                'action'     => 'refund',
            ]);

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertEquals($data['gatewayTransactionId'], 'FT2022712537204137');

        $transactionEntity = $this->getDbEntity('transaction', ['entity_id' => $updatedRefund['id']]);

        $this->assertNotNull($transactionEntity['reconciled_at']);
    }

    protected function paymentReconAsserts(array $payment)
    {
        $updatedPayment = $this->getDbEntity('payment', ['id' => $payment['id']]);

        $this->assertEquals(true, $updatedPayment['gateway_captured']);

        $this->assertEquals(Entity::UPI_PAYMENT_SERVICE, $updatedPayment['cps_route']);

        $gatewayEntity = $this->getDbEntity('mozart', ['payment_id' => $updatedPayment['id']]);

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertEquals($data['rrn'], '227121351902');

        $this->assertEquals($data['rrn'], $updatedPayment['reference16']);

        $this->assertEquals($data['gatewayTransactionId'], 'FT2022712537204137');

        $transactionEntity = $this->getDbEntity('transaction', ['entity_id' => $updatedPayment['id']]);

        $this->assertNotNull($transactionEntity['reconciled_at']);
    }

    private function createUploadedFile($file)
    {
        $this->assertFileExists($file);

        $mimeType = 'text/csv';

        $uploadedFile = new UploadedFile(
            $file,
            $file,
            $mimeType,
            filesize($file),
            null,
            true
        );

        return $uploadedFile;
    }

    private function makeUpiAirtelPaymentsSince(int $createdAt, string $rrn, int $count = 3)
    {
        for ($i = 0; $i < $count; $i++)
        {
            $payments[] = $this->doUpiAirtelPayment();
        }

        foreach ($payments as $payment)
        {
            $this->fixtures->edit('payment', $payment, ['created_at' => $createdAt]);
        }

        return $payments;
    }

    private function doUpiAirtelPayment()
    {
        $attributes = [
            'terminal_id'       => $this->terminal->getId(),
            'method'            => 'upi',
            'amount'            => $this->payment['amount'],
            'base_amount'       => $this->payment['amount'],
            'amount_authorized' => $this->payment['amount'],
            'status'            => 'captured',
            'gateway'           => $this->gateway,
            'authorized_at'     => time(),
            'cps_route'         => Entity::UPI_PAYMENT_SERVICE,
        ];

        $payment = $this->fixtures->create('payment', $attributes);

        $transaction = $this->fixtures->create('transaction',
            ['entity_id' => $payment->getId(), 'merchant_id' => '10000000000000']);

        $this->fixtures->edit('payment', $payment->getId(), ['transaction_id' => $transaction->getId()]);

        $this->fixtures->create(
            'mozart',
            array(
                'payment_id' => $payment['id'],
                'action' => 'authorize',
                'gateway' => 'upi_airtel',
                'amount' => $payment['amount'],
                'raw' => json_encode(
                    [
                        'rrn' => '227121351902',
                        'type' => 'MERCHANT_CREDITED_VIA_PAY',
                        'amount' => $payment['amount'],
                        'status' => 'payment_successful',
                        'payeeVpa' => 'billpayments@abfspay',
                        'payerVpa' => '',
                        'payerName' => 'JOHN MILLER',
                        'paymentId' => $payment['id'],
                        'gatewayResponseCode' => '00',
                        'gatewayTransactionId' => 'FT2022712537204137'
                    ]
                )
            )
        );

        return $payment->getId();
    }

    protected function mockServerRequestFunction($closure)
    {
        $this->upiPaymentService->shouldReceive('request')->andReturnUsing($closure);
    }

    protected function mockServerContentFunction($closure)
    {
        $this->upiPaymentService->shouldReceive('content')->andReturnUsing($closure);
    }

    /**
     * sets the razox mock
     *
     * @param [type] $closure
     * @return void
     */
    protected function setRazorxMock($closure)
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
            ->method('getTreatment')
            ->will($this->returnCallback($closure));
    }

    /**
     * returns a mock response of the razorx request
     *
     * @param string $inputFeature
     * @param string $expectedFeature
     * @param string $variant
     * @return string
     */
    protected function getRazoxVariant(string $inputFeature, string $expectedFeature, string $variant): string
    {
        if ($expectedFeature === $inputFeature)
        {
            return $variant;
        }

        return 'control';
    }

    private function makeUpiYesbankPaymentsSince(int $createdAt, int $count = 3)
    {
        for ($i = 0; $i < $count; $i++)
        {
            $payments[] = $this->doUpiYesbankPayment();
        }

        foreach ($payments as $payment)
        {
            $this->fixtures->edit('payment', $payment, ['created_at' => $createdAt]);
        }

        return $payments;
    }

    private function doUpiYesbankPayment()
    {
            $attributes = [
                    'terminal_id'       => $this->terminal->getId(),
                    'method'            => 'upi',
                    'amount'            => $this->payment['amount'],
                    'base_amount'       => $this->payment['amount'],
                    'amount_authorized' => $this->payment['amount'],
                    'status'            => 'captured',
                    'gateway'           => $this->gateway,
                    'authorized_at'     => time(),
                    'cps_route'         => Entity::UPI_PAYMENT_SERVICE,
                ];


            $payment = $this->fixtures->create('payment', $attributes);

            $transaction = $this->fixtures->create('transaction',
                    ['entity_id' => $payment->getId(), 'merchant_id' => '10000000000000']);

            $this->fixtures->edit('payment', $payment->getId(), ['transaction_id' => $transaction->getId()]);

            $this->fixtures->create(
                    'mozart',
                    array(
                            'payment_id' => $payment['id'],
                            'action' => 'authorize',
                            'gateway' => 'upi_yesbank',
                            'amount' => $payment['amount'],
                            'raw' => json_encode(
                                        [
                                                'rrn' => '227121351902',
                                                'type' => 'MERCHANT_CREDITED_VIA_PAY',
                                                'amount' => $payment['amount'],
                                                'status' => 'payment_successful',
                                                'payeeVpa' => 'billpayments@abfspay',
                                                'payerVpa' => '',
                                                'payerName' => 'JOHN MILLER',
                                                'paymentId' => $payment['id'],
                                                'gatewayResponseCode' => '00',
                                                'gatewayTransactionId' => 'FT2022712537204137'
                                                ]
                                    )
                            )
                );

    return $payment->getId();
}
}
