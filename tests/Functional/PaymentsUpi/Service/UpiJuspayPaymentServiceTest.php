<?php

namespace RZP\Tests\Functional\PaymentsUpi\Service;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Entity;
use Illuminate\Http\UploadedFile;

class UpiJuspayPaymentServiceTest extends UpiPaymentServiceTest
{

    public function testPaymentJuspayReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->gateway = 'upi_juspay';

        $this->makeUpiJuspayPaymentsSince($createdAt, 1);

        $payment = $this->getDbLastPayment();

        $this->mockReconContentFunction(function(&$content, $action = null)
        {
            if ($action === 'juspay_recon')
            {
                $content[0]['RRN']          = '227121351902';
                $content[0]['TXNID']        = 'FT2022712537204137';
            }
        });

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createJusPayUploadedFile(
            $fileContents['local_file_path'],
            'upi_sett_bajaj.csv',
            'text/plain');

        $this->reconcile($uploadedFile, 'UpiJuspay');

        $this->paymentReconAsserts($payment->toArray());

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals(
            [
                'type'            => 'reconciliation',
                'gateway'         => 'UpiJuspay',
                'status'          => 'processed',
                'total_count'     => 1,
                'success_count'   => 1,
                'processed_count' => 1,
                'failure_count'   => 0,
            ],
            $batch
        );
    }

    private function makeUpiJuspayPaymentsSince(int $createdAt, int $count = 3)
    {
        for ($i = 0; $i < $count; $i++)
        {
            $payments[] = $this->doUpiJuspayPayment();
        }

        foreach ($payments as $payment)
        {
            $this->fixtures->edit('payment', $payment, ['created_at' => $createdAt]);
        }

        return $payments;
    }

    private function doUpiJuspayPayment()
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
                'gateway' => 'upi_juspay',
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

    protected function createJusPayUploadedFile($file, $fileName = 'file.xlsx', $mimeType = null)
    {
        $this->assertFileExists($file);

        $mimeType = $mimeType ?? 'text/csv';
        $fileName = ($fileName == 'file.xlsx') ? $file : $fileName;

        $uploadedFile = new UploadedFile(
            $file,
            $fileName,
            $mimeType,
            null,
            true
        );

        return $uploadedFile;
    }
}
