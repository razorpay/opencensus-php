<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\UpiYesBank;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Batch\Status;
use RZP\Models\Payment\Gateway;
use RZP\Models\Merchant\Account;
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

    public function testUpiYesBankUnexpectedPaymentRecon()
    {
        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);
        $this->fixtures->merchant->enableUpi(Account::DEMO_ACCOUNT);

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $paymentCount = 1;

        $payments = $this->makeUpiYesBankPaymentsSince($paymentCount, $createdAt);

        $paymentEntity = $this->getDbLastpayment();

        $upiEntity = $this->getDbLastUpi();

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'yesbank_recon')
                {
                    $content[0]['PG Merchant ID']           = $this->sharedTerminal->getGatewayMerchantId();
                    $content[0]['Order No']                 = 'YESB12WE34RDSQ187';
                    $content[0]['Customer Ref No']          = '123456789013';
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

        $this->assertEquals('upi_yesbank', $unexpectedUpiEntity['gateway']);

        $this->assertNotNull($unexpectedUpiEntity['reconciled_at']);
    }

    public function testUpiYesBankDuplicateUnexpectedPayment()
    {
        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);
        $this->fixtures->merchant->enableUpi(Account::DEMO_ACCOUNT);

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $paymentCount = 1;

        $payments = $this->makeUpiYesBankPaymentsSince($paymentCount, $createdAt);

        $paymentEntity = $this->getDbLastpayment();

        $upiEntity = $this->getDbLastUpi();

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'yesbank_recon')
                {
                    $content[0]['PG Merchant ID']           = $this->sharedTerminal->getGatewayMerchantId();
                    $content[0]['Order No']                 = 'YESB12WE34RDSQ187';
                    $content[0]['Customer Ref No.']          = '123456789013';
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

        $this->mockReconContentFunction(
            function(& $content, $action = null)
            {
                if ($action === 'yesbank_recon')
                {
                    $content[0]['PG Merchant ID']           = $this->sharedTerminal->getGatewayMerchantId();
                    $content[0]['Order No']                 = 'YESB12WE34RDSQ187';
                    $content[0]['Customer Ref No.']         = '123456789013';
                }
            });

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiYesBank');

        $duplicateUnexpectedPayment = $this->getLastEntity('payment', true);

        $this->assertEquals($unexpectedPayment['id'], $duplicateUnexpectedPayment['id']);
    }

    public function testUpiYesBankMultipleRrn()
    {
        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);
        $this->fixtures->merchant->enableUpi(Account::DEMO_ACCOUNT);

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $paymentCount = 1;

        $payments = $this->makeUpiYesBankPaymentsSince($paymentCount, $createdAt);

        $paymentEntity = $this->getDbLastEntityToArray('payment');

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $this->fixtures->edit('upi', $upiEntity['id'], ['reconciled_at' => Carbon::now(Timezone::IST)->getTimestamp()]);

        $this->fixtures->edit('upi', $upiEntity['id'], ['gateway' => 'upi_yesbank']);

        $this->fixtures->edit('upi', $upiEntity['id'], ['npci_reference_id' => '123432145678']);

        $this->fixtures->edit('upi', $upiEntity['id'], ['merchant_reference' => $paymentEntity['id']]);

        $updatedUpiEntity = $this->getDbLastEntityToArray('upi');

        $this->assertEquals($paymentEntity['id'], $updatedUpiEntity['merchant_reference']);

        $this->mockReconContentFunction(
            function(& $content, $action = null) use ($paymentEntity)
            {
                if ($action === 'yesbank_recon')
                {
                    $content[0]['PG Merchant ID']           = $this->sharedTerminal->getGatewayMerchantId();
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

    public function testUpiYesBankRefundRecon()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->makeUpiYesBankPaymentsSince(1, $createdAt);

        $payment = $this->getDbLastPayment();

        $refund = $this->createDependentEntitiesForRefund($payment);

        $fileContents = $this->generateReconFile(
            [
                'gateway' => $this->gateway,
                'type'    => 'refund'
            ]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiYesBank');

        $this->refundReconAsserts($refund);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'            => 'reconciliation',
                'gateway'         => 'UpiYesBank',
                'status'          => Status::PROCESSED,
                'total_count'     => 1,
                'success_count'   => 1,
                'processed_count' => 1,
                'failure_count'   => 0,
            ],
            $batch
        );
    }

    protected function createDependentEntitiesForRefund($payment)
    {
        $refundArray = [
            'payment_id'  => $payment['id'],
            'merchant_id' => '10000000000000',
            'amount'      => $payment['amount'],
            'base_amount' => $payment['amount'],
            'status'      => 'processed',
            'gateway'     => $this->gateway,
        ];

        $refund = $this->fixtures->create('refund', $refundArray)->toArray();

        $this->fixtures->create(
            'mozart',
            array(
                'payment_id' => $payment['id'],
                'action'     => 'refund',
                'refund_id'  => $refund['id'],
                'gateway'    => 'upi_yesbank',
                'amount'     => $payment['amount'],
                'raw'        => json_encode(
                    [
                        'status' 				=> 'refund_initiated_successfully',
                        'apiStatus' 			=> 'SUCCESS',
                        'merchantId' 			=> '',
                        'refundAmount' 			=> $payment['amount'],
                        'responseCode' 			=> 'SUCCESS',
                        'responseMessage' 		=> 'SUCCESS',
                        'merchantRequestId' 	=> $payment['id'],
                        'transactionAmount' 	=> $payment['amount'],
                        'gatewayResponseCode' 	=> '00',
                        'gatewayTransactionId' 	=> 'FT2022712537204137',
                    ]
                )
            )
        );

        return $refund;
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

        $transactionEntity = $this->getDbEntity('transaction', ['entity_id' => $updatedRefund['id']]);

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
