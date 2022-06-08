<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\UpiJusPay;

use Illuminate\Http\UploadedFile;

use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Batch\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class UpiJusPayReconTest extends TestCase
{
    use ReconTrait;
    use BatchTestTrait;

    /**
     * @var array
     */
    private $payment;

    private $terminal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setMockGatewayTrue();

        $this->payment = $this->getDefaultUpiPaymentArray();

        $this->gateway = Payment\Gateway::UPI_JUSPAY;

        $this->fixtures->merchant->enableMethod(Merchant\Account::TEST_ACCOUNT, Payment\Method::UPI);

        $this->terminal = $this->fixtures->create('terminal:upi_juspay_terminal');

        $this->ba->publicAuth();
    }

    public function testUpiJuspayPaymentRecon()
    {
        $payment = $this->createDependentEntitiesForPayment(500000);

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], 'upi_sett_bajaj.csv', 'text/plain');

        $this->reconcile($uploadedFile, 'UpiJuspay');

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'            => 'reconciliation',
                'gateway'         => 'UpiJuspay',
                'status'          => Status::PROCESSED,
                'total_count'     => 1,
                'success_count'   => 1,
                'processed_count' => 1,
                'failure_count'   => 0,
            ],
            $batch
        );

        $this->paymentReconAsserts($payment);
    }

    public function testUpiJuspayRefundRecon()
    {
        $refund = $this->createDependentEntitiesForRefund(500000);

        $this->mockReconContentFunction(function (& $content) use ($refund)
        {
            if ($content['REFUNDID'] === $refund['id'])
            {
                $content = [];
            }
        });

        $fileContents = $this->generateReconFile(
            [
                'gateway' => $this->gateway,
                'type'    => 'refund',
            ]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], 'upi_refund_bajaj.csv', 'text/plain');

        $this->reconcile($uploadedFile, 'UpiJuspay');

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'            => 'reconciliation',
                'gateway'         => 'UpiJuspay',
                'status'          => Status::PROCESSED,
                'total_count'     => 1,
                'success_count'   => 1,
                'processed_count' => 1,
                'failure_count'   => 0,
            ],
            $batch
        );

        $this->refundReconAsserts($refund);
    }

    protected function paymentReconAsserts(array $payment)
    {
        $updatedPayment = $this->getDbEntity('payment', ['id' => $payment['id']]);

        $this->assertEquals(true, $updatedPayment['gateway_captured']);

        /**
         * @var $upi \RZP\Gateway\Upi\Base\Entity
         */
        $upi = $this->getDbEntity('upi', ['payment_id' => $updatedPayment['id']]);

        // Assert RRN is updated both in payment and UPI entity
        $this->assertEquals('009007125383', $upi->getNpciReferenceId());
        $this->assertEquals('009007125383', $updatedPayment['reference16']);

        // Assert vpa is updated both in payment and UPI entity
        $this->assertEquals('john.miller@ybl', $upi->getVpa());

        //Assert upi.npci_txn_id
        $this->assertEquals('BJJ08df8cc33c68435988aafa54de908913', $upi->getNpciTransactionId());


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

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertEquals($data['gatewayTransactionId'], 'BJJdcf478fff4b9a8ae78fb40b3384c2d01');

        $transactionEntity = $this->getDbEntity('transaction', ['entity_id' => $updatedRefund['id']]);

        $this->assertNotNull($transactionEntity['reconciled_at']);
    }

    protected function createDependentEntitiesForPayment($amount, $status = 'authorized', $bankRef = 'abc123456')
    {
        $paymentArray = [
            'merchant_id'      => '10000000000000',
            'amount'           => $amount,
            'currency'         => 'INR',
            'method'           => 'upi',
            'status'           => $status,
            'gateway'          => 'upi_juspay',
            'terminal_id'      => $this->terminal['id'],
        ];

        if ($status === 'authorized')
        {
            $paymentArray['gateway_captured'] = true;
        }

        $payment = $this->fixtures->create(
            'payment',
            $paymentArray
        )->toArray();

        $this->fixtures->create('upi', ['payment_id'            => $payment['id'],
                                                 'action'               => 'authorize',
                                                 'npci_reference_id'    => '009007125383',
                                                 'npci_txn_id'          => 'BJJ08df8cc33c68435988aafa54de908913',
            ]);

        return $payment;
    }

    protected function createDependentEntitiesForRefund($amount, $status = 'authorized', $bankRef = 'abc123456')
    {
        $paymentArray = [
            'merchant_id'      => '10000000000000',
            'amount'           => $amount,
            'currency'         => 'INR',
            'method'           => 'upi',
            'status'           => $status,
            'gateway'          => 'upi_juspay',
            'terminal_id'      => $this->terminal['id'],
        ];

        if ($status === 'authorized')
        {
            $paymentArray['gateway_captured'] = true;
        }

        $payment = $this->fixtures->create('payment', $paymentArray)->toArray();

        $this->fixtures->create(
            'mozart',
            array(
                'payment_id' => $payment['id'],
                'action'     => 'authorize',
                'gateway'    => 'upi_juspay',
                'amount'     => $amount,
                'raw'        => json_encode(
                    [
                        'rrn'                   => '',
                        'type'                  => 'MERCHANT_CREDITED_VIA_PAY',
                        'amount'                => $amount,
                        'status'                => 'payment_successful',
                        'payeeVpa'              => 'billpayments@abfspay',
                        'payerVpa'              => '',
                        'payerName'             => 'JOHN MILLER',
                        'paymentId'             => $payment['id'],
                        'gatewayResponseCode'   => '00',
                        'gatewayTransactionId'  => 'BJJ08df8cc33c68435988aafa54de908913'
                    ]
                )
            )
        );

        // Creating payment txn, as refund missing txn won't be
        // created during recon if payment txn is missing.
        $txnArray = [
            'entity_id'   => $payment['id'],
            'type'        => 'payment',
            'merchant_id' => '10000000000000',
            'amount'      => $payment['amount'],
        ];

        $paymentTxn = $this->fixtures->create('transaction', $txnArray)->toArray();

        $this->fixtures->edit('payment', $payment['id'], ['transaction_id' => $paymentTxn['id']]);

        $refundArray = [
            'payment_id'  => $payment['id'],
            'merchant_id' => '10000000000000',
            'amount'      => $payment['amount'],
            'status'      => 'processed',
            'gateway'     => 'upi_juspay',
        ];

        $refund = $this->fixtures->create('refund', $refundArray)->toArray();

        $this->fixtures->create(
            'mozart',
            array(
                'payment_id' => $payment['id'],
                'action'     => 'refund',
                'refund_id'  => $refund['id'],
                'gateway'    => 'upi_juspay',
                'amount'     => $payment['amount'],
                'raw'        => json_encode(
                    [
                        'status' 				=> 'refund_initiated_successfully',
                        'apiStatus' 			=> 'SUCCESS',
                        'merchantId' 			=> 'BAJAJBILLPAYMENTS',
                        'refundAmount' 			=> $payment['amount'],
                        'responseCode' 			=> 'SUCCESS',
                        'responseMessage' 		=> 'SUCCESS',
                        'merchantRequestId' 	=> $payment['id'],
                        'transactionAmount' 	=> $payment['amount'],
                        'gatewayResponseCode' 	=> '00',
                        'gatewayTransactionId' 	=> 'BJJdcf478fff4b9a8ae78fb40b3384c2d01',
                    ]
                )
            )
        );

        return $refund;
    }

    public function createUploadedFile(string $url, $fileName = 'file.xlsx', $mime = null): UploadedFile
    {
        $mime = $mime ?? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return new UploadedFile(
            $url,
            $fileName,
            $mime,
            filesize($url),
            null,
            true);
    }
}
