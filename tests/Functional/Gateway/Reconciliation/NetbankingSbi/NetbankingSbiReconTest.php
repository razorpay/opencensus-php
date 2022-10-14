<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\NetbankingSbi;

use Carbon\Carbon;
use RZP\Exception;

use RZP\Models\Payment;
use RZP\Services\Scrooge;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Reconciliator\RequestProcessor\Base as Recon;
use RZP\Reconciliator\Base\Foundation\ScroogeReconciliate;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Gateway\Netbanking\Sbi\ReconFields\RefundReconFields;
use RZP\Reconciliator\Base\SubReconciliator\RefundReconciliate;
use RZP\Reconciliator\NetbankingSbi\SubReconciliator\RefundReconciliate as NbSbiRefundReconciliate;

class NetbankingSbiReconTest extends TestCase
{
    use ReconTrait;
    use BatchTestTrait;

    private $payment;

    private $sharedTerminal;

    protected $method = Payment\Method::NETBANKING;

    protected $bank = IFSC::SBIN;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = Payment\Gateway::NETBANKING_SBI;

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_netbanking_sbi_terminal');

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    //----------------------------------------------- Payment Recon ----------------------------------------------------

    public function testPaymentReconciliation()
    {
        $this->makeSbiNbPaymentSince();

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], 'razorpay.txt');

        $this->reconcile($uploadedFile, Recon::NETBANKING_SBI);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(3, $batch['total_count']);
        $this->assertEquals(3, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);

        $netbanking = $this->getEntities('netbanking', [], true);

        foreach ($netbanking['items'] as $netbankingEntity)
        {
            $payment = $this->getEntityById('payment', $netbankingEntity['payment_id'], true);

            $this->assertEquals(true, $payment['gateway_captured']);

            $transactionId = $payment['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            $this->assertNotNull($transaction['reconciled_at']);
        }

        $this->assertEquals(Status::PROCESSED, $batch['status']);
    }

    public function testReconAmountValidationFailure()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $paymentId = $this->makePaymentsSince($createdAt, 1)[0];

        $this->mockReconContentFunction(
            function (& $content, $action = null)
            {
                $content[0][3] = 1;
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], 'razorpay.txt');

        $this->reconcile($uploadedFile, Recon::NETBANKING_SBI);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertPaymentReconSkipped($payment);
    }

    public function testReconPaymentFailedReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $paymentId = $this->makePaymentsSince($createdAt, 1)[0];

        $this->mockReconContentFunction(
            function (& $content, $action = null)
            {
                $content[0][4] = 'Failure';
            });

        $fileContents = $this->generateReconFile();

        // we identify payment / refund recon based on file name - if the name contains Razorpay its
        // a payment recon
        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], 'razorpay.txt');

        $this->reconcile($uploadedFile, Recon::NETBANKING_SBI);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $this->assertPaymentReconSkipped($payment);
    }

    public function testReconPaymentForceAuth()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $paymentId = $this->makePaymentsSince($createdAt, 1)[0];

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], 'razorpay.txt');

        $this->fixtures->payment->edit($paymentId,
            [
                'status'        => 'failed',
                'authorized_at' => null,
                'error_code'    => 'BAD_REQUEST_ERROR',
            ]);

        $this->reconcile($uploadedFile, Recon::NETBANKING_SBI, ['pay_'. $paymentId]);

        $paymentEntity = $this->getDbLastEntity('payment');

        $this->assertEquals(9999999999, $paymentEntity['reference1']);

        $this->assertEquals(9999999999, $paymentEntity['acquirer_data']['bank_transaction_id']);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(1, $batch['total_count']);
        $this->assertEquals(1, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);
        $this->assertEquals(Status::PROCESSED, $batch['status']);
    }

    public function testInvalidNetbankingSbiUpdateReconData()
    {
        $paymentId = $this->makeSbiNbPaymentSince(1);

        $content = $this->getDefaultNetbankingPostReconArray();

        $content['payment_id'] = $paymentId[0];

        unset($content['reconciled_at']);

        $this->makeRequestAndCatchException(function() use ($content)
        {
            $request = [
                'url' => '/reconciliate/data',
                'method' => 'POST',
                'content' => $content,
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }, Exception\BadRequestValidationFailureException::class);
    }

    public function testNetbankingSbiUpdateAlreadyReconciled()
    {
        $paymentId = $this->makeSbiNbPaymentSince(1);

        $transaction = $this->getDbLastEntity('transaction');

        $this->fixtures->edit('transaction', $transaction['id'], ['reconciled_at' => Carbon::now(Timezone::IST)->getTimestamp()]);

        $content = $this->getDefaultNetbankingPostReconArray();

        $content['payment_id'] = $paymentId[0];

        $response = $this->makeUpdatePostReconRequestAndGetContent($content);

        $this->assertFalse($response['success']);

        $this->assertEquals('ALREADY_RECONCILED', $response['error']['code']);
    }

    public function testNetbankingSbiUpdatePostReconData()
    {
        $paymentId = $this->makeSbiNbPaymentSince(1);

        $content = $this->getDefaultNetbankingPostReconArray();

        $content['payment_id'] = $paymentId[0];

        $content['reconciled_at'] = Carbon::now(Timezone::IST)->getTimestamp();

        $response = $this->makeUpdatePostReconRequestAndGetContent($content);

        $transactionEntity = $this->getDbLastEntity('transaction');

        $this->assertNotEmpty($transactionEntity['reconciled_at']);

        $this->assertTrue($response['success']);
    }

    //----------------------------------------------- Refund Recon ----------------------------------------------------

    public function testRefundRecon()
    {
        $refunds = [];

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $paymentIds = $this->makePaymentsSince($createdAt, 2);

        $refunds[] = $this->refundPayment('pay_' . $paymentIds[0], 1000);
        $refunds[] = $this->refundPayment('pay_' . $paymentIds[0], 1000);
        $refunds[] = $this->refundPayment('pay_' . $paymentIds[0], 1000);

        $this->mockReconContentFunction(
            function (& $content, $action = null) use ($paymentIds, $refunds)
            {
                $content = [RefundReconFields::REFUND_COLUMN_HEADERS];

                $payment = $this->getEntityById('payment', $paymentIds[0], true);

                // Failure Response
                $content[] = [
                    $payment['acquirer_data']['bank_transaction_id'],
                    $paymentIds[0],
                    '11111111',
                    Carbon::now()->format('d-m-Y H:i:s'),
                    1,
                    $refunds[0]['amount'] / 100,
                    'Failure',
                    'Failure',
                ];

                // Success Response
                $content[] = [
                    $payment['acquirer_data']['bank_transaction_id'],
                    $paymentIds[0],
                    '12345678',
                    Carbon::now()->format('d-m-Y H:i:s'),
                    2,
                    $refunds[1]['amount'] / 100,
                    'Success',
                    'Completed Successfully',
                ];

                // Declined (no retry)
                $content[] = [
                    $payment['acquirer_data']['bank_transaction_id'],
                    $paymentIds[0],
                    '10000000',
                    Carbon::now()->format('d-m-Y H:i:s'),
                    3,
                    $refunds[2]['amount'] / 100,
                    'Declined',
                    'Failure'
                ];
            });

        $fileContents = $this->generateReconFile();

        // The refund recon file name is a random id generated by SBI
        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], 'randomtext.txt');

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['initiateRefundRecon'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->method('initiateRefundRecon')
                           ->will($this->returnCallback(
                               function ($input)
                               {
                                   $this->assertEquals(Payment\Refund\Status::FAILED, $input[ScroogeReconciliate::REFUNDS][0][ScroogeReconciliate::GATEWAY_KEYS][RefundReconciliate::GATEWAY_STATUS]);
                                   $this->assertEquals(4, $input[ScroogeReconciliate::REFUNDS][0][ScroogeReconciliate::GATEWAY_KEYS][NbSbiRefundReconciliate::SEQUENCE_NO]);
                                   $this->assertEquals('success', $input[ScroogeReconciliate::REFUNDS][1][ScroogeReconciliate::GATEWAY_KEYS][RefundReconciliate::GATEWAY_STATUS]);
                                   $this->assertEquals('declined', $input[ScroogeReconciliate::REFUNDS][2][ScroogeReconciliate::GATEWAY_KEYS][RefundReconciliate::GATEWAY_STATUS]);
                                   $this->assertEquals(5, $input[ScroogeReconciliate::REFUNDS][2][ScroogeReconciliate::GATEWAY_KEYS][NbSbiRefundReconciliate::SEQUENCE_NO]);
                                   $refunds = [];

                                    foreach ($input['refunds'] as $refund)
                                    {
                                        if($refund['status'] !== Payment\Refund\Status::PROCESSED)
                                        {
                                            continue;
                                        }

                                        $refunds[] = [
                                            'arn'               => $refund['arn'],
                                            'gateway_keys'      => [
                                                'arn'               => '12345678910',
                                                'recon_batch_id'    => $input['batch_id']
                                            ],
                                            'reconciled_at'         => 1549108187,
                                            'refund_id'             => $refund['refund_id'],
                                            'status'                => 'processed',
                                            'gateway_settled_at'    => $refund['gateway_settled_at']
                                        ];
                                    }

                                    $response = [
                                        'body' => [
                                            'response' => [
                                                'batch_id'                  => $input['batch_id'],
                                                'chunk_number'              => $input['chunk_number'],
                                                'refunds'                   => $refunds,
                                                'should_force_update_arn'   => $input['should_force_update_arn'],
                                                'source'                    => 'manual',
                                            ]
                                        ]
                                    ];

                                    return $response;
                               }
                           ));

        $this->reconcile($uploadedFile, Recon::NETBANKING_SBI);

        // Failure refund assertions
        $failureRefund = $this->getDbEntityById('refund', $refunds[0]['id']);

        $this->assertTrue($failureRefund['gateway_refunded']);

        $this->assertNull($failureRefund['reference1']);

        $this->assertEquals(4, $failureRefund->getReference3());

        $transaction = $failureRefund->transaction;

        $this->assertNull($transaction['reconciled_at']);

        $this->fixtures->stripSign($refunds[0]['id']);

        $netbankingEntities = ($this->getDbEntities('netbanking', ['refund_id' => $refunds[0]['id']]))->toArray();

        $this->assertCount(1, $netbankingEntities);

        $expectedNetbankingDetails = [
                'status'          => 'sent',
                'bank_payment_id' => null,
                'received'        => false,
                'amount'          => 1000,
                'error_message'   => null,
                'reference1'      => '2',
                'action'          => 'refund'
        ];

        $this->assertArraySelectiveEquals($expectedNetbankingDetails, $netbankingEntities[0]);

        // Success Refund Assertions
        $successRefund = $this->getDbEntityById('refund', $refunds[1]['id']);

        $this->assertTrue($successRefund['gateway_refunded']);

        $transaction = $successRefund->transaction;

        $this->assertNotNull($transaction['reconciled_at']);

        $this->fixtures->stripSign($refunds[1]['id']);

        $netbankingEntities = ($this->getDbEntities('netbanking', ['refund_id' => $refunds[1]['id']]))->toArray();

        $this->assertCount(1, $netbankingEntities);

        // Declined refund assertions
        $declinedRefund = $this->getDbEntityById('refund', $refunds[2]['id']);

        $this->assertNull($declinedRefund['reference1']);

        $this->assertTrue($declinedRefund['gateway_refunded']);

        $this->assertEquals(5, $declinedRefund->getReference3());

        $transaction = $failureRefund->transaction;

        $this->assertNull($transaction['reconciled_at']);

        $this->fixtures->stripSign($refunds[2]['id']);

        $netbankingEntities = ($this->getDbEntities('netbanking', ['refund_id' => $refunds[2]['id']]))->toArray();

        $this->assertCount(1, $netbankingEntities);
    }

    public function testRefundReconAmountMismatch()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $paymentId = $this->makePaymentsSince($createdAt, 1)[0];

        $refund = $this->refundPayment('pay_' . $paymentId);

        $gatewayEntity = $this->getLastEntity('netbanking', true);

        $this->fixtures->edit('netbanking', $gatewayEntity['id'],
            [
                'reference1' => '1',
            ]);

        $this->ba->appAuth();

        $this->mockReconContentFunction(
            function (& $content, $action = null) use ($paymentId, $refund)
            {
                $content = [RefundReconFields::REFUND_COLUMN_HEADERS];

                $payment = $this->getEntityById('payment', $paymentId, true);

                // Success Response
                $content[] = [
                    $payment['acquirer_data']['bank_transaction_id'],
                    $paymentId,
                    '12345678',
                    Carbon::now()->format('d-m-Y H:i:s'),
                    1,
                    $refund['amount'] + 1000,
                    'Success',
                    'Completed Successfully',
                ];
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], 'randomtext.txt');

        $this->reconcile($uploadedFile, Recon::NETBANKING_SBI);

        $refund = $this->getDbEntityById('refund', $refund['id']);

        $transaction = $refund->transaction;

        // Transaction is not reconciled
        $this->assertNull($transaction['reconciled_at']);

        $batch = $this->getDbLastEntityToArray('batch');

        $expectedBatchOutput = [
            'AMOUNT_MISMATCH' => 1
        ];

        $this->assertEquals(Status::PARTIALLY_PROCESSED, $batch['status']);

        $this->assertArraySelectiveEquals($expectedBatchOutput, json_decode($batch['failure_reason'], true));
    }

    public function testRefundReconInvalidStatus()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $paymentId = $this->makePaymentsSince($createdAt, 1)[0];

        $refund = $this->refundPayment('pay_' . $paymentId, 3459);

        $gatewayEntity = $this->getLastEntity('netbanking', true);

        $this->fixtures->edit('netbanking', $gatewayEntity['id'],
            [
                'reference1' =>"1",
            ]);


        $this->mockReconContentFunction(
            function (& $content, $action = null) use ($paymentId, $refund)
            {
                $content = [RefundReconFields::REFUND_COLUMN_HEADERS];

                $payment = $this->getEntityById('payment', $paymentId, true);

                // Success Response
                $content[] = [
                    $payment['acquirer_data']['bank_transaction_id'],
                    $paymentId,
                    '12345678',
                    Carbon::now()->format('d-m-Y H:i:s'),
                    1,
                    $refund['amount'] + 1000,
                    'random',
                    'Completed Successfully',
                ];
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], 'randomtext.txt');

        $scroogeMock = $this->getMockBuilder(Scrooge::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['initiateRefundRecon'])
                            ->getMock();

        $this->app->instance('scrooge', $scroogeMock);

        $this->app->scrooge->expects($this->any())
            ->method('initiateRefundRecon')
            ->will($this->returnCallback(
                function (array $input, bool $throwExceptionOnFailure = false): array{
                    return
                        [
                            'body' => [
                                'response' => [
                                    'batch_id'                  => $input['batch_id'],
                                    'chunk_number'              => $input['chunk_number'],
                                    'refunds'                   => [],
                                    'should_force_update_arn'   => $input['should_force_update_arn'],
                                    'source'                    => 'manual',
                                ]
                            ]
                        ];
                }
            ));

        $this->reconcile($uploadedFile, Recon::NETBANKING_SBI);

        $refund = $this->getDbEntityById('refund', $refund['id']);

        $transaction = $refund->transaction;

        $this->assertNull($transaction['reconciled_at']);

        $batch = $this->getDbLastEntityToArray('batch');

        $expectedBatchOutput = [
            'RECON_ROW_INVALID_FORMAT_FOUND' => 1
        ];

        $this->assertEquals(Status::PARTIALLY_PROCESSED, $batch['status']);

        $this->assertArraySelectiveEquals($expectedBatchOutput, json_decode($batch['failure_reason'], true));
    }

    private function assertPaymentReconSkipped(array $payment)
    {
        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        // Transaction is not reconciled
        $this->assertNull($transaction['reconciled_at']);

        $this->assertBatchStatus(Status::PARTIALLY_PROCESSED);
    }

    protected function makeSbiNbPaymentSince($count = 3)
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $payment = $this->makePaymentsSince($createdAt, $count);

        foreach ($payment as $paymentId)
        {
            $this->fixtures->edit('payment', $paymentId,
                [
                    'bank' => $this->bank,
                ]);

            $this->fixtures->create($this->method,
                [
                    'payment_id'        => $paymentId,
                    'action'            => 'authorize',
                    'bank'              => $this->bank,
                    'caps_payment_id'   => strtoupper($paymentId),
                    'bank_payment_id'   => mt_rand(111111111, 999999999),
                ]);
        }

        return $payment;
    }

    protected final function createPayment()
    {
        $attributes = [
            'terminal_id'       => $this->sharedTerminal->getId(),
            'method'            => $this->method,
            'amount'            => $this->payment['amount'],
            'base_amount'       => $this->payment['amount'],
            'amount_authorized' => $this->payment['amount'],
            'gateway_captured'  => true,
            'status'            => 'captured',
            'gateway'           => $this->gateway,
            'bank'              => $this->bank
        ];

        $payment = $this->fixtures->create('payment', $attributes);

        $transaction = $this->fixtures->create('transaction',
            ['entity_id' => $payment->getId(), 'merchant_id' => '10000000000000']);

        $this->fixtures->edit('payment', $payment->getId(), ['transaction_id' => $transaction->getId()]);

        $this->fixtures->create(
            $this->method,
            [
                'payment_id'      => $payment->getId(),
                'bank_payment_id' => 9999999999,
                'bank'            => IFSC::SBIN,
                'caps_payment_id' => strtoupper($payment->getId()),
                'status'          => 'Y', // Success payments
            ]);

        return $payment->getId();
    }

    private function makeUpdatePostReconRequestAndGetContent(array $content)
    {
        $request = [
            'method'  => 'POST',
            'content' => $content,
            'url'     => '/reconciliate/data',
        ];

        $this->ba->appAuth();

        return $this->makeRequestAndGetContent($request);
    }
}
