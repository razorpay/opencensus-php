<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Payment\StaticCallbackNbplusGatewayTest;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingCanaraCombinedFileTest extends StaticCallbackNbplusGatewayTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingCanaraCombinedFileTestData.php';

        NbPlusPaymentServiceNetbankingTest::setUp();

        $this->bank = 'CNRB';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_canara_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testNetbankingCanaraCombinedFile()
    {
        Mail::fake();

        $paymentArray = $this->payment;

        $this->doAuthAndCapturePayment($paymentArray);

        $transaction1 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction1['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($transaction1['entity_id']);

        $refundTransaction1 = $this->getLastEntity('transaction', true);

        $paymentEntity1 = $this->getDbLastPayment();

        $refundEntity1 = $this->getDbLastRefund();

        $this->doAuthAndCapturePayment($paymentArray);

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
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $files = $this->getEntities('file_store', [
            'count' => 2
        ], true);

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'canara_netbanking_claims',
                ],
                [
                    'type' => 'canara_netbanking_refund',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($paymentEntity1, $refundEntity1, $refundEntity2, $paymentEntity2)
        {
            $today = Carbon::now(Timezone::IST)->format('dmY');

            $testData = [
                'subject' => 'PG RECON DATA '.$today,
                'amount' => [
                    'claims'  =>  "1000.00",
                    'refunds' =>  "505.00",
                    'total'   =>  "495.00"
                ],
                'count' => [
                    'claims'  => 2,
                    'refunds' => 2,
                    'total'   => 4
                ],
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertCount(2, $mail->attachments);

            $this->checkRefundsFile(
                $mail->viewData['refundsFile'],
                $paymentEntity1,
                $paymentEntity2,
                $refundEntity1,
                $refundEntity2);

            $this->checkClaimFile(
                $mail->viewData['claimsFile'],
                $paymentEntity1,
                $paymentEntity2);

            return true;
        });
    }
    protected function checkRefundsFile(array $refundFileData, $payment1, $payment2, $refundfull, $refundPartial)
    {
        $refundsFileContents = file($refundFileData['url']);

        $this->assertCount(3, $refundsFileContents);

        $rowFullRefund    = explode('|', $refundsFileContents[1]);
        $rowPartialRefund = explode('|', $refundsFileContents[2]);

        $this->assertCount(7, $rowFullRefund);


        $this->assertEquals($rowFullRefund[0], $this->getFormattedDate($payment1['created_at'], 'd-m-Y H:i:s'));
        $this->assertEquals($rowFullRefund[1], $this->getFormattedDate($refundfull['created_at'], 'd-m-Y H:i:s'));
        $this->assertNotNull($rowFullRefund[2]);
        $this->assertEquals($payment1['id'], $rowFullRefund[3]);
        $this->assertEquals($refundfull['id'], $rowFullRefund[4]);
        $this->assertEquals($this->getFormattedAmount($payment1['amount']), $rowFullRefund[5]);
        $this->assertEquals($this->getFormattedAmount($refundfull['amount']), trim($rowFullRefund[6]));

        $this->assertEquals($rowPartialRefund[0], $this->getFormattedDate($payment2['created_at'], 'd-m-Y H:i:s'));
        $this->assertEquals($rowPartialRefund[1], $this->getFormattedDate($refundPartial['created_at'], 'd-m-Y H:i:s'));
        $this->assertNotNull($rowPartialRefund[2]);
        $this->assertEquals($payment2['id'], $rowPartialRefund[3]);
        $this->assertEquals($refundPartial['id'], $rowPartialRefund[4]);
        $this->assertEquals($this->getFormattedAmount($payment2['amount']), $rowPartialRefund[5]);
        $this->assertEquals($this->getFormattedAmount($refundPartial['amount']), trim( $rowPartialRefund[6]));

    }

    protected function checkClaimFile(array $claimsFileData, $payment1, $payment2)
    {
        $claimsFileContents = file($claimsFileData['url']);

        $this->assertCount(3, $claimsFileContents);

        $row1 = explode('|', $claimsFileContents[1]);
        $row2 = explode('|', $claimsFileContents[2]);

        $this->assertCount(5, $row1);
        $this->assertCount(5, $row2);

        $this->assertEquals($payment1['id'], $row1[0]);
        $this->assertEquals($payment2['id'], $row2[0]);

        $this->assertNotNull($row1[1]);
        $this->assertNotNull($row2[1]);

        $this->assertEquals($row1[2], $this->getFormattedDate($payment1['created_at'], 'd-m-Y'));
        $this->assertEquals($row2[2], $this->getFormattedDate($payment2['created_at'], 'd-m-Y'));

        $this->assertEquals($row1[3], $this->getFormattedAmount($payment1['amount']));
        $this->assertEquals($row2[3], $this->getFormattedAmount($payment2['amount']));

        $this->assertEquals(trim($row1[4]), 'SUCCESS');
        $this->assertEquals($row2[4], 'SUCCESS');
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
