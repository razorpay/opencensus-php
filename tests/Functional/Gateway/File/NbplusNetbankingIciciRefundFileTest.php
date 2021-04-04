<?php

namespace RZP\Tests\Functional\Gateway\File;

use Carbon\Carbon;
use Mail;
use Excel;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\Payment\Gateway;
use RZP\Excel\Import as ExcelImport;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;
use RZP\Mail\Gateway\RefundFile\Constants as RefundFileMailConstants;

class NbplusNetbankingIciciRefundFileTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingIciciRefundFileTestData.php';

        parent::setUp();

        $this->bank = 'ICIC';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_icici_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testNetbankingIciciRefundFile()
    {
        Mail::fake();

        $this->doAuthAndCapturePayment($this->payment);

        $transaction1 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction1['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($transaction1['entity_id']);

        $refundTransaction1 = $this->getLastEntity('transaction', true);

        $paymentEntity1 = $this->getDbLastPayment();

        $refundEntity1 = $this->getDbLastRefund();

        $this->doAuthAndCapturePayment($this->payment);

        $transaction2 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction2['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($transaction2['entity_id'], 500);

        $refundTransaction2 = $this->getLastEntity('transaction', true);

        $paymentEntity2 = $this->getDbLastPayment();

        $refundEntity2 = $this->getDbLastRefund();

        $this->assertEquals('refunded', $paymentEntity1['status']);
        $this->assertEquals('captured', $paymentEntity2['status']);
        $this->assertEquals(3, $paymentEntity1['cps_route']);
        $this->assertEquals(3, $paymentEntity2['cps_route']);
        $this->assertEquals('full', $paymentEntity1['refund_status']);
        $this->assertEquals('partial', $paymentEntity2['refund_status']);
        $this->assertEquals(1, $refundEntity1['is_scrooge']);
        $this->assertEquals(1, $refundEntity2['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity1, $refundEntity2]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $refundTran1 = $this->getDbEntityById('transaction', $refundTransaction1['id']);
        $refundTran2 = $this->getDbEntityById('transaction', $refundTransaction2['id']);

        $this->assertNotNull($refundTran1['reconciled_at']);
        $this->assertNotNull($refundTran2['reconciled_at']);
        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $today = Carbon::now(Timezone::IST)->format('d-m-Y');

        $expectedFileContent = [
            'type'        => 'icici_netbanking_refund',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);


        Mail::assertQueued(RefundFileMail::class, function ($mail) use ($paymentEntity1, $refundEntity1, $refundEntity2, $paymentEntity2)
        {
            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $expectedSubject = RefundFileMailConstants::SUBJECT_MAP[Gateway::NETBANKING_ICICI] . $today;

            $this->assertEquals($expectedSubject, $mail->subject);

            $testData = [
                'body'          => RefundFileMailConstants::BODY_MAP[Gateway::NETBANKING_ICICI],
                'file_name'     => "Icici_Netbanking_Refunds_test_$today.xlsx",
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertNotEmpty($mail->attachments);

            $sheet = (new ExcelImport)->toArray($mail->attachments[0]['file'])[0];

            $this->assertCount(10, $sheet[0]);
            $this->assertEquals($sheet[0]['refund_amount'], 500);

            //
            // Marking netbanking transaction as reconciled after sending in bank file
            //
            $refundTransaction = $this->getLastEntity('transaction', true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

            return ($mail->hasFrom('refunds@razorpay.com') and
                ($mail->hasTo(RefundFileMailConstants::RECIPIENT_EMAILS_MAP[Gateway::NETBANKING_ICICI])));
        });
    }

    public function testRefundFileDirectSettlement()
    {
        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('ICIC');

        $payment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($payment['id']);

        $refundEntity1 = $this->getDbLastEntity('refund');

        $this->fixtures->terminal->disableTerminal($this->terminal['id']);

        $this->fixtures->create('terminal:direct_settlement_refund_icici_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray('ICIC');

        $payment = $this->doAuthPayment($payment);

        $this->refundPayment($payment['razorpay_payment_id']);

        $refundEntity2 = $this->getDbLastEntity('refund');

        // Netbanking Icici refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity1['is_scrooge']);
        $this->assertEquals(1, $refundEntity2['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity1, $refundEntity2]);

        $this->ba->adminAuth();

        $testData = $this->testData['testNetbankingIciciRefundFile'];

        $content = $this->runRequestResponseFlow($testData);

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $files = $this->getEntities('file_store', ['count' => 2], true);

        foreach ($files['items'] as $file)
        {
            $expectedFileContent = [
                'entity_type' => 'gateway_file',
                'entity_id' => $content['id'],
                'extension' => 'xlsx',
            ];

            $this->assertArraySelectiveEquals($expectedFileContent, $file);

            $this->assertContains($file['type'], ['icici_netbanking_refund', 'icici_netbanking_refund_direct_settlement']);

            Mail::assertQueued(RefundFileMail::class, function ($mail) use ($file) {
                $today = Carbon::now(Timezone::IST)->format('d-m-Y');

                $expectedSubject = RefundFileMailConstants::SUBJECT_MAP[Gateway::NETBANKING_ICICI] . $today;

                $this->assertEquals($expectedSubject, $mail->subject);

                $testData = [
                    'body' => RefundFileMailConstants::BODY_MAP[Gateway::NETBANKING_ICICI],
                    'file_name' => "Icici_Netbanking_Refunds_test_$today.xlsx",
                ];

                $this->assertArraySelectiveEquals($testData, $mail->viewData);

                $this->assertNotEmpty($mail->attachments);

                $sheet = (new ExcelImport)->toArray($mail->attachments[0]['file'])[0];

                $this->assertCount(10, $sheet[0]);
                $this->assertEquals($sheet[0]['refund_amount'], 500);

                //
                // Marking netbanking transaction as reconciled after sending in bank file
                //
                $refundTransaction = $this->getLastEntity('transaction', true);

                $this->assertNotNull($refundTransaction['reconciled_at']);

                return ($mail->hasFrom('refunds@razorpay.com') and
                    ($mail->hasTo(RefundFileMailConstants::RECIPIENT_EMAILS_MAP[Gateway::NETBANKING_ICICI])));
            });
        }
    }
}
