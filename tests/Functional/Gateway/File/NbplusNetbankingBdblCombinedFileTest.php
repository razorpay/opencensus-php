<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Constants\Entity as ConstantsEntity;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingBdblCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingBdblCombinedFileTestData.php';

        parent::setUp();

        $this->bank = 'BDBL';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_bdbl_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testNetbankingBdblCombinedFile()
    {
        Mail::fake();

        $this->doAuthAndCapturePayment($this->payment);

        $transaction1 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction1['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($transaction1['entity_id']);

        $paymentEntity1 = $this->getDbLastPayment();

        $refundEntity1 = $this->getDbLastRefund();

        $this->doAuthAndCapturePayment($this->payment);

        $transaction2 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction2['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($transaction2['entity_id'], 500);

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

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity1,$refundEntity2]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity(ConstantsEntity::FILE_STORE, true);


        $files = $this->getEntities('file_store', [
            'count' => 2
        ], true);

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'bdbl_netbanking_refund',
                    'type' => 'bdbl_netbanking_combined',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($refundEntity1, $refundEntity2, $paymentEntity1, $paymentEntity2)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Bdbl Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  =>  "1000.00",
                    'refunds' =>  "505.00",
                    'total'   =>  "495.00"
                ],
                'count' => [
                    'claims'  => 2,
                    'refunds' => 2
                ],
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertCount(2, $mail->attachments);

            $this->checkRefundsFile($mail->viewData['refundsFile'],
                $paymentEntity1,
                $refundEntity1,
                $refundEntity2,
                $paymentEntity2);

            //
            // Marking netbanking transaction as reconciled after sending in bank file
            //
            $refundTransaction = $this->getLastEntity('transaction', true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

            return true;
        });
    }

    protected function checkRefundsFile(array $refundFileData, $payment1, $fullRefund, $partialRefund, $payment2)
    {
        $refundsFileContents = file($refundFileData['url']);

        $fullRefundRowData1 = explode(',', $refundsFileContents[1]);

        $this->assertEquals(trim($fullRefundRowData1[8], '"'), $fullRefund['payment_id']);
        $this->assertEquals(trim($fullRefundRowData1[1], '"'), $fullRefund['id']);
        $this->assertEquals((int)number_format(trim(trim($fullRefundRowData1[10]),'"')*100, 0, '', ''), $fullRefund['amount']);
        $this->assertEquals((int)number_format(trim(trim($fullRefundRowData1[9]),'"')*100, 0, '', ''), $payment1['amount']);

        $partialRefundRowData1 = explode(',', $refundsFileContents[2]);

        $this->assertEquals(trim($partialRefundRowData1[8], '"'),$partialRefund['payment_id']);
        $this->assertEquals(trim($partialRefundRowData1[1], '"'),$partialRefund['id']);
        $this->assertEquals((int)number_format(trim(trim($partialRefundRowData1[10]),'"')*100, 0, '',''),$partialRefund['amount']);
        $this->assertEquals((int)number_format(trim(trim($partialRefundRowData1[9]),'"')*100, 0, '',''),$payment2['amount']);
    }
}
