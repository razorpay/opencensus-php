<?php

namespace RZP\Tests\Functional\PaymentsUpi\Service;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;
use Illuminate\Http\UploadedFile;
use RZP\Models\Payment\UpiMetadata\Flow;
use RZP\Models\Batch\Status as BatchStatus;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Exception\PaymentVerificationException;


class UpiIciciPaymentServiceTest extends UpiPaymentServiceTest
{
    use BatchTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = 'upi_icici';

        $this->testData['testRefundUpiEntity'] = [
            'action'                => 'refund',
            'amount'                => 50000,
            'bank'                  => 'ICIC',
            'acquirer'              => 'icici',
            'received'              => true,
            'gateway_data'          => null,
            'contact'               => null,
            'gateway_merchant_id'   => '123456',
            'npci_reference_id'     => '836416213628',
            'status_code'           => '0',
            'vpa'                   => 'vishnu@icici',
            'provider'              => 'icici',
            'entity'                => 'upi',
        ];
    }

    public function testNonRearchPaymentSuccessWithApiPreProcess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPayment('terminal:shared_upi_icici_terminal', 'upi_icici');

        $this->gateway = 'upi_icici';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(0, $payment->getCpsRoute());

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_icici_pre_process_v1', 'upi_icici');
        });

        $payment = $this->getDbLastPayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $upiEntity = $upiEntity = $this->getLastEntity('upi', true);

        $content = $this->mockServer('upi_icici')->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_icici');

        $payment = $this->getDbLastPayment()->toArray();

        $this->assertArraySubset([
            Entity::STATUS          => Status::AUTHORIZED,
            Entity::REFERENCE16     => $upiEntity['npci_reference_id'],
            Entity::TERMINAL_ID     => $this->terminal->getId(),
            Entity::GATEWAY         => $this->gateway
        ], $payment);
    }
    public function testPaymentFailureWithApiPreProcess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPayment('terminal:shared_upi_icici_terminal', 'upi_icici');

        $this->gateway = 'upi_icici';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(0, $payment->getCpsRoute());

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_icici_pre_process_v1', 'upi_icici');
        });

        $payment = $this->getDbLastPayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $upiEntity = $upiEntity = $this->getLastEntity('upi', true);

        $content = $this->mockServer('upi_icici')->getFailedAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_icici');

        $payment = $this->getDbLastPayment()->toArray();

        $upiEntity = $this->getDbLastUpi();

        $this->assertArraySubset([
            Entity::STATUS              => Status::FAILED,
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::GATEWAY             => $this->gateway,
            Entity::CPS_ROUTE           => 0,
            Entity::ERROR_CODE          => 'GATEWAY_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'GATEWAY_ERROR_DEBIT_FAILED',
        ], $payment);

        $this->assertArraySubset([
            UpiEntity::TYPE          => Flow::COLLECT,
            UpiEntity::ACTION        => 'authorize',
            UpiEntity::GATEWAY       => $this->gateway,
            UpiEntity::STATUS_CODE   => 'U30'
        ], $upiEntity->toArray());
    }

    public function testUpsPaymentSuccess($description = 'create_collect_success')
    {
        $this->gateway = 'upi_mozart';

        $this->payment['description'] = $description;

        $this->setMockGatewayTrue();

        $this->doAjaxPaymentWithUps('terminal:shared_upi_icici_terminal', 'upi_icici');

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $this->gateway = 'upi_icici';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(4, $payment->getCpsRoute());

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_icici_pre_process_v1', 'upi_icici');
        });

        $payment = $this->getDbLastPayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $upiEntity = [];
        $upiEntity['created_at'] = $payment['created_at'];
        $upiEntity['gateway_payment_id'] = '882087011';
        $upiEntity['gateway_merchant_id'] = '123456';
        $upiEntity['vpa'] =  'vishnu@icici';
        $upiEntity['payment_id'] = $payment['id'];

        $content = $this->mockServer('upi_icici')->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_icici');

        $payment = $this->getDbLastPayment()->toArray();

        $this->assertArraySubset([
            Entity::STATUS          => Status::AUTHORIZED,
            Entity::TERMINAL_ID     => $this->terminal->getId(),
            Entity::GATEWAY         => $this->gateway,
            Entity::CPS_ROUTE       => 4,
        ], $payment);
    }

    /**
     * @param string $variant
     * @param bool $shouldRouteViaPgRouter
     * @return mixed
     *
     * @dataProvider testPaymentS2SDataProvider
     */
    public function testPaymentS2S(string $variant, bool $shouldRouteViaPgRouter)
    {
        $this->fixtures->merchant->addFeatures(['s2supi']);

        $payment = $this->getDefaultUpiPaymentArray();

        $this->setRazorxMock(function ($mid, $feature, $mode) use ($variant)
        {
            return $this->getRazoxVariant($feature, 'allow_merchants_on_rearch_ups_v2_s2s', $variant);
        });

        $routedViaPgRouter  = false;
        $this->pgService->shouldReceive('sendRequest')
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout) use (&$routedViaPgRouter) {
                if ($method === 'GET')
                {
                    return [
                        'body' => [
                            "data" => [
                                "payment" => [
                                    'id' =>'GfnS1Fj048VHo2',
                                    'merchant_id' =>'10000000000000',
                                    'amount' =>50000,
                                    'fees' => 0,
                                    'currency' =>'INR',
                                    'base_amount' =>50000,
                                    'method' =>'upi',
                                    'status' =>'created',
                                    'two_factor_auth' =>'not_applicable',
                                    'order_id' => NULL,
                                    'invoice_id' => NULL,
                                    'transfer_id' => NULL,
                                    'payment_link_id' => NULL,
                                    'receiver_id' => NULL,
                                    'receiver_type' => NULL,
                                    'international' =>FALSE,
                                    'amount_authorized' =>50000,
                                    'amount_refunded' =>0,
                                    'base_amount_refunded' =>0,
                                    'amount_transferred' =>0,
                                    'amount_paidout' =>0,
                                    'refund_status' => NULL,
                                    'description' =>'description',
                                    'bank' => NULL,
                                    'wallet' => NULL,
                                    'vpa' => 'vishnu@icici',
                                    'on_hold' =>FALSE,
                                    'on_hold_until' => NULL,
                                    'emi_plan_id' => NULL,
                                    'emi_subvention' => NULL,
                                    'error_code' => NULL,
                                    'internal_error_code' => NULL,
                                    'error_description' => NULL,
                                    'global_customer_id' => NULL,
                                    'app_token' => NULL,
                                    'global_token_id' => NULL,
                                    'email' =>'a@b.com',
                                    'contact' =>'+919918899029',
                                    'notes' =>[
                                        'merchant_order_id' =>'id',
                                    ],
                                    'authorized_at' => 0,
                                    'auto_captured' =>FALSE,
                                    'captured_at' => 0,
                                    'gateway' =>'hdfc',
                                    'terminal_id' =>'1n25f6uN5S1Z5a',
                                    'authentication_gateway' => NULL,
                                    'batch_id' => NULL,
                                    'reference1' => NULL,
                                    'reference2' => NULL,
                                    'cps_route' =>5,
                                    'signed' =>FALSE,
                                    'verified' => NULL,
                                    'gateway_captured' =>TRUE,
                                    'verify_bucket' =>0,
                                    'verify_at' =>1614253880,
                                    'callback_url' => NULL,
                                    'fee' =>0,
                                    'mdr' =>0,
                                    'tax' =>0,
                                    'otp_attempts' => NULL,
                                    'otp_count' => NULL,
                                    'recurring' =>FALSE,
                                    'save' =>FALSE,
                                    'late_authorized' =>FALSE,
                                    'convert_currency' => NULL,
                                    'disputed' =>FALSE,
                                    'recurring_type' => NULL,
                                    'auth_type' => NULL,
                                    'acknowledged_at' => NULL,
                                    'refund_at' => NULL,
                                    'reference13' => NULL,
                                    'settled_by' =>'Razorpay',
                                    'reference16' => NULL,
                                    'reference17' => NULL,
                                    'created_at' =>1614253879,
                                    'updated_at' =>1614253880,
                                    'captured' =>TRUE,
                                    'reference2' => '12343123',
                                    'entity' =>'payment',
                                    'fee_bearer' =>'platform',
                                    'error_source' => NULL,
                                    'error_step' => NULL,
                                    'error_reason' => NULL,
                                    'dcc' =>FALSE,
                                    'gateway_amount' =>50000,
                                    'gateway_currency' =>'INR',
                                    'forex_rate' => NULL,
                                    'dcc_offered' => NULL,
                                    'dcc_mark_up_percent' => NULL,
                                    'dcc_markup_amount' => NULL,
                                    'mcc' =>FALSE,
                                    'forex_rate_received' => NULL,
                                    'forex_rate_applied' => NULL,
                                ]
                            ]
                        ]
                    ];
                }

                if ($method === 'POST')
                {
                    if($endpoint === 'v1/payments/create/upi')
                    {
                        $routedViaPgRouter = true;
                    }
                    return [
                        'body' => [
                            "payment_id" =>  'pay_GfnS1Fj048VHo2',
                            "http_status" => 200
                        ],
                        'code' => 200,
                    ];
                }
        });

        $response = $this->doS2SUpiPayment($payment);

        $paymentId = $response['razorpay_payment_id'];

        $this->assertEquals($shouldRouteViaPgRouter, $routedViaPgRouter);
        $this->assertEquals('pay_GfnS1Fj048VHo2', $paymentId);
    }

    public function testPaymentFailure()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPaymentWithUps('terminal:shared_upi_icici_terminal', 'upi_icici');

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $this->gateway = 'upi_icici';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(4, $payment->getCpsRoute());

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_icici_pre_process_v1', 'upi_icici');
        });

        $payment = $this->getDbLastPayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $upiEntity = [];
        $upiEntity['created_at'] = $payment['created_at'];
        $upiEntity['gateway_payment_id'] = '882087011';
        $upiEntity['gateway_merchant_id'] = '123456';
        $upiEntity['vpa'] =  'vishnu@icici';
        $upiEntity['payment_id'] = $payment['id'];

        $this->mockServerContentFunction(
            function (&$error, $action)
            {
                if ($action != 'callback')
                {
                    return;
                }

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

        $content = $this->mockServer('upi_icici')->getFailedAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_icici');

        $payment = $this->getDbLastPayment()->toArray();

        $this->assertArraySubset([
            Entity::STATUS              => Status::FAILED,
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::GATEWAY             => $this->gateway,
            Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
            Entity::ERROR_CODE          => 'GATEWAY_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'GATEWAY_ERROR_DEBIT_FAILED',
        ], $payment);
    }

    public function testPaymentFailureAndDisableTerminal()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPaymentWithUps('terminal:shared_upi_icici_terminal', 'upi_icici');

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $this->gateway = 'upi_icici';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(4, $payment->getCpsRoute());

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_icici_pre_process_v1', 'upi_icici');
        });

        $payment = $this->getDbLastPayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $upiEntity = [];
        $upiEntity['created_at'] = $payment['created_at'];
        $upiEntity['gateway_payment_id'] = '882087011';
        $upiEntity['gateway_merchant_id'] = '123456';
        $upiEntity['vpa'] =  'vishnu@icici';
        $upiEntity['payment_id'] = $payment['id'];

        $this->mockServerContentFunction(
            function (&$error)
            {
                $responseError = [
                'internal' => [
                    'code'          => 'GATEWAY_ERROR_DEBIT_FAILED',
                    'description'   => 'GATEWAY_ERROR',
                    'metadata'      => [
                        'description'               => $error['description'],
                        'gateway_error_code'        => 'U16',
                        'gateway_error_description' => $error['gateway_error_description'],
                        'internal_error_code'       => $error['internal_error_code']
                    ]
                ]
                ];

                return $responseError;
            }
        );

        $content = $this->mockServer('upi_icici')->getFailedAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_icici');

        $payment = $this->getDbLastPayment()->toArray();

        $this->assertArraySubset([
            Entity::STATUS              => Status::FAILED,
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::GATEWAY             => $this->gateway,
            Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
            Entity::ERROR_CODE          => 'GATEWAY_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'GATEWAY_ERROR_DEBIT_FAILED',
        ], $payment);

        $this->assertEquals(true, $this->terminal->isEnabled());

        $this->terminal->reload();

        $this->assertEquals(false, $this->terminal->isEnabled());
    }

    public function testTpvPaymentSuccess()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $order = $this->createTpvOrder();

        $this->payment['amount'] = $order['amount'];
        $this->payment['order_id'] = $order['id'];
        $this->payment['description'] = 'tpv_order_success';

        $this->doAjaxPaymentWithUps('terminal:shared_upi_icici_tpv_terminal', 'upi_icici');

        $this->gateway = 'upi_icici';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(4, $payment->getCpsRoute());

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_icici_pre_process_v1', 'upi_icici');
        });

        $payment = $this->getDbLastPayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $upiEntity = [
            'created_at'            => $payment['created_at'],
            'gateway_payment_id'    => '882087011',
            'gateway_merchant_id'   => '123456',
            'vpa'                   => 'vishnu@icici',
            'payment_id'            => $payment['id'],
        ];

        $content = $this->mockServer('upi_icici')->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_icici');

        $payment = $this->getDbLastPayment()->toArray();

        $terminal = $this->terminal;

        $merchant = $this->fixtures->merchant;

        // assert terminal is tpv
        $this->assertEquals(true, $terminal->isTpvAllowed());

        $this->assertArraySubset([
            Entity::STATUS          => Status::AUTHORIZED,
            Entity::TERMINAL_ID     => $terminal->getId(), // assert terminal id
            Entity::GATEWAY         => $this->gateway,
            Entity::CPS_ROUTE       => 4,
        ], $payment);
    }

    public function testTpvPaymentFailure()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $order = $this->createTpvOrder();

        $this->payment['amount'] = $order['amount'];
        $this->payment['order_id'] = $order['id'];
        $this->payment['description'] = 'tpv_order_success';

        $this->doAjaxPaymentWithUps('terminal:shared_upi_icici_tpv_terminal', 'upi_icici');

        $this->gateway = 'upi_icici';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(4, $payment->getCpsRoute());

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_icici_pre_process_v1', 'upi_icici');
        });

        $payment = $this->getDbLastPayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $upiEntity = [
            'created_at'            => $payment['created_at'],
            'gateway_payment_id'    => '882087011',
            'gateway_merchant_id'   => '123456',
            'vpa'                   => 'vishnu@icici',
            'payment_id'            => $payment['id'],
        ];
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

        $content = $this->mockServer('upi_icici')->getFailedAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_icici');

        $payment = $this->getDbLastPayment()->toArray();

        $terminal = $this->terminal;

        $merchant = $this->fixtures->merchant;

        // assert terminal is tpv
        $this->assertEquals(true, $terminal->isTpvAllowed());

        $this->assertArraySubset([
            Entity::STATUS              => Status::FAILED,
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::GATEWAY             => $this->gateway,
            Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
            Entity::ERROR_CODE          => 'GATEWAY_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'GATEWAY_ERROR_DEBIT_FAILED',
        ], $payment);
    }

    public function testFullRefund()
    {
        $this->testUpsPaymentSuccess();

        $payment = $this->getDbLastPayment();

        $payment = $this->getEntityById('payment', $payment->getPublicId(), true);

        $this->capturePayment($payment['id'], 50000);

        $this->mockServerGatewayContentFunction(function(&$content, $action)
        {
            if ($action === 'verify')
            {
                $content['status'] = 'FAILURE';
            }

            if ($action === 'refund')
            {
                $content['originalBankRRN'] = '836416213628';
            }
        });

        $this->mockServerContentFunction(function(&$content)
        {
            $content['entity']['customer_reference'] = '836416213628';
        });

        $this->refundPayment($payment['id']);

        $refund = $this->getLastEntity('refund', true);

        $this->assertNotNull($refund['reference1']);

        $upiEntity = $this->getLastEntity('upi', true);

        $this->assertTestResponse($upiEntity, 'testRefundUpiEntity');
    }

    public function testFullRefundVerifySuccess()
    {
        $this->testUpsPaymentSuccess();

        $payment = $this->getDbLastPayment();

        $payment = $this->getEntityById('payment', $payment->getPublicId(), true);

        $this->capturePayment($payment['id'], 50000);

        $this->refundPayment($payment['id']);

        $upiEntity = $this->getLastEntity('upi', true);

        $this->assertNull($upiEntity);
    }

    /**
     * Test Late Auth Payments
     *
     * @return void
     */
    public function testVerifyLateAuth()
    {
        $this->testPaymentFailure();

        $payment = $this->getDbLastPayment();

        $time = Carbon::now(Timezone::IST)->addMinutes(4);

        Carbon::setTestNow($time);

        $this->verifyAllPayments();

        $payment->reload();

        $this->assertTrue($payment->isLateAuthorized());

        $this->assertArraySubset(
            [
            Entity::STATUS          => Status::AUTHORIZED,
            Entity::GATEWAY         => 'upi_icici',
            Entity::TERMINAL_ID     => $this->terminal->getId(),
            Entity::CPS_ROUTE       => Entity::UPI_PAYMENT_SERVICE,
            ], $payment->toArray()
        );

        $upiEntity = $this->getDbLastEntity('upi', 'test');

        $this->assertNull($upiEntity);
    }

    public function testRetryRefund()
    {
        $this->testUpsPaymentSuccess();

        $payment = $this->getDbLastPayment();

        $payment = $this->getEntityById('payment', $payment->getPublicId(), true);

        $this->capturePayment($payment['id'], 50000);

        $refundAmount = 30000;

        $this->mockServerGatewayContentFunction(function(&$content, $action)
        {
            if ($action === 'refund')
            {
                $content['status'] = 'FAILURE';
            }
        });

        $refund = $this->refundPayment($payment['id']);

        $this->mockServerGatewayContentFunction(function(&$content, $action)
        {
            if ($action === 'refund')
            {
                $content['status'] = 'SUCCESS';
            }
        });

        $refund = $this->retryFailedRefund($refund['id'], $refund['payment_id']);

        $this->assertEquals($refund['status'], 'processed');
    }

    /**
     * Test verify amount mismatch
     *
     * @return void
     */
    public function testVerifyAmountMisMatch()
    {
        $this->testUpsPaymentSuccess('verify_amount_mismatch');

        $payment = $this->getDbLastPayment();

        $this->assertSame(Status::AUTHORIZED, $payment->getStatus());

        $this->expectException(PaymentVerificationException::class);

        $this->verifyPayment($payment->getPublicId());
    }

    /**
     * Test verify amount mismatch
     *
     * @return void
     */
    public function testVerifyAmountMisMatchSuccess()
    {
        $this->doAjaxPaymentWithUps('terminal:shared_upi_icici_terminal', 'upi_icici');

        $payment = $this->getDbLastPayment();

        $this->assertSame(Status::CREATED, $payment->getStatus());

        $payment->setVerifyAt(now()->getTimestamp());
        $payment->setDescription('verify_amount_mismatch');
        $payment->save();

        $response = $this->verifyAllPayments($payment->getPublicId());

        $this->assertArraySubset([
            'authorized'    => 0,
            'success'       => 0,
            'error'         => 1,
            'unknown'       => 0,
        ], $response);
    }

    public function testAmountDeficitOnSuccessfulWithVerify()
    {
        $this->doAjaxPaymentWithUps('terminal:shared_upi_icici_terminal', 'upi_icici');

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            'amount'        => 50000,
            'base_amount'   => 50000,
            'status'        => 'created',
        ], $payment->toArray(), true);

        $payment->setVerifyAt(now()->getTimestamp());
        $payment->setDescription('verify_amount_mismatch');
        $payment->save();

        config()->set('app.amount_difference_allowed_authorized', [$payment->getMerchantId()]);

        $this->mockServerContentFunction(function (&$content){
            $content['data']['data']['payment']['amount_authorized'] = 49000;
        });

        $response = $this->verifyAllPayments($payment->getPublicId());

        $this->assertArraySubset([
            'authorized'    => 1,
            'success'       => 0,
            'error'         => 0,
            'unknown'       => 0,
        ], $response);

        $payment->refresh();

        $this->assertArraySubset([
            'amount'            => 49000,
            'base_amount'       => 49000,
            'status'            => 'authorized',
            'verified'          => 0,
            'vpa'               => 'vishnu@icici',
            'late_authorized'   => true,
        ], $payment->toArray(), true);

        $this->assertArraySubset([
            'gateway_amount'            => 49000,
            'mismatch_amount'           => 1000,
            'mismatch_amount_reason'    => 'credit_deficit',
        ], $payment->paymentMeta->toArray(), true);
    }

    public function testPaymentReconciliation()
    {
        $this->gateway = 'upi_icici';

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '734122607521';

        $this->makeUpiIciciPaymentsSince($createdAt, $rrn, 1);

        $this->ba->h2hAuth();

        $fileContents = $this->generateReconFile(['type' => 'payment']);

        $uploadedFile = $this->createIciciUploadedFile($fileContents['local_file_path']);

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {
            $this->assertNull($payment['reference16']);
        }

        $upiEntity = $this->getDbLastEntity('upi');

        $this->fixtures->edit(
            'upi',
            $upiEntity['id'],
            [
                'payment_id' => 'invalid',
            ]
        );

        $this->reconcile($uploadedFile, 'UpiIcici');

        $payments = $this->getEntities('payment', [], true);

        foreach ($payments['items'] as $payment)
        {

            $this->assertEquals(true, $payment['gateway_captured']);

            $transactionId = $payment['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            $this->assertNotNull($transaction['reconciled_at']);

            $this->assertNotNull($payment['reference16']);
        }

        $this->assertBatchStatus(BatchStatus::PROCESSED);
    }

    public function testPaymentReconWithSyncUpdate()
    {
        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'upi_icici_recon_sync_update', 'upi_icici');
        });

        $this->testPaymentReconciliation();
    }

    public function testPaymentIdAbsentReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '734122607521';

        // We make just one payment
        $this->makeUpiIciciPaymentsSince($createdAt, $rrn, 1);

        $this->ba->h2hAuth();

        $this->mockReconContentFunction(
            function(&$content, $action = null)
            {
                if ($action === 'col_payment_icici_recon')
                {
                    $content['banktranid'] = '';
                }
            },
            $this->gateway,
            [
                'type' => 'payment'
            ]);

        $this->assertFailedPaymentRecon();
    }

    public function testUpiIciciForceAuthorizePayment()
    {
        $this->gateway = 'upi_icici';

        $this->testData = [
            'upiIcici' => [
                'accountNumber'   => '000205025290',
                'merchantID'      => '116798',
                'merchantName'    => 'RAZORPAY',
                'subMerchantID'   => '116798',
                'subMerchantName' => 'Razorpay SUB',
                'merchantTranID'  => 'EqLhm2zHgYQ1Mx',
                'bankTranID'      => '734122607521',
                'date'            => '03/07/2020',
                'time'            => '08:27 PM',
                'amount'          => 500,
                'payerVA'         => '9619218329@ybl',
                'status'          => 'SUCCESS',
                'Commission'      => '0',
                'Net amount'      => '0',
                'Service tax'     => '0',
            ],
        ];

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '734122607521';

        $this->makeUpiIciciPaymentsSince($createdAt, $rrn, 1);

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $this->fixtures->payment->edit($upiEntity['payment_id'],
            [
                'status'                => 'failed',
                'authorized_at'         => null,
                'error_code'            => 'BAD_REQUEST_ERROR',
                'internal_error_code'   => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'     => 'Payment was not completed on time.',
            ]);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('failed', $payment['status']);

        $entries[] = $this->overrideUpiIciciPayment($upiEntity);

        $file = $this->writeToExcelFile($entries, 'mis_report','files/settlement','Recon MIS');

        $uploadedFile = $this->createIciciUploadedFile($file);

        $upiEntity = $this->getDbLastEntity('upi');

        $this->fixtures->edit(
            'upi',
            $upiEntity['id'],
            [
                'payment_id' => 'invalid',
            ]
        );

        $this->reconcile($uploadedFile, 'UpiIcici');

        $payments = $this->getEntities('payment', [], true);

        $payment = $payments['items'][0];

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        $this->assertNotNull($transaction['reconciled_at']);

        $this->assertNotNull($payment['reference16']);
    }

    public function testUpiIciciRrnMismatchPayment()
    {
        $this->testData = [
            'upiIcici' => [
                'accountNumber'   => '000205025290',
                'merchantID'      => '116798',
                'merchantName'    => 'RAZORPAY',
                'subMerchantID'   => '116798',
                'subMerchantName' => 'Razorpay SUB',
                'merchantTranID'  => 'EqLhm2zHgYQ1Mx',
                'bankTranID'      => '734122607521',
                'date'            => '03/07/2020',
                'time'            => '08:27 PM',
                'amount'          => 500,
                'payerVA'         => '9619218329@ybl',
                'status'          => 'SUCCESS',
                'Commission'      => '0',
                'Net amount'      => '0',
                'Service tax'     => '0',
            ],
        ];

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '734122607521';

        $this->makeUpiIciciPaymentsSince($createdAt, $rrn, 1);

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $this->fixtures->payment->edit($upiEntity['payment_id'],
            [
                'status' => 'failed',
                'authorized_at' => null,
                'error_code' => 'BAD_REQUEST_ERROR',
                'internal_error_code' => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description' => 'Payment was not completed on time.',
            ]);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('failed', $payment['status']);

        $entries[] = $this->overrideUpiIciciPayment($upiEntity, '123456789');

        $file = $this->writeToExcelFile($entries, 'mis_report', 'files/settlement', 'Recon MIS');

        $uploadedFile = $this->createIciciUploadedFile($file);

        $this->fixtures->edit(
            'upi',
            $upiEntity['id'],
            [
                'payment_id' => 'invalid',
            ]
        );

        $this->reconcile($uploadedFile, 'UpiIcici');

        $payments = $this->getEntities('payment', [], true);

        $payment = $payments['items'][0];

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        $this->assertNotNull($transaction['reconciled_at']);

        $this->assertNotNull($payment['reference16']);
    }

    public function testMultipleRrn()
    {
        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);
        $this->fixtures->merchant->enableUpi(Account::DEMO_ACCOUNT);

        $this->terminal = $this->fixtures->create('terminal:shared_upi_icici_terminal');

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        // This one will not get reconciled, this is the easier way
        // we can create a recon file with multiple credits
        $payments['000000000003'] = $this->makeUpiIciciPaymentsSince($createdAt, '000000000003', 1)[0];

        // First lets create 2 payments in the system
        $payments['000000000002'] = $this->makeUpiIciciPaymentsSince($createdAt, '000000000002', 1)[0];
        $payments['000000000001'] = $this->makeUpiIciciPaymentsSince($createdAt, '000000000001', 1)[0];

        // Mark reconciled_at of entity fetch response for multiple rrn scenario
        $this->mockServerContentFunction(function (&$content) use ($payments)
        {
            if ($content['entity']['payment_id'] === $payments['000000000001'])
            {
                $content['entity']['reconciled_at'] =  Carbon::now(Timezone::IST)->getTimestamp();
                $content['entity']['customer_reference'] = '000000000001';
            }
            else if ($content['entity']['payment_id'] === $payments['000000000002'])
            {
                $content['entity']['reconciled_at'] =  Carbon::now(Timezone::IST)->getTimestamp();
                $content['entity']['customer_reference'] = '000000000002';
            }
            else if ($content['entity']['payment_id'] === $payments['000000000003'])
            {
                $content['entity']['customer_reference'] = '000000000003';
            }
        });

        // Order of payments is important as the last one created will reconcile first
        $uploadedFile = $this->reconcileWithMock(
            function(&$content) use ($payments)
            {
                $actualRrn = array_search($content['merchantTranID'], $payments);

                // Correct the Gateway Merchant Id in file
                $content['merchantid'] = $this->terminal->getGatewayMerchantId();

                // Correct the RRN in the recon row
                $content['bankTranID'] = $actualRrn;

                // For the third row, change the payment id so it will become multiple rrn case
                if ($actualRrn === '000000000003')
                {
                    $content['merchantTranID']  = $payments['000000000001'];
                    $content['amount']          = '500.01';
                }
            });

        $this->unlinkUpiEnity('000000000003');
        $this->unlinkUpiEnity('000000000002');
        $this->unlinkUpiEnity('000000000001');

        $this->reconcile($uploadedFile, 'UpiIcici');

        // This is the payment which must have been created
        $payment = $this->getDbLastPayment();

        // Now we can make sure that this is the new payment created
        $this->assertFalse(in_array($payment->getId(), $payments, true));

        $this->assertArraySubset([
            Entity::MERCHANT_ID => Account::DEMO_ACCOUNT,
            Entity::AMOUNT      => 50001,
            Entity::VPA         => '9619218329@ybl',
            Entity::STATUS      => Status::AUTHORIZED,
        ], $payment->toArray(), true);

        $this->assertSame('000000000003', $payment->getReference16());

        $this->assertNotEmpty($payment->transaction->getReconciledAt());

        $reconciled1 = $this->getDbEntity('transaction', ['entity_id' => $payments['000000000001']]);
        $this->assertNotEmpty($reconciled1->getReconciledAt());

        $reconciled2 = $this->getDbEntity('transaction', ['entity_id' => $payments['000000000002']]);
        $this->assertNotEmpty($reconciled2->getReconciledAt());

        // The payment with RRN
        $reconciled3 = $this->getDbEntity('transaction', ['entity_id' => $payments['000000000003']]);
        $this->assertNull($reconciled3->getReconciledAt());
    }

    /*********************************Helpers*********************************/

    protected function unlinkUpiEnity($rrn = '')
    {
        $upiEntity = $this->getDbEntity('upi', [
            'npci_reference_id' => $rrn,
        ]);

        $this->fixtures->edit(
            'upi',
            $upiEntity['id'],
            [
                'payment_id' => 'invalid',
            ]
        );
    }

    public function testDifferentRrn()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        // We will create only one payment
        $paymentId = $this->makeUpiIciciPaymentsSince($createdAt, '000000000001', 1)[0];

        $this->mockServerContentFunction(function (&$content) use ($paymentId)
        {
            if ($content['entity']['payment_id'] === $paymentId)
            {
                $content['entity']['customer_reference'] = '000000000001';
            }
        });

        // Order of payments is important as the last one created will reconcile first
        $uploadedFile = $this->reconcileWithMock(
            function(&$content)
            {
                // Change the RRN from what is saved in database
                $content['bankTranID'] = '000000000002';
            });

        $this->unlinkUpiEnity('000000000001');

        $this->reconcile($uploadedFile, 'UpiIcici');

        $payment = $this->getDbLastPayment();

        // there must only be one payment
        $this->assertSame($paymentId, $payment->getId());
        // RRN must be changed to the updated one
        $this->assertSame('000000000002', $payment->getReference16());

        $this->assertNotEmpty($payment->transaction->getReconciledAt());
    }

    public function testPaymentS2SDataProvider() {
        $data = [];
        $data['route_via_ups'] = ['on', true];
        $data['route_10%_via_ups'] = ['on10', true];
        $data['route_50%_via_ups'] = ['on50', true];

        return $data;
    }

    private function reconcileWithMock(callable $closure = null)
    {
        $this->ba->h2hAuth();

        $this->mockReconContentFunction(
            function(&$content, $action = null) use ($closure)
            {
                if ($action === 'col_payment_icici_recon')
                {
                    if (is_callable($closure) === true)
                    {
                        $closure($content);
                    }
                }
            },
            $this->gateway,
            [
                'type' => 'payment'
            ]);

        $fileContents = $this->generateReconFile(['type' => 'payment']);

        $uploadedFile = $this->createIciciUploadedFile($fileContents['local_file_path']);

        return $uploadedFile;
    }

    private function makeUpiIciciPaymentsSince(int $createdAt, string $rrn, int $count = 3)
    {
        for ($i = 0; $i < $count; $i++)
        {
            $payments[] = $this->doUpiIciciPayment();

            $upiEntity = $this->getDbLastEntity('upi');

            $this->fixtures->edit(
                'upi',
                $upiEntity['id'],
                [
                    'npci_reference_id' => $rrn,
                    'gateway'           => 'upi_icici',
                    'vpa'               => 'test@icici',
                    'bank'              => 'icici',
                    'provider'          => 'icici'
                ]
            );
        }

        foreach ($payments as $payment)
        {
            $this->fixtures->edit('payment', $payment, ['created_at' => $createdAt]);
        }

        return $payments;
    }

    private function doUpiIciciPayment(array $override = [])
    {
        $status = $override['status'] ?? 'captured';

        $attributes = [
            'terminal_id'       => $this->terminal->getId(),
            'method'            => 'upi',
            'amount'            => $this->payment['amount'],
            'base_amount'       => $this->payment['amount'],
            'amount_authorized' => $this->payment['amount'],
            'status'            => $status,
            'gateway'           => $this->gateway,
            'cps_route'         => 4,
            'authorized_at'     => time(),
        ];

        $attributes = array_merge($attributes, $override);

        $payment = $this->fixtures->create('payment', $attributes);

        if ($status !== 'failed')
        {
            $transaction = $this->fixtures->create('transaction', [
                'entity_id' => $payment->getId(),
                'merchant_id' => '10000000000000'
            ]);

            $this->fixtures->edit('payment', $payment->getId(), ['transaction_id' => $transaction->getId()]);
        }

        $this->fixtures->create('upi', [
            'payment_id'    => $payment->getId(),
            'gateway'       => $this->gateway,
            'amount'        => $payment->getAmount(),
        ]);

        return $payment->getId();
    }

    private function createIciciUploadedFile($file)
    {
        $this->assertFileExists($file);

        $mimeType = 'application/octet-stream';

        $uploadedFile = new UploadedFile(
            $file,
            $file,
            $mimeType,
            null,
            true
        );

        return $uploadedFile;
    }

    protected function overrideUpiIciciPayment(array $upiEntity, $gatewayPaymentId = null)
    {
        $facade                   = $this->testData['upiIcici'];

        $facade['amount']         = $upiEntity['amount'] / 100;

        $facade['bankTranID']     = $gatewayPaymentId ?? $upiEntity['npci_reference_id'];

        $facade['merchantTranID'] = $upiEntity['payment_id'];

        return $facade;
    }

    private function assertFailedPaymentRecon()
    {
        $fileContents = $this->generateReconFile(['type' => 'payment']);

        $uploadedFile = $this->createIciciUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiIcici');

        $payment = $this->getLastEntity('payment', true);

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        $this->assertNull($transaction['reconciled_at']);
    }

    protected function mockServerContentFunction($closure)
    {
        $this->upiPaymentService->shouldReceive('content')->andReturnUsing($closure);
    }

    public function testPaymentCreatedForBTUpiICICI()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPaymentWithUps('terminal:shared_upi_icici_terminal', 'upi_icici');

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $this->gateway = 'upi_icici';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(4, $payment->getCpsRoute());

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            if ($feature === 'skip_upi_icici_callback_for_bt')
            {
                return $this->getRazoxVariant($feature, 'skip_upi_icici_callback_for_bt', 'on');
            }
            else
            {
                return $this->getRazoxVariant($feature, 'api_upi_icici_pre_process_v1', 'upi_icici');
            }
        });

        $payment = $this->getDbLastPayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $upiEntity = [];
        $upiEntity['created_at'] = $payment['created_at'];
        $upiEntity['gateway_payment_id'] = '882087011';
        $upiEntity['gateway_merchant_id'] = '123456';
        $upiEntity['vpa'] =  'BT@icici';
        $upiEntity['payment_id'] = $payment['id'];

        $content = $this->mockServer('upi_icici')->getFailedAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_icici');

        $payment = $this->getDbLastPayment()->toArray();

        $this->assertArraySubset([
            Entity::STATUS              => Status::CREATED,
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::GATEWAY             => $this->gateway,
            Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
            Entity::ERROR_CODE          => null,
            Entity::INTERNAL_ERROR_CODE => null,
        ], $payment);
    }

    public function testPaymentFailedForBTUpiICICI()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPaymentWithUps('terminal:shared_upi_icici_terminal', 'upi_icici');

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $this->gateway = 'upi_icici';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(4, $payment->getCpsRoute());

        $this->setRazorxMock(function ($mid, $feature, $mode)

        {
            if ($feature === 'skip_upi_icici_callback_for_bt')
            {
                // Returns variant 'off' for 'skip_upi_icici_callback_for_bt'
                return $this->getRazoxVariant($feature, 'skip_upi_icici_callback_for_bt', 'off');
            }
            else
            {
                return $this->getRazoxVariant($feature, 'api_upi_icici_pre_process_v1', 'upi_icici');
            }
        });

        $payment = $this->getDbLastPayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $upiEntity = [];
        $upiEntity['created_at'] = $payment['created_at'];
        $upiEntity['gateway_payment_id'] = '882087011';
        $upiEntity['gateway_merchant_id'] = '123456';
        $upiEntity['vpa'] =  'BT@icici';
        $upiEntity['payment_id'] = $payment['id'];

        $this->mockServerContentFunction(
            function (&$error, $action)
            {
                if ($action != 'callback')
                {
                    return;
                }

                $responseError = [
                    'internal' => [
                        'code'          => 'GATEWAY_ERROR_TRANSACTION_PENDING',
                        'description'   => 'Transaction is pending (BT)',
                        'metadata'      => [
                            'description'               => 'Transaction is pending (BT)',
                            'gateway_error_code'        => 'BT',
                            'gateway_error_description' => 'Transaction is pending (BT)',
                            'internal_error_code'       => 'GATEWAY_ERROR_TRANSACTION_PENDING'
                        ]
                    ]
                ];

                return $responseError;
            }
        );

        $content = $this->mockServer('upi_icici')->getFailedAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_icici');

        $payment = $this->getDbLastPayment()->toArray();

        $this->assertArraySubset([
            Entity::STATUS              => Status::FAILED,
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::GATEWAY             => $this->gateway,
            Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
            Entity::ERROR_CODE          => 'GATEWAY_ERROR',
            Entity::INTERNAL_ERROR_CODE => 'GATEWAY_ERROR_TRANSACTION_PENDING',
        ], $payment);
    }

    public function testVerifyPaymentCreatedForBTUpiICICI()
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->doAjaxPaymentWithUps('terminal:shared_upi_icici_terminal', 'upi_icici');

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableTPV();

        $this->gateway = 'upi_icici';

        $payment = $this->getDbLastPayment();

        $this->assertEquals(4, $payment->getCpsRoute());

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            if ($feature === 'skip_upi_icici_callback_for_bt')
            {
                return $this->getRazoxVariant($feature, 'skip_upi_icici_callback_for_bt', 'on');
            }
            else
            {
                return $this->getRazoxVariant($feature, 'api_upi_icici_pre_process_v1', 'upi_icici');
            }
        });

        $payment = $this->getDbLastPayment()->toArray();

        $payment['payment_id'] = $payment['id'];

        $upiEntity = [];
        $upiEntity['created_at'] = $payment['created_at'];
        $upiEntity['gateway_payment_id'] = '882087011';
        $upiEntity['gateway_merchant_id'] = '123456';
        $upiEntity['vpa'] =  'BT@icici';
        $upiEntity['payment_id'] = $payment['id'];

        $content = $this->mockServer('upi_icici')->getFailedAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'upi_icici');

        $payment = $this->getDbLastPayment();

        $paymentArr = $payment->toArray();

        $this->assertArraySubset([
            Entity::STATUS              => Status::CREATED,
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::GATEWAY             => $this->gateway,
            Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
            Entity::ERROR_CODE          => null,
            Entity::INTERNAL_ERROR_CODE => null,
        ], $paymentArr);

        $this->verifyAllPayments();

        $paymentArr = $this->getDbLastPayment()->toArray();

        $this->assertArraySubset([
            Entity::STATUS              => Status::CREATED,
            Entity::TERMINAL_ID         => $this->terminal->getId(),
            Entity::GATEWAY             => $this->gateway,
            Entity::CPS_ROUTE           => Entity::UPI_PAYMENT_SERVICE,
            Entity::ERROR_CODE          => null,
            Entity::INTERNAL_ERROR_CODE => null,
        ], $paymentArr);

    }
}
