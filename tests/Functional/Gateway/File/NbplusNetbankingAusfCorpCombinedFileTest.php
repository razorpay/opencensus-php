<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Entity;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Models\Feature\Constants;
use RZP\Models\Gateway\File;
use RZP\Mail\Gateway\DailyFile;
use RZP\Excel\Import as ExcelImport;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingAusfCorpCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingAusfCorpCombinedFileTestData.php';

        parent::setUp();

        $this->bank = Netbanking::AUBL_C;



        $this->fixtures->merchant->addFeatures([Constants::CORPORATE_BANKS]);

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $terminalAttrs = [
            \RZP\Models\Terminal\Entity::CORPORATE => 1,
        ];

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_ausf_terminal', @$terminalAttrs);

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }


    public function testNetbankingAusfCorpCombinedFile()
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

        $file = $this->getEntities(Entity::FILE_STORE, ['count' => 3], true);

        $refundsFile = $file['items'][2]['location'];

        $refundsFileLocation = 'storage/files/filestore/' . $refundsFile;

        $expectedFilesContent = [
            'entity' => 'collection',
            'count'  => 3,
            'items'  => [
                [
                    'type' => \RZP\Models\FileStore\Type::AUBL_CORP_NETBANKING_COMBINED,
                ],
                [
                    'type' => \RZP\Models\FileStore\Type::AUBL_CORP_NETBANKING_CLAIM,
                ],
                [
                    'type' => \RZP\Models\FileStore\Type::AUBL_CORP_NETBANKING_REFUND,
                ]
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $file);

        Mail::assertSent(DailyFile::class, function ($mail) use ($paymentEntity1, $refundEntity1, $paymentEntity2, $refundEntity2, $refundsFileLocation)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Aubl_corp Netbanking claims and refund files for '.$date,
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
                $mail->viewData['claimsFile'],
                $paymentEntity1,
                $paymentEntity2);

            //
            // Marking netbanking transaction as reconciled after sending in bank file
            //
            $refundTransaction = $this->getLastEntity('transaction', true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

            return true;
        });
    }

    protected function checkRefundsFile($refundsFileLocation, $payment1, $payment2, $fullRefund, $partialRefund)
    {
        $refundsFileContents = (new ExcelImport)->toArray($refundsFileLocation)[0];

        $this->assertCount(2, $refundsFileContents);

        $refundFileRow1 = $refundsFileContents[0];

        $this->assertCount(7, $refundFileRow1);
        $this->assertEquals($refundFileRow1['refund_id'], $fullRefund['id']);
        $this->assertNotNull($refundFileRow1['bank_ref_no']);
        $this->assertEquals($refundFileRow1['rzp_reference_no'], $payment1['id']);
        $this->assertEquals($refundFileRow1['txn_amount_rs_ps'], $this->getFormattedAmount($payment1['amount']));
        $this->assertEquals($refundFileRow1['refund_amount_rs_ps'], $this->getFormattedAmount($fullRefund['amount']));

        $refundFileRow2 = $refundsFileContents[1];

        $this->assertCount(7, $refundFileRow1);
        $this->assertEquals($refundFileRow2['refund_id'], $partialRefund['id']);
        $this->assertNotNull($refundFileRow2['bank_ref_no']);
        $this->assertEquals($refundFileRow2['rzp_reference_no'], $payment2['id']);
        $this->assertEquals($refundFileRow2['txn_amount_rs_ps'], $this->getFormattedAmount($payment2['amount']));
        $this->assertEquals($refundFileRow2['refund_amount_rs_ps'], $this->getFormattedAmount($partialRefund['amount']));
    }

    protected function checkClaimFile(array $claimData, $payment1, $payment2)
    {
        $claimFileContents = (new ExcelImport)->toArray($claimData['url'])[0];

        $this->assertCount(2, $claimFileContents);

        $claimFileRow = $claimFileContents[0];

        $this->assertCount(18, $claimFileRow);
        $this->assertEquals($claimFileRow['date'], $this->getFormattedDate($payment1['created_at'], 'd/m/Y'));
        $this->assertNotEmpty($claimFileRow['externalreferenceid']);
        $this->assertEquals($payment1['id'], $claimFileRow['userreferenceno']);
        $this->assertEquals($this->getFormattedAmount($payment1['amount']), $claimFileRow['amount']);

        $claimFileRow2 = $claimFileContents[1];

        $this->assertCount(18, $claimFileRow2);
        $this->assertEquals($claimFileRow2['date'], $this->getFormattedDate($payment2['created_at'], 'd/m/Y'));
        $this->assertNotEmpty($claimFileRow2['externalreferenceid']);
        $this->assertEquals($payment2['id'], $claimFileRow2['userreferenceno']);
        $this->assertEquals($this->getFormattedAmount($payment2['amount']), $claimFileRow2['amount']);
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
