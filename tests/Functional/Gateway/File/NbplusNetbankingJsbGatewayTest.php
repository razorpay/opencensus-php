<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Excel;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Gateway\Mozart\NetbankingJsb\ClaimFields;
use RZP\Gateway\Mozart\NetbankingJsb\RefundFields;
use RZP\Models\Gateway\File;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Models\Transaction\Statement\Entity;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingJsbGatewayTest extends NbPlusPaymentServiceNetbankingTest
{
    const REFUND_FIELDS = [
        RefundFields::MERCHANT_CODE,
        RefundFields::MERCHANT_NAME,
        RefundFields::PAYMENT_ID,
        RefundFields::REFUND_AMOUNT,
        RefundFields::CURRENCY,
        RefundFields::BANK_REFERENCE_NUMBER,
        RefundFields::TRANSACTION_DATE,
    ];

    const CLAIM_FIELDS = [
        ClaimFields::PAYMENT_ID,
        ClaimFields::BANK_REFERENCE_NUMBER,
        ClaimFields::CURRENCY,
        ClaimFields::PAYMENT_AMOUNT,
        ClaimFields::STATUS,
        ClaimFields::TRANSACTION_DATE,
        ClaimFields::MERCHANT_CODE,
        ClaimFields::MERCHANT_NAME,
    ];

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingJsbCombinedFileTestData.php';

        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_jsb_terminal');

        $this->bank = 'JSFB';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testNetbankingJsbCombinedFile()
    {
        Mail::fake();

        $paymentArray = $this->payment;

        $payment1 = $this->doAuthAndCapturePayment($paymentArray);

        $transaction1 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction1['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $refundFull = $this->refundPayment($transaction1['entity_id']);

        $paymentEntity1 = $this->getDbLastPayment();

        $refundEntity1 = $this->getDbLastRefund();

        $payment2 = $this->doAuthAndCapturePayment($paymentArray);

        $transaction2 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction2['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $refundPartial = $this->refundPayment($transaction2['entity_id'], 500);

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

        $tran1 = $this->getDbEntityById('transaction', $transaction1['id']);
        $tran2 = $this->getDbEntityById('transaction', $transaction2['id']);

        $this->assertNotNull($tran1[Entity::RECONCILED_AT]);
        $this->assertNotNull($tran2[Entity::RECONCILED_AT]);
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
                    'type' => 'jsb_netbanking_claim',
                ],
                [
                    'type' => 'jsb_netbanking_refund',
                ]
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($refundFull, $refundPartial, $payment1, $payment2)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Jsb Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  => '1000.00',
                    'refunds' => '505.00',
                    'total'   => '495.00'
                ],
                'count' => [
                    'claims'  => 2,
                    'refunds' => 2,
                ],
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkRefundsFile(
                $mail->viewData['refundsFile'],
                $payment1['id'],
                [$refundFull['amount'], $refundPartial['amount']], 3);

            $this->checkClaimFile(
                $mail->viewData['claimsFile'],
                $payment1['id'],
                [$payment1['amount'], $payment2['amount']]);

            $this->assertCount(2, $mail->attachments);

            return true;
        });
    }

    protected function checkRefundsFile(array $refundFileData, $paymentId, $refundAmts, $expectedRefundCount)
    {
        $refundsFileContents = file($refundFileData['url']);

        $this->assertCount($expectedRefundCount, $refundsFileContents);

        $fullRefundRowData = explode('|',$refundsFileContents[1]);
        $fullRefundRowData = array_combine(self::REFUND_FIELDS, $fullRefundRowData);

        $partialRefundRowData = explode('|',$refundsFileContents[2]);
        $partialRefundRowData = array_combine(self::REFUND_FIELDS, $partialRefundRowData);

        $this->assertCount(7, $fullRefundRowData);

        $this->fixtures->stripSign($paymentId);

        $this->assertEquals($paymentId, $fullRefundRowData['MerRefNo']);

        // validating if partial refund amount is reflected in the file
        $refundAmount = number_format($refundAmts[0] / 100, 2, '.', '');
        $this->assertEquals($refundAmount, $fullRefundRowData[RefundFields::REFUND_AMOUNT]);

        $refundAmount = number_format($refundAmts[1] / 100, 2, '.', '');
        $this->assertEquals($refundAmount, $partialRefundRowData[RefundFields::REFUND_AMOUNT]);
    }

    protected function checkClaimFile(array $claimFileData, $paymentId, $paymentAmts)
    {
        $claimFileContents = file($claimFileData['url']);

        $this->assertCount(3, $claimFileContents);

        $PaymentRowData1 = explode('|',$claimFileContents[1]);
        $PaymentRowData1 = array_combine(self::CLAIM_FIELDS, $PaymentRowData1);

        $PaymentRowData2 = explode('|',$claimFileContents[2]);
        $PaymentRowData2 = array_combine(self::CLAIM_FIELDS, $PaymentRowData2);

        $this->fixtures->stripSign($paymentId);

        $this->assertEquals($paymentId, $PaymentRowData1['MerRefNo']);

        $payment1Amount = number_format($paymentAmts[0] / 100, 2, '.', '');
        $this->assertEquals($payment1Amount, $PaymentRowData1[ClaimFields::PAYMENT_AMOUNT]);

        $payment2Amount = number_format($paymentAmts[1] / 100, 2, '.', '');
        $this->assertEquals($payment2Amount, $PaymentRowData2[ClaimFields::PAYMENT_AMOUNT]);
    }
}
