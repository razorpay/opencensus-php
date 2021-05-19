<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Excel;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Mozart\NetbankingCub;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingCubCombinedFileTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $terminal;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingCubCombinedFileTestData.php';

        parent::setUp();

        $this->bank = 'CIUB';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_cub_terminal');

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);

        $this->markTestSkipped('this flow is depricated and is moved to nbplus service');
    }

    public function testNetbankingCubCombinedFile()
    {
        Mail::fake();

        list($payment1, $fullRefund) = $this->createRefund();

        $refundEntity1 = $this->getDbLastEntity('refund');

        list($payment2, $partialRefund) = $this->createRefund(500);

        $refundEntity2 = $this->getDbLastEntity('refund');

        // Netbanking CUB refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity1['is_scrooge']);
        $this->assertEquals(1, $refundEntity2['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity1, $refundEntity2]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

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

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($payment1, $fullRefund, $partialRefund, $payment2)
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
                $payment1,
                $fullRefund,
                $partialRefund);

            $this->checkClaimFile(
                $mail->viewData['claimsFile'],
                $payment1,
                $payment2);

            $this->assertCount(2, $mail->attachments);
            //
            // Marking netbanking transaction as reconciled after sending in bank file
            //
            $refundTransaction = $this->getLastEntity('transaction', true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

            return true;
        });
    }


    protected function checkRefundsFile(array $refundFileData, $payment, $fullRefund, $partialRefund)
    {
        $refundsFileContents = file($refundFileData['url']);

        $this->assertCount(2, $refundsFileContents);

        $fullRefundRowData = explode('|',$refundsFileContents[0]);
        $fullRefundRowData = array_combine(NetbankingCub\RefundFields::REFUND_FIELDS, $fullRefundRowData);

        $partialRefundRowData = explode('|',$refundsFileContents[1]);
        $partialRefundRowData = array_combine(NetbankingCub\RefundFields::REFUND_FIELDS, $partialRefundRowData);

        $this->assertCount(6, $fullRefundRowData);

        $this->fixtures->stripSign($payment['id']);

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

        $payment1RowData = explode('~',$claimFileContents[1]);
        $payment1RowData = array_combine(NetbankingCub\ClaimFields::COLUMNS, $payment1RowData);

        $payment2RowData = explode('~',$claimFileContents[2]);
        $payment2RowData = array_combine(NetbankingCub\ClaimFields::COLUMNS, $payment2RowData);

        $this->assertCount(4, $payment1RowData);

        $this->fixtures->stripSign($payment1['id']);
        $this->assertEquals($payment1['id'], $payment1RowData[NetbankingCub\ClaimFields::PAYMENT_ID]);

        $this->fixtures->stripSign($payment2['id']);
        $this->assertEquals($payment2['id'], $payment2RowData[NetbankingCub\ClaimFields::PAYMENT_ID]);

        $paymentAmount = number_format($payment1['amount'] / 100, 2, '.', '');

        $this->assertEquals($paymentAmount, $payment1RowData[NetbankingCub\ClaimFields::TRANSACTION_AMOUNT]);
    }

    protected function createRefund($amount = -1)
    {
        $payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $payment = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        // -1 for full refund
        if ($amount === -1)
        {
            $refund = $this->refundPayment($payment['id']);
        }
        else
        {
            $refund = $this->refundPayment($payment['id'], $amount);
        }

        return [$payment, $refund];
    }
}
