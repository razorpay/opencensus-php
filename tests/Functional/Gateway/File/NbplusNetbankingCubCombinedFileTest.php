<?php

namespace RZP\Tests\Functional\Gateway\File;

use Carbon\Carbon;
use Mail;
use Excel;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Gateway\Mozart\NetbankingCub;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingCubCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingCubCombinedFileTestData.php';

        parent::setUp();

        $this->bank = 'CIUB';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_cub_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testNetbankingCubCombinedFile()
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

        $files = $this->getEntities('file_store', [
            'count' => 2
        ], true);

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'cub_netbanking_claim',
                ],
                [
                    'type' => 'cub_netbanking_refund',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($paymentEntity1, $refundEntity1, $refundEntity2, $paymentEntity2)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Cub Netbanking claims and refund files for '.$date,
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

            $this->checkRefundsFile(
                $mail->viewData['refundsFile'],
                $paymentEntity1,
                $refundEntity1,
                $refundEntity2);

            $this->checkClaimFile(
                $mail->viewData['claimsFile'],
                $paymentEntity1,
                $paymentEntity2);

            $this->assertCount(2, $mail->attachments);

            return true;
        });
    }


    protected function checkRefundsFile(array $refundFileData, $payment, $fullRefund, $partialRefund)
    {
        $refundsFileContents = file($refundFileData['url']);

        $this->assertCount(2, $refundsFileContents);

        $fullRefundRowData = explode('|', $refundsFileContents[0]);
        $fullRefundRowData = array_combine(NetbankingCub\RefundFields::REFUND_FIELDS, $fullRefundRowData);

        $partialRefundRowData = explode('|', $refundsFileContents[1]);
        $partialRefundRowData = array_combine(NetbankingCub\RefundFields::REFUND_FIELDS, $partialRefundRowData);

        $this->assertCount(6, $fullRefundRowData);

        $this->assertEquals($payment['id'], $fullRefundRowData[NetbankingCub\RefundFields::PAYMENT_ID]);

        // validating if partial refund amount is reflected in the file
        $refundAmount = number_format($fullRefund['amount'] / 100, 2, '.', '');
        $this->assertEquals($refundAmount, $fullRefundRowData[NetbankingCub\RefundFields::REFUND_AMOUNT]);

        $refundAmount = number_format($partialRefund['amount'] / 100, 2, '.', '');
        $this->assertEquals($refundAmount, $partialRefundRowData[NetbankingCub\RefundFields::REFUND_AMOUNT]);
    }

    protected function checkClaimFile(array $claimData, $payment1, $payment2)
    {
        $claimFileContents = file($claimData['url']);

        $payment1RowData = explode('~', $claimFileContents[1]);
        $payment1RowData = array_combine(NetbankingCub\ClaimFields::COLUMNS, $payment1RowData);

        $payment2RowData = explode('~', $claimFileContents[2]);
        $payment2RowData = array_combine(NetbankingCub\ClaimFields::COLUMNS, $payment2RowData);

        $this->assertCount(4, $payment1RowData);

        $this->assertEquals($payment1['id'], $payment1RowData[NetbankingCub\ClaimFields::PAYMENT_ID]);

        $this->assertEquals($payment2['id'], $payment2RowData[NetbankingCub\ClaimFields::PAYMENT_ID]);

        $paymentAmount = number_format($payment1['amount'] / 100, 2, '.', '');

        $this->assertEquals($paymentAmount, $payment1RowData[NetbankingCub\ClaimFields::TRANSACTION_AMOUNT]);
    }
}
