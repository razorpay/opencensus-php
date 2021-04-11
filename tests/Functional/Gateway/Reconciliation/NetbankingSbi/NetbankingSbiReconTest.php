<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\NetbankingSbi;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Reconciliator\RequestProcessor\Base as Recon;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;
use RZP\Gateway\Netbanking\Sbi\ReconFields\RefundReconFields;

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
        $payments = $this->makeSbiNbPaymentSince();

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], 'razorpay.txt');

        $this->reconcile($uploadedFile, Recon::NETBANKING_SBI);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(3, $batch['total_count']);
        $this->assertEquals(3, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);

        $netbanking = $this->getEntities('netbanking', [], true);

        foreach ($netbanking['items'] as $id => $netbankingEntity)
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

        $payment = $this->makePaymentsSince($createdAt, 1)[0];


        $this->mockReconContentFunction(
            function (& $content, $action = null)
            {
                $content[0][3] = 1;
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], 'razorpay.txt');

        $this->reconcile($uploadedFile, Recon::NETBANKING_SBI);

        $netbanking = $this->getLastEntity('netbanking', true);

        $payment = $this->getEntityById('payment', $payment, true);

        $this->assertPaymentReconSkipped($payment, $netbanking);
    }

    public function testReconPaymentFailedReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $payment = $this->makePaymentsSince($createdAt, 1)[0];


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

        $netbanking = $this->getLastEntity('netbanking', true);

        $payment = $this->getEntityById('payment', $payment, true);

        $this->assertPaymentReconSkipped($payment, $netbanking);
    }

    public function testReconPaymentForceAuth()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $payment = $this->makePaymentsSince($createdAt, 1)[0];


        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], 'razorpay.txt');

        $this->fixtures->payment->edit($payment,
            [
                'status'        => 'failed',
                'authorized_at' => null,
                'error_code'    => 'BAD_REQUEST_ERROR',
            ]);

        $this->reconcile($uploadedFile, Recon::NETBANKING_SBI, [ 'pay_'. $payment ]);

        $paymentEntity = $this->getDbLastEntity('payment');

        $this->assertEquals($paymentEntity['reference1'], 9999999999);

        $this->assertEquals($paymentEntity['acquirer_data']['bank_transaction_id'], 9999999999);

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(1, $batch['total_count']);
        $this->assertEquals(1, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);
        $this->assertEquals(Status::PROCESSED, $batch['status']);
    }

    //----------------------------------------------- Refund Recon ----------------------------------------------------

    public function testRefundRecon()
    {
        $refunds = [];

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $payments = $this->makePaymentsSince($createdAt, 2);

        $refunds[] = $this->refundPayment('pay_' . $payments[0], 1000);
        $refunds[] = $this->refundPayment('pay_' . $payments[0], 1000);
        $refunds[] = $this->refundPayment('pay_' . $payments[0], 1000);


        $this->mockReconContentFunction(
            function (& $content, $action = null) use ($payments, $refunds)
            {
                $content = [RefundReconFields::REFUND_COLUMN_HEADERS];

                $payment = $this->getEntityById('payment', $payments[0], true);

                // Failure Response
                $content[] = [
                    $payment['acquirer_data']['bank_transaction_id'],
                    $payments[0],
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
                    $payments[0],
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
                    $payments[0],
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

        $this->reconcile($uploadedFile, Recon::NETBANKING_SBI);

        // Failure refund assertions
        $failureRefund = $this->getDbEntityById('refund', $refunds[0]['id']);

        $this->assertNull($failureRefund['reference1']);

        $this->assertNull($failureRefund['gateway_refunded']);

        $this->assertEquals(4, $failureRefund->getReference3());

        $transaction = $failureRefund->transaction;

        $this->assertNull($transaction['reconciled_at']);

        $this->fixtures->stripSign($refunds[0]['id']);

        $netbankingEntities = ($this->getDbEntities('netbanking', ['refund_id' => $refunds[0]['id']]))->toArray();

        $this->assertEquals(2, count($netbankingEntities));

        $expectedNetbankingDetails = [
            [
                'status'          => 'failed',
                'bank_payment_id' => '11111111',
                'received'        => true,
                'amount'          => 1000,
                'error_message'   => 'Failure',
                'reference1'      => '1',
                'action'          => 'refund',
            ],
            [
                'status'          => 'sent',
                'bank_payment_id' => null,
                'received'        => false,
                'amount'          => 1000,
                'error_message'   => null,
                'reference1'      => '4',
                'action'          => 'refund'
            ]
        ];

        $this->assertArraySelectiveEquals($expectedNetbankingDetails, $netbankingEntities);

        // Success Refund Assertions
        $successRefund = $this->getDbEntityById('refund', $refunds[1]['id']);

        $this->assertEquals('12345678', $successRefund['reference1']);

        $this->assertTrue($successRefund['gateway_refunded']);

        $transaction = $successRefund->transaction;

        $this->assertNotNull($transaction['reconciled_at']);

        $this->fixtures->stripSign($refunds[1]['id']);

        $netbankingEntities = ($this->getDbEntities('netbanking', ['refund_id' => $refunds[1]['id']]))->toArray();

        $this->assertEquals(1, count($netbankingEntities));

        $expectedNetbankingDetails = [
            'status'          => 'processed',
            'bank_payment_id' => '12345678',
            'received'        => true,
            'amount'          => 1000,
            'error_message'   => null,
            'reference1'      => '2',
            'action'          => 'refund',
        ];

        $this->assertArraySelectiveEquals($expectedNetbankingDetails, $netbankingEntities[0]);

        // Declined refund assertions
        $declinedRefund = $this->getDbEntityById('refund', $refunds[2]['id']);

        $this->assertNull($declinedRefund['reference1']);

        $this->assertFalse($declinedRefund['gateway_refunded']);

        $this->assertEquals(5, $declinedRefund->getReference3());

        $transaction = $failureRefund->transaction;

        $this->assertNull($transaction['reconciled_at']);

        $this->fixtures->stripSign($refunds[2]['id']);

        $netbankingEntities = ($this->getDbEntities('netbanking', ['refund_id' => $refunds[2]['id']]))->toArray();

        $this->assertEquals(2, count($netbankingEntities));

        $expectedNetbankingDetails = [
            [
                'status'          => 'failed',
                'bank_payment_id' => '10000000',
                'received'        => true,
                'amount'          => 1000,
                'error_message'   => 'Failure',
                'reference1'      => '3',
                'action'          => 'refund',
            ],
            [
                'status'          => 'sent',
                'bank_payment_id' => null,
                'received'        => false,
                'amount'          => 1000,
                'error_message'   => null,
                'reference1'      => '5',
                'action'          => 'refund'
            ]
        ];

        $this->assertArraySelectiveEquals($expectedNetbankingDetails, $netbankingEntities);
    }

    public function testRefundReconAmountMismatch()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $paymentId = $this->makePaymentsSince($createdAt, 1)[0];

        $refund = $this->refundPayment('pay_' . $paymentId);


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

        $this->assertNull($refund['gateway_refunded']);

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

        $refund = $this->refundPayment('pay_' . $paymentId);


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

        $this->reconcile($uploadedFile, Recon::NETBANKING_SBI);

        $refund = $this->getDbEntityById('refund', $refund['id']);

        $this->assertNull($refund['gateway_refunded']);

        $transaction = $refund->transaction;

        $this->assertNull($transaction['reconciled_at']);

        $batch = $this->getDbLastEntityToArray('batch');

        $expectedBatchOutput = [
            'RECON_ROW_INVALID_FORMAT_FOUND' => 1
        ];

        $this->assertEquals(Status::PARTIALLY_PROCESSED, $batch['status']);

        $this->assertArraySelectiveEquals($expectedBatchOutput, json_decode($batch['failure_reason'], true));
    }

    private function assertPaymentReconSkipped(array $payment, array $netbanking)
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
}
