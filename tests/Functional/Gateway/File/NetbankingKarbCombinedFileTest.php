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
use RZP\Models\Gateway\File\Processor\Refund\Karb;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NetbankingKarbCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{
    const REFUND_FIELDS = [
        Karb::SERIAL_NO,
        Karb::PAYMENT_ID,
        Karb::REFUND,
        Karb::BANK_NAME,
        Karb::REFUND_AMOUNT,
        Karb::BANK_REFERENCE_NO,
    ];

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingKarbCombinedFileTestData.php';

        parent::setUp();

        $this->bank = IFSC::KARB;

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_Karnataka_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testNetbankingKarbCombinedFile()
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
                    'type' => 'karnataka_netbanking_refund',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $file);

        Mail::assertSent(DailyFile::class, function ($mail) use ($paymentEntity1, $refundEntity1, $paymentEntity2, $refundEntity2, $refundsFileLocation)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Karb Netbanking claims and refund files for '.$date,
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
        $refundsFileContents = file($refundsFileLocation);;

        $this->assertCount(3, $refundsFileContents);

        $fullRefundRowData = explode('||',$refundsFileContents[1]);
        $fullRefundRowData = array_combine(self::REFUND_FIELDS, $fullRefundRowData);

        $partialRefundRowData = explode('||',$refundsFileContents[2]);
        $partialRefundRowData = array_combine(self::REFUND_FIELDS, $partialRefundRowData);

        $this->assertCount(6, $fullRefundRowData);

        $this->assertEquals($payment1['id'], $fullRefundRowData[Karb::PAYMENT_ID]);

        // validating is  refund amount is reflected in the file
        $refundAmount = number_format(($fullRefund['amount']) / 100, 2, '.', '');
        $this->assertEquals($refundAmount, trim($fullRefundRowData[Karb::REFUND_AMOUNT]));

        $refundAmount = number_format(($partialRefund['amount']) / 100, 2, '.', '');
        $this->assertEquals($refundAmount, $partialRefundRowData[Karb::REFUND_AMOUNT]);

    }

    protected function checkClaimFile(array $claimData, $payment1, $payment2)
    {
        $totalAmount = $payment1['amount'] + $payment2['amount'];

        $this->assertEquals($claimData['amount']['claims'], $this->getFormattedAmount($totalAmount));
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
