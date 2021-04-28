<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Entity;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Mail\Gateway\DailyFile;
use RZP\Excel\Import as ExcelImport;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingDlbCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingDlbCombinedFileTestData.php';

        parent::setUp();

        $this->bank = IFSC::DLXB;

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_dlb_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }


    public function testNetbankingDlbCombinedFile()
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

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getEntities(Entity::FILE_STORE, ['count' => 1], true);

        $refundsFile = $file['items'][0]['location'];

        $refundsFileLocation = 'storage/files/filestore/' . $refundsFile;

        $expectedFilesContent = [
            'entity' => 'collection',
            'count'  => 1,
            'items'  => [
                [
                    'type' => 'dlb_netbanking_refund',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $file);

        Mail::assertSent(DailyFile::class, function ($mail) use ($paymentEntity1, $refundEntity1, $paymentEntity2, $refundEntity2, $refundsFileLocation)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Dlb Netbanking claims and refund files for '.$date,
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertCount(1, $mail->attachments);

            $this->checkRefundsFile(
                $refundsFileLocation,
                $paymentEntity1,
                $paymentEntity2,
                $refundEntity1,
                $refundEntity2);

            $this->checkClaimFile(
                $mail->viewData,
                $paymentEntity1,
                $paymentEntity2);

            return true;
        });
    }

    protected function checkRefundsFile($refundsFileLocation, $payment1, $payment2, $fullRefund, $partialRefund)
    {
        $refundsFileContents = (new ExcelImport)->toArray($refundsFileLocation)[0];

        $this->assertCount(6, $refundsFileContents);

        $refundFileRow1 = $refundsFileContents[4];

        $this->assertCount(28, $refundFileRow1);
        $this->assertNotNull($refundFileRow1[1]);
        $this->assertEquals($refundFileRow1[8], $this->getFormattedAmount($fullRefund['amount']));
        $this->assertEquals($refundFileRow1[9], $this->getFormattedAmount($fullRefund['amount']));

        $refundFileRow2 = $refundsFileContents[5];

        $this->assertCount(28, $refundFileRow1);
        $this->assertNotNull($refundFileRow2[1]);
        $this->assertEquals($refundFileRow2[8], $this->getFormattedAmount($partialRefund['amount']));
        $this->assertEquals($refundFileRow2[9], $this->getFormattedAmount($partialRefund['amount']));

    }

    protected function checkClaimFile(array $claimData, $payment1, $payment2)
    {
        $totalAmount = $payment1['amount'] + $payment2['amount'];

        $this->assertEquals($claimData['amount']['claims'], $this->getFormattedAmount($totalAmount));
    }

    protected function getFormattedDate($date, $format)
    {
        return Carbon::createFromTimestamp($date, Timezone::IST)->format($format);
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
