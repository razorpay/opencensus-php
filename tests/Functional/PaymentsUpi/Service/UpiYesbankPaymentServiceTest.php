<?php

namespace RZP\Tests\Functional\PaymentsUpi\Service;


use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Entity;
use RZP\Models\Merchant\Account;
use RZP\Excel\Import as ExcelImport;

class UpiYesbankPaymentServiceTest extends UpiPaymentServiceTest
{

    public function testPaymentYesbankReconciliation()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->gateway = 'upi_yesbank';

        $this->makeUpiYesbankPaymentsSince($createdAt, 1);

        $payment = $this->getDbLastPayment();

        $this->mockReconContentFunction(
            function(&$content, $action = null)
           {
                if ($action === 'yesbank_recon')
                {
                    $content[0]['Customer Ref No']    = '227121351902';
                }
            });

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createUpsUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiYesBank');

        $this->paymentReconAsserts($payment->toArray());

        $batch = $this->getDbLastEntityToArray('batch');

        $this->assertArraySelectiveEquals
        (
            [
                    'type'            => 'reconciliation',
                    'gateway'         => 'UpiYesBank',
                    'status'          => 'processed',
                    'total_count'     => 1,
                    'success_count'   => 1,
                    'processed_count' => 1,
                    'failure_count'   => 0,
                ],
            $batch
        );
    }

    public function testUpiYesBankUnexpectedPaymentRecon()
    {
        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);
        $this->fixtures->merchant->enableUpi(Account::DEMO_ACCOUNT);

        $terminal = $this->fixtures->create('terminal:shared_upi_yesbank_terminal');

        $this->gateway = 'upi_yesbank';

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $payments = $this->makeUpiYesBankPaymentsSince($createdAt,1);

        $paymentEntity = $this->getDbLastpayment();

        $this->mockReconContentFunction(
            function(&$content, $action = null)
            {
                if ($action === 'yesbank_recon')
                {
                    $content[0]['PG Merchant ID']       = 'vpa_merchantsVpaId';
                    $content[0]['Order No']             = 'YESB12WE34RDSQ187';
                    $content[0]['Customer Ref No.']     = '123456789013'; // rrn is used to mock for unexpected payment
                }
            });

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createUpsUploadedFile($fileContents['local_file_path']);

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
    }

    public function testUpsYesBankDuplicateUnexpectedPayment()
    {
        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);
        $this->fixtures->merchant->enableUpi(Account::DEMO_ACCOUNT);

        $terminal = $this->fixtures->create('terminal:shared_upi_yesbank_terminal');

        $this->gateway = 'upi_yesbank';

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $payments = $this->makeUpiYesBankPaymentsSince($createdAt,1);

        $paymentEntity = $this->getDbLastEntityToArray('payment');

        // Changes a rrn of entity fetch response
        $this->mockServerRequestFunction(function (&$content) use ($paymentEntity)
        {
            $content['payment_id'] = $paymentEntity['id'];
        });

        $this->mockReconContentFunction(
            function(&$content, $action = null)
            {
                if ($action === 'yesbank_recon')
                {
                    $content[0]['PG Merchant ID']       = 'vpa_merchantsVpaId';
                    $content[0]['Order No']             = 'YESB12WE34RDSQ187';
                    $content[0]['Customer Ref No.']     = '123456789013'; // rrn is used to mock for unexpected payment
                }
            });

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createUpsUploadedFile($fileContents['local_file_path']);

        $this->reconcile($uploadedFile, 'UpiYesBank');

        $unexpectedPayment = $this->getDbLastEntityToArray('payment');

        $this->assertEquals($unexpectedPayment['id'], $paymentEntity['id']);

        $this->assertNotNull($unexpectedPayment['reference16']);

        $transaction = $this->getDbLastEntity('transaction');

        $this->assertNotNull($transaction['reconciled_at']);
    }

    public function testUpsYesBankMultipleRrn()
    {
        $this->fixtures->merchant->createAccount(Account::DEMO_ACCOUNT);
        $this->fixtures->merchant->enableUpi(Account::DEMO_ACCOUNT);

        $terminal = $this->fixtures->create('terminal:shared_upi_yesbank_terminal');

        $this->gateway = 'upi_yesbank';

        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $payments = $this->makeUpiYesBankPaymentsSince($createdAt,1);

        $paymentEntity = $this->getDbLastEntityToArray('payment');

        // Mark reconciled_at of entity fetch response for multiple rrn scenario
        $this->mockServerRequestFunction(function (&$content)
        {
            $content['reconciled_at'] = Carbon::now(Timezone::IST)->getTimestamp();
        });

        $this->mockReconContentFunction(
            function(&$content, $action = null) use ($paymentEntity)
            {
                if ($action === 'yesbank_recon')
                {
                    $content[0]['PG Merchant ID']           = 'vpa_merchantsVpaId';
                    $content[0]['Order No']                 = $paymentEntity['id'];
                    $content[0]['Customer Ref No']          = '123456789012';
                }
            });

        $fileContents = $this->generateReconFile(['gateway' => $this->gateway]);

        $uploadedFile = $this->createUpsUploadedFile($fileContents['local_file_path']);

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

    public function testRefundPaymentFileFlow()
    {
        $createdAt = Carbon::yesterday(Timezone::IST)->addHours(3)->getTimestamp();

        $this->makeUpiYesBankPaymentsSince($createdAt,1);

        $payment = $this->getDbLastPayment();

        $refund = $this->createDependentEntitiesForRefund($payment);

        $this->fixtures->edit('refund', $refund['id'], ['gateway_amount' => $refund['amount']]);

        $this->fixtures->edit('refund', $refund['id'], ['gateway_currency' => 'INR']);

        // Changes a rrn of entity fetch response
        $this->mockServerRequestFunction(function (&$content) use ($payment)
        {
            $content['payment_id'] = $payment['id'];
        });

        $refund = $this->getDbLastEntityToArray('refund');

        $refundArray[] = $refund;

        $this->setFetchFileBasedRefundsFromScroogeMockResponse($refundArray);

        $data = $this->generateRefundsExcelForYesbankUpi();

        $content = $data['items'][0];

        $file = $this->getLastEntity('file_store', true);

        $time = Carbon::now(Timezone::IST)->format('dmY_Hi');

        $this->assertEquals('file_store', $file['entity']);
        $this->assertEquals('rzp-1415-prod-sftp', $file['bucket']);
        $this->assertEquals('ap-south-1', $file['region']);
        $this->assertEquals('upi/upi_yesbank/refund/normal_refund_file/YesbankRefundFile_' . $time .'.xlsx', $file['location']);
        $this->assertEquals('upi/upi_yesbank/refund/normal_refund_file/YesbankRefundFile_' . $time, $file['name']);

        $refundFileRows = (new ExcelImport)->toArray('storage/files/filestore/'.$file['location'])[0];

        $expectedRefundFile = [
            'bankadjref'        => '227121351900',
            'flag'              => 'C',
            'shtdat'            => Carbon::createFromTimestamp($payment['created_at'], Timezone::IST)->format('m/d/Y'),
            'adjamt'            => '500',
            'shser'             => '227121351900',
            'utxid'             => 'FT2022712537204130',
            'filename'          => 'REFUND_RAZORPAY',
            'reason'            => 'Yesbank(Manual Refunds)',
            'specifyother'      => '500',
            'refund_id'         => $refund['id'],
        ];

        $this->assertArraySelectiveEquals($expectedRefundFile, $refundFileRows[0]);
    }

    /** Generate refunds file for Upi Airtel
     * @param false $date
     * @return mixed
     */
    protected function generateRefundsExcelForYesbankUpi($date = false)
    {
        $this->ba->adminAuth();

        $request = [
            'url'       => '/gateway/files',
            'method'    => 'POST',
            'content'   => [
                'type'      => 'refund',
                'targets'   => ['upi_yesbank'],
                'begin'     => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'       => Carbon::tomorrow(Timezone::IST)->getTimestamp()
            ],
        ];

        if ($date === true)
        {
            $request['content']['on'] = Carbon::now()->format('Y-m-d');
        }

        return $this->makeRequestAndGetContent($request);
    }

    private function makeUpiYesbankPaymentsSince(int $createdAt, int $count = 3)
    {
        for ($i = 0; $i < $count; $i++)
        {
            $payments[] = $this->doUpiYesbankPayment();
        }

        foreach ($payments as $payment)
        {
            $this->fixtures->edit('payment', $payment, ['created_at' => $createdAt]);
        }

        return $payments;
    }

    private function doUpiYesbankPayment()
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
                            'gateway' => 'upi_yesbank',
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
}
