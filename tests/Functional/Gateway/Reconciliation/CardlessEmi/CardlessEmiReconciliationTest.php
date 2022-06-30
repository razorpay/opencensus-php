<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\CardlessEmi;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Batch\Status;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Processor\CardlessEmi;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class CardlessEmiReconciliationTest extends TestCase
{
    use ReconTrait;
    use BatchTestTrait;

    private $payment;

    private $sharedTerminal;

    private $provider;

    private $method = 'cardless_emi';

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = CardlessEmi::FLEXMONEY;

        $this->payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_cardless_emi_terminal');

        $this->gateway = Gateway::CARDLESS_EMI;

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    public function testPaymentReconciliation()
    {
        $this->fixtures->edit('terminal', $this->sharedTerminal->getId(), [
            'gateway_acquirer' => 'flexmoney',
        ]);

        $createdAt = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $payments = $this->makePaymentsSince($createdAt);

        $count = 1;

        foreach ($payments as $paymentId)
        {
            $this->createCardlessEmi($paymentId, 'captured', $count);

            $count++;
        }

        $fileContents = $this->generateReconFile();

        $arr = explode('/', $fileContents['local_file_path']);

        $fileName = $arr[count($arr)-1];

        $uploadedFile = $this->createUploadedFileCsv($fileContents['local_file_path'], $fileName);

        $this->reconcile($uploadedFile, 'CardlessEmiFlexMoney');

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(3, $batch['total_count']);
        $this->assertEquals(3, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);

        foreach ($payments as $paymentId)
        {
            $payment = $this->getEntityById('payment', $paymentId, true);

            $this->assertEquals(true, $payment['gateway_captured']);

            $transactionId = $payment['transaction_id'];

            $transaction = $this->getEntityById('transaction', $transactionId, true);

            $this->assertNotNull($transaction['reconciled_at']);

            $this->assertEquals('800',$transaction['gateway_fee']);

            $this->assertEquals('144',$transaction['gateway_service_tax']);
        }

        $this->assertEquals(Status::PROCESSED, $batch['status']);
    }

    public function testReconAmountValidationFailed()
    {
        $this->fixtures->edit('terminal', $this->sharedTerminal->getId(), [
            'gateway_acquirer' => 'flexmoney',
        ]);

        $createdAt = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $payments = $this->makePaymentsSince($createdAt, 1);

        $this->createCardlessEmi($payments[0], 'captured', 1);

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'payment_recon')
                {
                    // Setting amount to 100 will cause payment amount validation to fail
                    $content['Transaction Amount'] = '200';
                }
            });

        $fileContents = $this->generateReconFile();

        $arr = explode('/', $fileContents['local_file_path']);

        $fileName = $arr[count($arr)-1];

        $uploadedFile = $this->createUploadedFileCsv($fileContents['local_file_path'], $fileName);

        $this->reconcile($uploadedFile, 'CardlessEmiFlexMoney');

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(1, $batch['total_count']);
        $this->assertEquals(0, $batch['success_count']);
        $this->assertEquals(1, $batch['failure_count']);

        $payment = $this->getEntityById('payment', $payments[0], true);

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        $this->assertNull($transaction['reconciled_at']);

        $this->assertEquals(Status::PARTIALLY_PROCESSED, $batch['status']);
    }

    public function testRefundReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $paymentId = $this->makePaymentsSince($createdAt, 1);

        $this->fixtures->edit('payment', $paymentId[0], ['status' => 'captured']);

        $this->createCardlessEmi($paymentId[0], 'captured', 1);

        $this->refundPayment('pay_' . $paymentId[0]);

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'payment_recon')
                {
                    $gatewayRefund = $this->getLastEntity('refund', true);

                    $data = $content;
                    $content = [];
                    $content['PG Refund ID']            = explode('_', $gatewayRefund['id'])[1];
                    $content['Flexpay Transaction ID']  = uniqid();
                    $content['Transaction Amount']      = $gatewayRefund['amount'];
                    $content['Refund Amount']           = $gatewayRefund['amount'] / 100;
                    $content['Refund Date']             = $data['Transaction Date'];
                }
            });

        $fileContents = $this->generateReconFile();

        $arr = explode('/', $fileContents['local_file_path']);

        $fileName = $arr[count($arr)-1];

        $uploadedFile = $this->createUploadedFileCsv($fileContents['local_file_path'], $fileName);

        $this->reconcile($uploadedFile, 'CardlessEmiFlexMoney');

        $batch = $this->getLastEntity('batch', true);

        $this->assertEquals(1, $batch['total_count']);
        $this->assertEquals(1, $batch['success_count']);
        $this->assertEquals(0, $batch['failure_count']);

        $refund = $this->getLastEntity('refund', true);

        $transactionId = $refund['transaction_id'];

        $transaction = $this->getEntityById('transaction', 'txn_' . $transactionId, true);

        $this->assertEquals($transaction['id'], 'txn_' . $transactionId);

        $this->assertEquals(Status::PROCESSED, $batch['status']);
    }

    protected function createCardlessEmi($paymentId, $status, $id)
    {
        $attributes = [
            'id'                    => $id,
            'action'                => 'authorize',
            'gateway'               => 'cardless_emi',
            'amount'                => '100',
            'gateway_reference_id'  => uniqid(),
            'status'                => $status,
            'payment_id'            => $paymentId,
            'provider'              => $this->provider,
            'contact'               => '+919876543212',
        ];

        $netbanking = $this->fixtures->create('cardless_emi', $attributes);

        return $netbanking;
    }

    private function createPayment($content = [])
    {
        $attributes = [
            'terminal_id'       => $this->sharedTerminal->getId(),
            'method'            => $this->method,
            'amount'            => $this->payment['amount'],
            'base_amount'       => $this->payment['amount'],
            'amount_authorized' => $this->payment['amount'],
            'status'            => 'authorized',
            'gateway'           => $this->gateway,
            'wallet'            => $this->provider,
        ];

        $payment = $this->fixtures->create('payment', $attributes);

        $transaction = $this->fixtures->create(
            'transaction',
            [
                'entity_id'   => $payment->getId(),
                'merchant_id' => '10000000000000',
            ]
        );

        $this->fixtures->edit('payment', $payment->getId(), ['transaction_id' => $transaction->getId()]);

        return $payment->getId();
    }
}
