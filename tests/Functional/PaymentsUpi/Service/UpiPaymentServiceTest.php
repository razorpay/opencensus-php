<?php

namespace RZP\Tests\Functional\PaymentsUpi\Service;

use Mockery;
use Illuminate\Http\UploadedFile;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Payment\Status;
use RZP\Services\RazorXClient;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\PaymentVerificationException;
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

    protected $pgService;

    protected $shouldCreateTerminal = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['rzp.mode'] = Mode::TEST;

        // Enable UPI payment service in config
        $this->app['config']->set(['applications.upi_payment_service.enabled' => true]);

        $this->upiPaymentService =  Mockery::mock('RZP\Services\UpiPayment\Mock\Service', [$this->app])->makePartial();

        $this->app->instance('upi.payments', $this->upiPaymentService);

        $this->pgService = Mockery::mock('RZP\Services\PGRouter', [$this->app])->makePartial();

        $this->app->instance('pg_router', $this->pgService);

        $this->enablePgRouterConfig();

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

    public function testValidateVpaNumericSuccess()
    {
        $this->fixtures->merchant->addFeatures(['enable_vpa_validate']);

        $input = [
            'entity' => 'vpa',
            'value' => '9815225341',
        ];

        $request = [
            'content' => $input,
            'url'     => '/v1/payments/validate/account',
            'method'  => 'post'
        ];

        $this->ba->publicAuth();

        $toAssertResponse = [
            'vpa' => 'test.cust@icici',
            'customer_name' => 'T************',
            'success' => true,
        ];

        $response =  $this->makeRequestAndGetContent($request);

        $this->assertEquals($toAssertResponse, $response);

    }

    public function testValidateVpaNumericFailure()
    {
        $this->fixtures->merchant->addFeatures(['enable_vpa_validate']);

        $input = [
            'entity' => 'vpa',
            'value' => '77777777',
        ];

        $request = [
            'content' => $input,
            'url'     => '/v1/payments/validate/account',
            'method'  => 'post'
        ];

        $this->ba->publicAuth();


        $this->makeRequestAndCatchException(
            function() use ($request)
            {
                $this->makeRequestAndGetContent($request);
            },
            \RZP\Exception\GatewayErrorException::class,
            'Invalid UPI Number. Please enter a valid UPI Number'. PHP_EOL .
            'Gateway Error Code: 1038'. PHP_EOL .
            'Gateway Error Desc: Invalid UPI number'
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
     * Test timeout of collect payment with
     * created timestamp more than expiry
     * @return void
     */
    public function testCollectPaymentTimeoutWithMerchantExpiry(): void
    {
        $this->payment['description'] = 'create_collect_success';

        $this->fixtures->merchant->addFeatures(['s2supi']);

        // Setting the collect expiry to 20 mins
        $this->payment['upi']['expiry_time'] = 20;

        $response = $this->doS2sUpiPayment($this->payment);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
                Entity::STATUS => 'created',
                Entity::REFUND_AT => null,
                Entity::CPS_ROUTE => Entity::UPI_PAYMENT_SERVICE,
            ], $payment->toArray()
        );

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'enable_timeout_on_upi_collect_expiry', 'on');
        });

        $createdAt = time();

        // Updating the payment created timestamp to 14 < 20(expiry time) min old to test the time out behaviour
        $this->fixtures->edit('payment', $payment->getId(), ['created_at' => $createdAt - 14*60]);

        $this->timeoutOldPayment();

        $paymentNew = $this->getDbLastPayment();

        $this->assertEquals('created', $paymentNew->getStatus());

        $this->assertNull($upiEntity);
    }

    /**
     * Test timeout of collect payment without
     * expiry time enabled
     * @return void
     */
    public function testCollectPaymentTimeoutWithoutMerchantExpiry(): void
    {
        $this->payment['description'] = 'create_collect_success';

        $this->fixtures->merchant->addFeatures(['s2supi']);

        // Setting the collect expiry to 20 mins
        $this->payment['upi']['expiry_time'] = 14;

        $response = $this->doS2sUpiPayment($this->payment);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
                Entity::STATUS => 'created',
                Entity::REFUND_AT => null,
                Entity::CPS_ROUTE => Entity::UPI_PAYMENT_SERVICE,
            ], $payment->toArray()
        );

        $upiMetadata = $this->getDbLastEntity('upi_metadata');

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $createdAt = time();

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'enable_timeout_on_upi_collect_expiry', 'on');
        });

        // Updating the payment created timestamp to 14 < 20(expiry time) min old to test the time out behaviour
        $this->fixtures->edit('payment', $payment->getId(), ['created_at' => $createdAt - 20 * 60]);

        $this->timeoutOldPayment();

        $paymentNew = $this->getDbLastPayment();

        $this->assertEquals('failed', $paymentNew->getStatus());

        $this->assertEquals('BAD_REQUEST_PAYMENT_TIMED_OUT', $paymentNew->getInternalErrorCode());

        $this->assertNull($upiEntity);

    }
    /**
     * Test verify failed payments
     * @return void
     */
    public function testVerifyFailedPayment()
    {
        $this->testMozartFailure();

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
                Entity::STATUS              => Status::FAILED,
                Entity::GATEWAY             => 'upi_airtel',
                Entity::TERMINAL_ID         => $this->terminal->getId(),
                Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
                Entity::ERROR_CODE          => 'GATEWAY_ERROR',
                Entity::INTERNAL_ERROR_CODE => 'GATEWAY_ERROR_ENCRYPTION_ERROR',
            ], $payment->toArray()
        );

        $payment = $this->verifyPayment($payment->getPublicId());

        $this->assertSame($payment['payment']['verified'], 1);

        $this->assertArraySubset(
            [
                Entity::STATUS              => Status::FAILED,
                Entity::GATEWAY             => 'upi_airtel',
                Entity::TERMINAL_ID         => $this->terminal->getId(),
                Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
                Entity::ERROR_CODE          => 'GATEWAY_ERROR',
                Entity::INTERNAL_ERROR_CODE => 'GATEWAY_ERROR_DEBIT_FAILED',
            ], $payment['payment']
        );
    }

    /**
     * Test verify created payments
     * @return void
     */
    public function testVerifyCreatedPayment()
    {
        $payment = $this->payment;

        $payment['description'] =  'create_collect_success';

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $this->assertEquals('async', $response['type']);

        $this->assertArrayHasKey('vpa', $response['data']);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
                Entity::STATUS          => Status::CREATED,
                Entity::GATEWAY         => 'upi_airtel',
                Entity::TERMINAL_ID     => $this->terminal->getId(),
                Entity::REFUND_AT       => null,
                Entity::CPS_ROUTE       => Entity::UPI_PAYMENT_SERVICE,
            ], $payment->toArray()
        );

        $payment->setDescription('mozart_failure');

        $payment->saveOrFail();

        $payment = $this->verifyPayment($payment->getPublicId());

        $this->assertSame($payment['payment']['verified'], 1);

        $this->assertArraySubset(
            [
                Entity::STATUS              => Status::CREATED,
                Entity::GATEWAY             => 'upi_airtel',
                Entity::TERMINAL_ID         => $this->terminal->getId(),
                Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
                Entity::ERROR_CODE          => null,
                Entity::INTERNAL_ERROR_CODE => null,
            ], $payment['payment']
        );
    }

    /**
     * Test verify authorzied payments
     * @return void
     */
    public function testVerifyAuthorizedPayment()
    {
        $payment = $this->payment;

        $payment['description'] =  'create_collect_success';

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $this->assertEquals('async', $response['type']);

        $this->assertArrayHasKey('vpa', $response['data']);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
                Entity::STATUS          => Status::CREATED,
                Entity::GATEWAY         => 'upi_airtel',
                Entity::TERMINAL_ID     => $this->terminal->getId(),
                Entity::REFUND_AT       => null,
                Entity::CPS_ROUTE       => Entity::UPI_PAYMENT_SERVICE,
            ], $payment->toArray()
        );

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'ups_upi_airtel_pre_process_v1', 'upi_airtel');
        });

        $payment = $this->getDbLastpayment();

        $content = $this->mockServer('upi_airtel')->getAsyncCallbackContent($payment->toArray(),
            $this->terminal->toArray());

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_airtel');

        $payment = $this->getDbLastPayment();

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

        $payment->setDescription('mozart_failure');

        $payment->saveOrFail();

        $request = array(
            'url'    => '/payments/'.$payment->getPublicId().'/verify',
            'method' => 'GET');

        $this->ba->adminAuth();

        $this->makeRequestAndCatchException(function() use ($request) {
            $this->makeRequestAndGetContent($request);
        }, PaymentVerificationException::class);

        $payment->reload();

        $this->assertArraySubset(
            [
                Entity::STATUS              => Status::AUTHORIZED,
                Entity::GATEWAY             => 'upi_airtel',
                Entity::TERMINAL_ID         => $this->terminal->getId(),
                Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
                Entity::VERIFIED            => 0,
                Entity::ERROR_CODE          => null,
                Entity::INTERNAL_ERROR_CODE => null
            ], $payment->toArray()
        );
    }

    /**
     * Test verify captured payments
     * @return void
     */
    public function testVerifyCapturedPayment()
    {

        $payment = $this->payment;

        $payment['description'] =  'create_collect_success';

        $response = $this->doAuthPaymentViaAjaxRoute($payment);

        $this->assertEquals('async', $response['type']);

        $this->assertArrayHasKey('vpa', $response['data']);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset(
            [
                Entity::STATUS          => Status::CREATED,
                Entity::GATEWAY         => 'upi_airtel',
                Entity::TERMINAL_ID     => $this->terminal->getId(),
                Entity::REFUND_AT       => null,
                Entity::CPS_ROUTE       => Entity::UPI_PAYMENT_SERVICE,
            ], $payment->toArray()
        );

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'ups_upi_airtel_pre_process_v1', 'upi_airtel');
        });

        $payment = $this->getDbLastpayment();

        $content = $this->mockServer('upi_airtel')->getAsyncCallbackContent($payment->toArray(),
            $this->terminal->toArray());

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_airtel');

        $payment = $this->getDbLastPayment();

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

        $payment = $this->capturePayment($payment->getPublicId(), $payment[Entity::AMOUNT]);

        $this->assertEquals(Status::CAPTURED, $payment[Entity::STATUS]);

        $payment = $this->getDbLastPayment();

        $payment->setDescription('mozart_failure');

        $payment->saveOrFail();

        $request = array(
            'url'    => '/payments/'.$payment->getPublicId().'/verify',
            'method' => 'GET');

        $this->ba->adminAuth();

        $this->makeRequestAndCatchException(function() use ($request) {
            $this->makeRequestAndGetContent($request);
        }, PaymentVerificationException::class);

        $payment->reload();

        $this->assertArraySubset(
            [
                Entity::STATUS              => Status::CAPTURED,
                Entity::GATEWAY             => 'upi_airtel',
                Entity::TERMINAL_ID         => $this->terminal->getId(),
                Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
                Entity::VERIFIED            => 0,
                Entity::ERROR_CODE          => null,
                Entity::INTERNAL_ERROR_CODE => null
            ], $payment->toArray()
        );
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

    protected function doAjaxPaymentWithUps(string $terminalResource, string $gateway)
    {
        if ($this->shouldCreateTerminal === true)
        {
            $this->fixtures->terminal->disableTerminal($this->terminal->getID());

            $this->terminal = $this->fixtures->create($terminalResource);
        }

        $this->gateway = $gateway;

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_'.$this->gateway.'_v1', 'upips');
        });

        return $this->doAuthPaymentViaAjaxRoute($this->payment);
    }

    protected function doAjaxPayment(string $terminalResource, string $gateway)
    {
        $this->fixtures->terminal->disableTerminal($this->terminal->getID());

        $this->terminal = $this->fixtures->create($terminalResource);

        $this->gateway = $gateway;

        $this->doAuthPaymentViaAjaxRoute($this->payment);
    }

    protected function makeUpdatePostReconRequestAndGetContent(array $input)
    {
        $request = [
            'method'  => 'POST',
            'content' => $input,
            'url'     => '/reconciliate/data',
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function createBankAcountForCustomer()
    {

        $this->ba->privateAuth();

        $this->testData['createBankAcountForCustomer'] = [
            'request' => [
                'content' => [
                    "ifsc_code" => "ICIC0001207",
                    'account_number' => '04030403040304',
                    'beneficiary_name'=> 'RATN0000001',
                    "beneficiary_address1"  => "address 1",
                    "beneficiary_address2"  => "address 2",
                    "beneficiary_address3"  => "address 3",
                    "beneficiary_address4"  => "address 4",
                    "beneficiary_email"     => "random@email.com",
                    "beneficiary_mobile"    => "9988776655",
                    "beneficiary_city"      =>"Kolkata",
                    "beneficiary_state"     => "WB",
                    "beneficiary_country"   => "IN",
                    "beneficiary_pin"      =>"123456"
                ],
                'method'    => 'POST',
                'url'       => '/orders',
            ],
            'response' => [
                'content' => [
                    'amount'         => 50000,
                    'currency'       => 'INR',
                    'receipt'        => 'rcptid42',
                ],
            ],
        ];

        return $this->startTest();
    }

    protected function createTpvOrderWithoutBankAccount()
    {
        $this->fixtures->merchant->enableTpv();

        $this->ba->privateAuth();

        $this->testData['createTpvOrderWithoutBankAccount'] = [
            'request' => [
                'content' => [
                    'amount'         => 50000,
                    'currency'       => 'INR',
                    'receipt'        => 'rcptid42',
                    'method'         => 'upi'
                ],
                'method'    => 'POST',
                'url'       => '/orders',
            ],
            'response' => [
                'content' => [
                    'amount'         => 50000,
                    'currency'       => 'INR',
                    'receipt'        => 'rcptid42',
                ],
            ],
        ];

        return $this->startTest();
    }

    protected function createTpvOrder()
    {
        $this->fixtures->merchant->enableTpv();

        $this->ba->privateAuth();

        $this->testData['createTpvOrder'] = [
            'request' => [
                'content' => [
                    'amount'         => 50000,
                    'currency'       => 'INR',
                    'receipt'        => 'rcptid42',
                    'method'         => 'upi',
                    'bank_account'   => [
                        'name'           => 'Test User',
                        'account_number' => '04030403040304',
                        'ifsc'           => 'RATN0000001'
                    ]
                ],
                'method'    => 'POST',
                'url'       => '/orders',
            ],
            'response' => [
                'content' => [
                    'amount'         => 50000,
                    'currency'       => 'INR',
                    'receipt'        => 'rcptid42',
                ],
            ],
        ];

        return $this->startTest();
    }

    protected function getTurboPreferences(string $order_id , string $customer_id)
    {
        $this->ba->publicAuth();

        // if both order id and customer id are passed pass both of them
        if($order_id !== '' && $customer_id !== '')
        {
            $this->testData['getTurboPreferences'] = [
                'request'  => [
                    'content' => [
                        'order_id' => $order_id,
                        'customer_id' => $customer_id
                    ],
                    'headers' => [
                        'X-RAZORPAY-VPA-HANDLE' => 'razoraxisolive'
                    ],
                    'method'  => 'POST',
                    'url'     => '/upi/turbo/preferences',
                ],
                'response' => [
                    'content' => [
                    ],
                ],
            ];
        }
        // if only order id is passed pass order id
        else if($order_id !== '')
        {
            $this->testData['getTurboPreferences'] = [
                'request'  => [
                    'content' => [
                        'order_id' => $order_id
                    ],
                    'headers' => [
                        'X-RAZORPAY-VPA-HANDLE' => 'razoraxisolive'
                    ],
                    'method'  => 'POST',
                    'url'     => '/upi/turbo/preferences',
                ],
                'response' => [
                    'content' => [
                    ],
                ],
            ];
        }
        // if customer id is the only thing that is passed pass only customer id
        else if($customer_id !== '')
        {
            $this->testData['getTurboPreferences'] = [
                'request'  => [
                    'content' => [
                        'customer_id' => $customer_id
                    ],
                    'headers' => [
                        'X-RAZORPAY-VPA-HANDLE' => 'razoraxisolive'
                    ],
                    'method'  => 'POST',
                    'url'     => '/upi/turbo/preferences',
                ],
                'response' => [
                    'content' => [
                    ],
                ],
            ];
        }
        else{
            $this->testData['getTurboPreferences'] = [
                'request'  => [
                    'content' => [
                    ],
                    'headers' => [
                        'X-RAZORPAY-VPA-HANDLE' => 'razoraxisolive'
                    ],
                    'method'  => 'POST',
                    'url'     => '/upi/turbo/preferences',
                ],
                'response' => [
                    'content' => [
                    ],
                ],
            ];
        }
        return $this->startTest();
    }

    protected function createUpsUploadedFile($file, $fileName = 'file.xlsx', $mimeType = null)
    {
        $this->assertFileExists($file);

        $mimeType = $mimeType ?? 'text/csv';
        $fileName = ($fileName == 'file.xlsx') ? $file : $fileName;

        $uploadedFile = new UploadedFile(
            $file,
            $fileName,
            $mimeType,
            null,
            true
        );

        return $uploadedFile;
    }

    protected function mockServerGatewayContentFunction($closure, $gateway = null)
    {
        $server = $this->mockServer($gateway)
                       ->shouldReceive('content')
                       ->andReturnUsing($closure)
                       ->mock();

        $this->setMockServer($server, $gateway);

        return $server;
    }

    protected function mockServerGatewayRequestFunction($closure, $gateway = null)
    {
        $server = $this->mockServer($gateway)
                       ->shouldReceive('request')
                       ->andReturnUsing($closure)
                       ->mock();

        $this->setMockServer($server, $gateway);

        return $server;
    }
}
