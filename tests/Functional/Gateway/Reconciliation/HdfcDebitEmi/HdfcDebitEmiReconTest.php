<?php

namespace RZP\Tests\Functional\Gateway\Reconciliation\HdfcDebitEmi;

use RZP\Models\Batch\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\Helpers\Reconciliator\ReconTrait;

class HdfcDebitEmiReconTest extends TestCase
{
    use BatchTestTrait;
    use ReconTrait;

    protected $payment  = null;
    protected $terminal = null;
    protected $card     = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'hdfc_debit_emi';

        $this->setMockGatewayTrue();

        $this->card = $this->fixtures->card->createHdfcDebitEmiCard();

        $this->terminal = $this->fixtures->create('terminal:hdfc_debit_emi');

        $this->ba->publicAuth();
    }

    public function testPaymentRecon()
    {
        $payment_success = $this->createDependentEntities(500000);

        $payment_late_authorized = $this->createDependentEntities(550000, 'failed');

        $payment_failed = $this->createDependentEntities(550000, 'failed');

        $refund = $this->fixtures->create(
            'refund',
            [
                'payment_id'  => $payment_success['id'],
                'merchant_id' => '10000000000000',
                'amount'      => $payment_success['amount'],
                'status'      => 'processed',
                'gateway'     => 'hdfc_debit_emi',
            ]);

        $this->mockReconContentFunction(function (& $content) use ($payment_failed)
        {
            if ($content['MerchantReferenceNumber'] === $payment_failed['id'])
            {
                $content = [];
            }
        });

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createUploadedFile($fileContents['local_file_path'], 'merchantreconcilationreport.xlsx');

        $this->reconcile($uploadedFile, 'HdfcDebitEmi');

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'            => 'reconciliation',
                'gateway'         => 'HdfcDebitEmi',
                'status'          => Status::PROCESSED,
                'total_count'     => 3,
                'success_count'   => 3,
                'processed_count' => 3,
                'failure_count'   => 0,
            ],
            $batch
        );

        $this->paymentSuccessAsserts($payment_success);

        $this->paymentSuccessAsserts($payment_late_authorized);

        $this->paymentFailureAsserts($payment_failed);
    }

    protected function createDependentEntities($amount, $status = 'authorized', $bankRef = 'abc123456')
    {
        $paymentArray = [
            'merchant_id'      => '10000000000000',
            'amount'           => $amount,
            'currency'         => 'INR',
            'method'           => 'emi',
            'status'           => $status,
            'gateway'          => 'hdfc_debit_emi',
            'terminal_id'      => $this->terminal['id'],
            'card_id'          => $this->card['id'],
        ];

        if ($status === 'authorized')
        {
            $paymentArray['gateway_captured'] = true;
        }

        $payment = $this->fixtures->create(
            'payment',
            $paymentArray
        )->toArray();

        $this->fixtures->create(
            'mozart',
            array(
                'payment_id' => $payment['id'],
                'action'     => 'authorize',
                'gateway'    => 'hdfc_debit_emi',
                'amount'     => $amount,
                'raw'        => json_encode(
                    [
                        'Token'                      => '123456',
                        'status'                     => 'OTP_sent',
                        'BankReferenceNo'            => $bankRef,
                        'EligibilityStatus'          => 'Yes',
                        'MerchantReferenceNo'        => 'EQcXw2rrWYkpbv',
                        'ValidateOtpErrorCode'       => '0000',
                        'AuthenticationErrorCode'    => '0000',
                        'OrderConfirmationStatus'    => 'Yes',
                        'ValidateOtpErrorMessage'    => '',
                        'AuthenticationErrorMessage' => '',
                    ]
                )
            )
        );

        return $payment;
    }

    protected function paymentSuccessAsserts(array $payment)
    {
        $payment = $this->getDbEntity('payment', ['id' => $payment['id']])->toArray();

        $this->assertArraySelectiveEquals(
            [
                'status' => 'authorized',
            ],
            $payment
        );
        $gatewayEntity = $this->getDbEntity('mozart', ['payment_id' => $payment['id']]);

        $data = json_decode($gatewayEntity['raw'], true);

        $this->assertNotNull($data['BankReferenceNo']);

        $transactionEntity = $this->getDbEntity('transaction', ['entity_id' => $payment['id']]);

        $this->assertNotNull($transactionEntity['reconciled_at']);
    }

    protected function paymentFailureAsserts(array $payment)
    {
        $payment = $this->getDbEntity('payment', ['id' => $payment['id']])->toArray();

        $this->assertArraySelectiveEquals(
            [
                'status'        => 'failed',
            ],
            $payment
        );

        $transactionEntity = $this->getDbEntity('transaction', ['entity_id' => $payment['id']]);

        $this->assertNull($transactionEntity);
    }
}
