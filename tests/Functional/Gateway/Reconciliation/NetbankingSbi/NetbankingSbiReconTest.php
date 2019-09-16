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

class NetbankingSbiReconTest extends TestCase
{
    use ReconTrait;
    use BatchTestTrait;

    private $payment;

    private $sharedTerminal;

    protected $method = Payment\Method::NETBANKING;

    protected $bank = IFSC::SBIN;

    public function setUp()
    {
        parent::setUp();

        $this->gateway = Payment\Gateway::NETBANKING_SBI;

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_netbanking_sbi_terminal');
    }

    public function testPaymentReconciliation()
    {
        $payments = $this->makeSbiNbPaymentSince();

        $this->ba->appAuth();

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], 'SBI_Mock_recon.txt');

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

        $this->ba->appAuth();

        $this->mockReconContentFunction(
            function (& $content, $action = null)
            {
                $content[0][3] = 1;
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], 'SBI_Mock_recon.txt');

        $this->reconcile($uploadedFile, Recon::NETBANKING_SBI);

        $netbanking = $this->getLastEntity('netbanking', true);

        $payment = $this->getEntityById('payment', $payment, true);

        $this->assertPaymentReconSkipped($payment, $netbanking);
    }

    public function testReconPaymentFailedReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $payment = $this->makePaymentsSince($createdAt, 1)[0];

        $this->ba->appAuth();

        $this->mockReconContentFunction(
            function (& $content, $action = null)
            {
                $content[0][4] = 'Failure';
            });

        $fileContents = $this->generateReconFile();

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], 'SBI_Mock_recon.txt');

        $this->reconcile($uploadedFile, Recon::NETBANKING_SBI);

        $netbanking = $this->getLastEntity('netbanking', true);

        $payment = $this->getEntityById('payment', $payment, true);

        $this->assertPaymentReconSkipped($payment, $netbanking);
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
            'gateway'           => $this->gateway
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