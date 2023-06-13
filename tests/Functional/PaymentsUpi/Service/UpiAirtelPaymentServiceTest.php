<?php

namespace RZP\Tests\Functional\PaymentsUpi\Service;


use RZP\Exception;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Entity;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\Method;
use RZP\Models\Merchant\Account;

class UpiAirtelPaymentServiceTest extends UpiPaymentServiceTest
{
    protected function setUp(): void
    {
        parent::setUp();
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
     * Test Failed Collect Payment with pre-process through UPS
     * @return void
     */
    public function testCollectPaymentFailure()
    {
        $this->testCollectPaymentCreateSuccess('payment_failed');

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
     * Test Invalid Callback Payload with pre-process through API
     *
     * @dataProvider testCallbackInvalidPayloadProvider
     *
     * @return void
     */
    public function testCallbackInvalidPayload($respOverride)
    {
        $this->gateway = 'upi_mozart';

        $this->setMockGatewayTrue();

        $this->gateway = 'upi_airtel';

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'api_upi_airtel_pre_process_v1', 'upi_airtel');
        });

        $resp = array_merge([
            'amount' => 200.00,
            'mid' => 'MER0000000548542',
            'rrn' => '987654321',
            'txnStatus' => 'SUCCESS',
            'hdnOrderID' => 'L2QOgfMbvTS9PT',
            'messageText' => 'success',
            'code'      => '0',
            'errorCode' => '000',
            'payerVPA'	=> 'vishnu@rzp',
            'txnRefNo'	=> 'FT2129114821982611',
        ], $respOverride);

        $content = json_encode($resp);

        $this->makeRequestAndCatchException(
            function() use ($content)
            {
                $this->makeS2SCallbackAndGetContent($content, 'upi_airtel');
            },
            \RZP\Exception\BadRequestException::class,
            'payload does not contain required keys - gateway_merchant_id and payeeVPA.'
        );
    }

    /**
     *
     */
    public function testCallbackInvalidPayloadProvider()
    {
        return [
            [
                [
                    'payeeVpa' => null,
                ]
            ],
            [
                [
                    'payeeVpa' => '',
                ]
            ],
            [
                [
                    'gateway_merchant_id' => null,
                ]
            ],
            [
                [
                    'gateway_merchant_id' => '',
                ]
            ]
        ];
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

        $payment->setDescription('');

        $payment->saveOrFail();

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

        $uploadedFile = $this->createUpsUploadedFile($fileContents['local_file_path']);

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

        $uploadedFile = $this->createUpsUploadedFile($fileContents['local_file_path']);

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

        $uploadedFile = $this->createUpsUploadedFile($fileContents['local_file_path']);

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

        $uploadedFile = $this->createUpsUploadedFile($fileContents['local_file_path']);

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

    public function testCollectPaymentFailure_BT_UpiAirtel()
    {
        $this->testCollectPaymentCreateSuccess('payment_failed');

        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'ups_upi_airtel_pre_process_v1', 'upi_airtel');
        });

        $this->mockServerContentFunction(
            function (&$error)
            {
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
                Entity::INTERNAL_ERROR_CODE => 'GATEWAY_ERROR_TRANSACTION_PENDING',
            ], $payment->toArray()
        );

        $upiEntity = $this->getDbLastEntity('upi', Mode::TEST);

        $this->assertNull($upiEntity);
    }
}
