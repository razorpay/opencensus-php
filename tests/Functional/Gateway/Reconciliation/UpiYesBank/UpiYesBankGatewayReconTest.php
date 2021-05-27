<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\UpiYesBank;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Status;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class UpiYesBankGatewayReconTest extends TestCase
{
    use ReconTrait;
    use BatchTestTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_yesbank_terminal');

        $this->gateway = Gateway::UPI_YESBANK;

        $this->setMockGatewayTrue();

        $this->payment = $this->getDefaultUpiPaymentArray();
    }

    public function testUpiYesBankReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $paymentCount = 1;

        $payments = $this->makeUpiYesBankPaymentsSince($paymentCount, $createdAt);

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiYesBank');

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'            => 'reconciliation',
                'gateway'         => 'UpiYesBank',
                'status'          => Status::PROCESSED,
                'total_count'     => $paymentCount,
                'success_count'   => $paymentCount,
                'processed_count' => $paymentCount,
                'failure_count'   => 0,
            ],
            $batch
        );

        foreach ($payments as $payment)
        {
            $this->paymentReconAsserts($payment->toArray());
        }
    }

    private function makeUpiYesBankPaymentsSince(int $count, int $createdAt)
    {
        for ($i = 0; $i < $count; $i++)
        {
            $this->doUpYesBankPayment();
            $payments[] = $this->getDbLastPayment();
        }

        foreach ($payments as $payment)
        {
            $this->fixtures->edit('payment', $payment["id"], ['created_at' => $createdAt]);
        }

        return $payments;
    }

    protected function paymentReconAsserts(array $payment)
    {
        $updatedPayment = $this->getDbEntity('payment', ['id' => $payment["id"]]);

        $this->assertEquals(true, $updatedPayment['gateway_captured']);

        $upi = $this->getDbEntity('upi', ['payment_id' => $updatedPayment['id']]);

        // Assert RRN is updated both in payment and UPI entity
        $this->assertEquals('25700000000', $upi->getNpciReferenceId());
        $this->assertEquals('25700000000', $updatedPayment['reference16']);

        $transactionEntity = $this->getDbEntity('transaction', ['entity_id' => $updatedPayment['id']]);

        $this->assertNotNull($transactionEntity['reconciled_at']);
    }

    private function doUpYesBankPayment()
    {
        $attributes = [
            'terminal_id'       => $this->sharedTerminal->getId(),
            'method'            => 'upi',
            'amount'            => $this->payment['amount'],
            'base_amount'       => $this->payment['amount'],
            'amount_authorized' => $this->payment['amount'],
            'status'            => 'captured',
            'gateway'           => $this->gateway,
            'authorized_at'     => time(),
        ];

        $payment = $this->fixtures->create('payment', $attributes);

        $transaction = $this->fixtures->create('transaction', ['entity_id' => $payment->getId(), 'merchant_id' => '10000000000000']);

        $this->fixtures->edit('payment', $payment->getId(), ['transaction_id' => $transaction->getId()]);

        $this->fixtures->create('upi', ['payment_id' => $payment->getId()]);

        return $payment->getId();
    }
}
