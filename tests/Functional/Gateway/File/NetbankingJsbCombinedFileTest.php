<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Gateway\Mozart\NetbankingJsb\ClaimFields;
use RZP\Gateway\Mozart\NetbankingJsb\RefundFields;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingJsbCombinedFileTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $terminal;

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
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingJsbCombinedFileTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_netbanking_jsb_terminal');

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    public function testNetbankingJsbCombinedFile()
    {
        Mail::fake();

        // full refund
        $paymentArray = $this->getDefaultNetbankingPaymentArray('JSFB');

        $payment1    = $this->doAuthAndCapturePayment($paymentArray);
        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);
        $refundFull    = $this->refundPayment($payment1['id']);
        $refundEntity1 = $this->getDbLastEntity('refund');

        //partial refund
        $payment2    = $this->doAuthAndCapturePayment($paymentArray);
        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);
        $refundPartial   = $this->refundPayment($payment2['id'], 500);
        $refundEntity2   = $this->getDbLastEntity('refund');

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

        $file = $this->getEntities('file_store', [
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
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $file);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($file,$payment1, $payment2, $refundFull, $refundPartial)
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

            //
            // Marking netbanking transaction as reconciled after sending in bank file
            //
            $refundTransaction = $this->getLastEntity('transaction', true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

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
