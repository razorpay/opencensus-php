<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\UpiAirtel;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Illuminate\Http\UploadedFile;

use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Account;
use RZP\Models\Batch\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class UpiAirtelReconTest extends TestCase
{
    use ReconTrait;
    use BatchTestTrait;

    private $payment;
    private $refund;
    private $sharedTerminal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = Payment\Gateway::UPI_AIRTEL;

        $this->setMockGatewayTrue();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_upi_airtel_terminal');

        $this->fixtures->merchant->enableMethod(Merchant\Account::TEST_ACCOUNT, Payment\Method::UPI);

        $this->payment = $this->getDefaultUpiPaymentArray();
    }

    public function testPaymentReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '227121351902';

        $this->makeUpiAirtelPaymentsSince($createdAt, $rrn, 1);

        $payment = $this->getDbLastPayment();

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiAirtel');

        $this->paymentReconAsserts($payment->toArray());

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'            => 'reconciliation',
                'gateway'         => 'UpiAirtel',
                'status'          => Status::PROCESSED,
                'total_count'     => 1,
                'success_count'   => 1,
                'processed_count' => 1,
                'failure_count'   => 0,
            ],
            $batch
        );
    }

    public function testUnexpectedPaymentReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '232712135190';

        $this->makeUpiAirtelPaymentsSince($createdAt, $rrn, 1);

        $paymentEntity = $this->getDbLastEntityToArray('payment');

        $this->fixtures->payment->edit($paymentEntity['id'],
            [
                'refund_at'                => null,
            ]);

        $paymentEntity = $this->getDbLastEntityToArray('payment');

        $this->mockReconContentFunction(function (& $content, $action = null)
        {
            if ($action === 'airtel_recon')
            {
                $content['Till ID']         = 'BB31121900923519425756';
                $content['PARTNER_TXN_ID']  = '232712135190';
            }
        });

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiAirtel');

        $payment = $this->getDbLastEntityToArray('payment');

        // payment which is already created during callback is asserted here
        $this->assertEquals($payment['id'], $paymentEntity['id']);

        $upiEntity = $this->getDbLastEntityToArray('upi');

        $this->assertEquals($upiEntity['payment_id'], $paymentEntity['id']);

        // reconciling the unexpected payment which is created already
        $this->assertNotNull($upiEntity['reconciled_at']);

        $this->assertNotNull($payment['refund_at']);

        $transactionEntity = $this->getDbLastEntityToArray('transaction');

        $this->assertNotNull($transactionEntity['reconciled_at']);
    }

    /**
     * Test setting of rrn using MIS data.
     * rrn is set as empty while creating entity.
     * rrn is present in mis file
     * rrn of upi entity is saved as rrn of MIS file and same is asserted.
     */
    public function testPaymentReconciliationWithoutRrnInUpi()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        // rrn is set as empty
        $rrn = '';

        $this->makeUpiAirtelPaymentsSince($createdAt, $rrn, 1);

        $payment = $this->getDbLastPayment();

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiAirtel');

        $this->paymentReconAsserts($payment->toArray());

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'            => 'reconciliation',
                'gateway'         => 'UpiAirtel',
                'status'          => Status::PROCESSED,
                'total_count'     => 1,
                'success_count'   => 1,
                'processed_count' => 1,
                'failure_count'   => 0,
            ],
            $batch
        );
    }

    public function testUpiAirtelRefundRecon()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '22712135190';

        $this->makeUpiAirtelPaymentsSince($createdAt, $rrn, 1);

        $payment = $this->getDbLastPayment();

        $refund = $this->createDependentEntitiesForRefund($payment);

        $this->mockReconContentFunction(function (& $content) use ($refund)
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

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiAirtel');

        $this->refundReconAsserts($refund);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'            => 'reconciliation',
                'gateway'         => 'UpiAirtel',
                'status'          => Status::PROCESSED,
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

        $rrn = '22712135190';

        $this->makeUpiAirtelPaymentsSince($createdAt, $rrn, 1);

        $payment = $this->getDbLastPayment();

        $this->fixtures->payment->edit($payment['id'],
            [
                'status'                => 'failed',
                'authorized_at'         =>  null,
                'error_code'            => 'BAD_REQUEST_ERROR',
                'internal_error_code'   => 'BAD_REQUEST_PAYMENT_TIMED_OUT',
                'error_description'     => 'Payment was not completed on time.',
            ]);

        $payment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals('failed', $payment['status']);

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiAirtel');

        $payments = $this->getEntities('payment', [], true);

        $payment = $payments['items'][0];

        $transactionId = $payment['transaction_id'];

        $transaction = $this->getEntityById('transaction', $transactionId, true);

        $this->assertNotNull($transaction['reconciled_at']);

        $this->assertNotNull($payment['reference16']);
    }

    public function testNewMISFilePaymentReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '227121351902';

        $this->makeUpiAirtelPaymentsSince($createdAt, $rrn, 1);

        $payment = $this->getDbLastPayment();

        $this->mockReconContentFunction(function (& $content, $action = null)
        {
            if ($action === 'airtel_recon')
            {
                $content['Transaction Type']         = 'COLLECT';
            }
        });

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiAirtel');

        $this->paymentReconAsserts($payment->toArray());

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'            => 'reconciliation',
                'gateway'         => 'UpiAirtel',
                'status'          => Status::PROCESSED,
                'total_count'     => 1,
                'success_count'   => 1,
                'processed_count' => 1,
                'failure_count'   => 0,
            ],
            $batch
        );
    }

    public function testNewMisFileRefundRecon()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $rrn = '22712135190';

        $this->makeUpiAirtelPaymentsSince($createdAt, $rrn, 1);

        $payment = $this->getDbLastPayment();

        $refund = $this->createDependentEntitiesForRefund($payment);

        $this->mockReconContentFunction(function (& $content)
        {
            $content['Transaction Type']         = 'MERCHANT_REFUND';
        });

        $fileContents = $this->generateReconFile(
            [
                'gateway' => $this->gateway,
                'type'    => 'refund',
            ]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiAirtel');

        $this->refundReconAsserts($refund);

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'            => 'reconciliation',
                'gateway'         => 'UpiAirtel',
                'status'          => Status::PROCESSED,
                'total_count'     => 1,
                'success_count'   => 1,
                'processed_count' => 1,
                'failure_count'   => 0,
            ],
            $batch
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

        $gatewayEntity = $this->getDbEntity('upi', ['payment_id' => $updatedPayment['id']]);

        $this->assertEquals('227121351902', $gatewayEntity['npci_reference_id']);

        $this->assertEquals($gatewayEntity['npci_reference_id'], $updatedPayment['reference16']);

        $gatewayEntity = $this->getDbEntity('mozart', ['payment_id' => $updatedPayment['id']]);

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertEquals($data['gatewayTransactionId'], 'FT2022712537204137');

        $transactionEntity = $this->getDbEntity('transaction', ['entity_id' => $updatedPayment['id']]);

        $this->assertNotNull($transactionEntity['reconciled_at']);
    }

    private function createUploadedFile($file)
    {
        $this->assertFileExists($file);

        $mimeType = 'text/csv';

        $uploadedFile = new UploadedFile(
            $file,
            $file,
            $mimeType,
            null,
            true
        );

        return $uploadedFile;
    }

    private function makeUpiAirtelPaymentsSince(int $createdAt, string $rrn, int $count = 3)
    {
        for ($i = 0; $i < $count; $i++)
        {
            $payments[] = $this->doUpiAirtelPayment($rrn);

            $upiEntity = $this->getDbLastEntity('upi');

            $this->fixtures->edit('upi', $upiEntity['id'], ['npci_reference_id' => $rrn, 'gateway' => 'upi_airtel']);
        }

        foreach ($payments as $payment)
        {
            $this->fixtures->edit('payment', $payment, ['created_at' => $createdAt]);
        }

        return $payments;
    }

    private function doUpiAirtelPayment(string $rrn)
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

        $this->fixtures->create(
            'mozart',
            array(
                'payment_id' => $payment['id'],
                'action' => 'authorize',
                'gateway' => 'upi_airtel',
                'amount' => $payment['amount'],
                'raw' => json_encode(
                    [
                        'rrn' => $rrn,
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
}
