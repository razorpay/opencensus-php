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

class NbplusNetbankingUjjivanCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingUjjivanCombinedFileTestData.php';

        parent::setUp();

        $this->bank = IFSC::UJVN;

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_ujjivan_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testNetbankingUjjivanCombinedFile()
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

        $file = $this->getEntities(Entity::FILE_STORE, ['count' => 2], true);

        $refundsFile = $file['items'][1]['location'];

        $claimsFile = $file['items'][0]['location'];

        $refundsFileLocation = 'storage/files/filestore/' . $refundsFile;

        $claimsFileLocation = 'storage/files/filestore/' . $claimsFile;

        $expectedFilesContent = [
            'entity' => 'collection',
            'count'  => 2,
            'items'  => [
                [
                    'type' => 'ujjivan_netbanking_claims',
                ],
                [
                    'type' => 'ujjivan_netbanking_refund',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $file);

        Mail::assertSent(DailyFile::class, function ($mail) use ($paymentEntity1, $refundEntity1, $paymentEntity2, $refundEntity2, $refundsFileLocation, $claimsFileLocation)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Ujjivan Netbanking claims and refund files for '.$date,
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertCount(2, $mail->attachments);

            $this->checkRefundsFile(
                $refundsFileLocation,
                $paymentEntity1,
                $paymentEntity2,
                $refundEntity1,
                $refundEntity2);

            $this->checkClaimFile(
                $mail->viewData,
                $claimsFileLocation,
                $paymentEntity1,
                $paymentEntity2);

            return true;
        });
    }

    protected function checkRefundsFile($refundsFileLocation, $payment1, $payment2, $fullRefund, $partialRefund)
    {
        $refundsFileContents = (new ExcelImport)->toArray($refundsFileLocation)[0];

        $this->assertCount(2, $refundsFileContents);

        $refundFileRow1 = $refundsFileContents[0];

        $this->assertCount(6, $refundFileRow1);
        $this->assertNotNull($refundFileRow1['unique_reference_number_original_transaction_reference_id']);
        $this->assertEquals($refundFileRow1['amount'], $this->getFormattedAmount($fullRefund['amount']));

        $refundFileRow2 = $refundsFileContents[1];

        $this->assertCount(6, $refundFileRow1);
        $this->assertNotNull($refundFileRow2['unique_reference_number_original_transaction_reference_id']);
        $this->assertEquals($refundFileRow2['amount'], $this->getFormattedAmount($partialRefund['amount']));

    }

    protected function checkClaimFile(array $claimData, $claimsFileLocation, $payment1, $payment2)
    {
        $totalAmount = $payment1['amount'] + $payment2['amount'];

        $this->assertEquals($claimData['amount']['claims'], $this->getFormattedAmount($totalAmount));

        $claimsFileContents = (new ExcelImport)->toArray($claimsFileLocation)[0];

        $this->assertCount(2, $claimsFileContents);

        $claimsFileRow1 = $claimsFileContents[0];

        $this->assertCount(6, $claimsFileRow1);
        $this->assertNotNull($claimsFileRow1['unique_reference_number']);
        $this->assertEquals($claimsFileRow1['unique_reference_number'], $payment1['id']);
        $this->assertEquals($claimsFileRow1['amount'], $this->getFormattedAmount($payment1['amount']));

        $claimsFileRow2 = $claimsFileContents[1];

        $this->assertCount(6, $claimsFileRow2);
        $this->assertNotNull($claimsFileRow2['unique_reference_number']);
        $this->assertEquals($claimsFileRow2['unique_reference_number'], $payment2['id']);
        $this->assertEquals($claimsFileRow2['amount'], $this->getFormattedAmount($payment2['amount']));
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
