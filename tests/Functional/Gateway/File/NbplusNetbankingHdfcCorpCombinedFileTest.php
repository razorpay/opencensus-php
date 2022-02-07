<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Mail\Gateway\DailyFile;
use RZP\Models\Feature\Constants;
use RZP\Excel\Import as ExcelImport;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;
use RZP\Tests\Functional\Payment\StaticCallbackNbplusGatewayTest;

class NbplusNetbankingHdfcCorpCombinedFileTest extends StaticCallbackNbplusGatewayTest
{

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NbplusNetbankingHdfcCorpRefundFileTestData.php';

        NbPlusPaymentServiceNetbankingTest::setUp();

        $this->bank = Netbanking::HDFC_C;

        $this->fixtures->merchant->addFeatures([Constants::CORPORATE_BANKS]);

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $terminalAttrs = [
            \RZP\Models\Terminal\Entity::CORPORATE => 1,
        ];

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal', $terminalAttrs);
    }


    public function testNetbankingHdfcCorpCombinedFile()
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

        $this->ba->appAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getEntities(Entity::FILE_STORE, ['count' => 2], true);

        $claimFile   = $file['items'][0]['location'];
        $refundsFile = $file['items'][1]['location'];

        $refundsFileLocation = 'storage/files/filestore/' . $refundsFile;
        $claimFileLocation   = 'storage/files/filestore/' . $claimFile;

        $expectedFilesContent = [
            'entity' => 'collection',
            'count'  => 2,
            'items'  => [
                [
                    'type' => 'hdfc_corp_netbanking_claims',
                ],
                [
                    'type' => 'hdfc_corp_netbanking_refunds',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $file);

        Mail::assertSent(DailyFile::class, function ($mail) use ($paymentEntity1, $refundEntity1, $paymentEntity2, $refundEntity2, $refundsFileLocation, $claimFileLocation)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Hdfc_c Netbanking claims and refund files for '.$date,
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
                $claimFileLocation,
                $mail->viewData,
                $paymentEntity1,
                $paymentEntity2);

            return true;
        });
    }

    protected function checkRefundsFile($refundsFileLocation, $payment1, $payment2, $fullRefund, $partialRefund)
    {
        $refundsFileContents = (new ExcelImport)->toArray($refundsFileLocation)[0];

        $this->assertCount(7, $refundsFileContents);

        $refundFileRow1 = $refundsFileContents[5];

        $this->assertCount(15, $refundFileRow1);
        $this->assertNotNull($refundFileRow1[1]);
        $this->assertEquals($refundFileRow1[9], $this->getFormattedAmount($payment1['amount']));
        $this->assertEquals($refundFileRow1[10], $this->getFormattedAmount($fullRefund['amount']));

        $refundFileRow2 = $refundsFileContents[6];

        $this->assertCount(15, $refundFileRow1);
        $this->assertNotNull($refundFileRow2[1]);
        $this->assertEquals($refundFileRow2[9], $this->getFormattedAmount($payment2['amount']));
        $this->assertEquals($refundFileRow2[10], $this->getFormattedAmount($partialRefund['amount']));

        // Marking netbanking transaction as reconciled after sending in bank file
        $refundTransaction = $this->getLastEntity('transaction',true);
        $this->assertNotNull($refundTransaction['reconciled_at']);
    }

    protected function checkClaimFile($claimFileLocation, array $claimData, $payment1, $payment2)
    {
        $totalAmount = $payment1['amount'] + $payment2['amount'];

        $this->assertEquals($claimData['amount']['claims'], $this->getFormattedAmount($totalAmount));

        $claimsFileContents = (new ExcelImport)->toArray($claimFileLocation)[0];

        $this->assertCount(2, $claimsFileContents);

        $claimFileRow1 = $claimsFileContents[0];

        $this->assertCount(10, $claimFileRow1);
        $this->assertNotNull($claimFileRow1['pgirefno']);
        $this->assertNotNull($claimFileRow1['bankrefno']);
        $this->assertEquals($claimFileRow1['txnamount'], $this->getFormattedAmount($payment2['amount']));

        $claimFileRow2 = $claimsFileContents[1];

        $this->assertCount(10, $claimFileRow2);
        $this->assertNotNull($claimFileRow2['pgirefno']);
        $this->assertNotNull($claimFileRow2['bankrefno']);
        $this->assertEquals($claimFileRow2['txnamount'], $this->getFormattedAmount($payment2['amount']));
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
