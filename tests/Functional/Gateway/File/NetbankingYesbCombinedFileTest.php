<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Excel;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Tests\Functional\TestCase;
use RZP\Excel\Import as ExcelImport;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingYesbCombinedFileTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $terminal;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingYesbCombinedFileTestData.php';

        parent::setUp();

        $this->bank = 'YESB';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_yesb_terminal');

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);

        $this->markTestSkipped('this flow is depricated and is moved to nbplus service');
    }

    public function testNetbankingYesbCombinedFile()
    {
        Mail::fake();

        list($payment1, $fullRefund) = $this->createRefund();

        $refundEntity1 = $this->getDbLastEntity('refund');

        $this->createRefund(500);

        $refundEntity2 = $this->getDbLastEntity('refund');

        // Netbanking Scb refunds have moved to scrooge
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
                    'type' => 'yesb_netbanking_claim',
                ],
                [
                    'type' => 'yesb_netbanking_refund',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($payment1, $fullRefund)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Yesbank Netbanking claims and refund files for '.$date,
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

            $claimSheet = (new ExcelImport)->toArray($mail->attachments[0]['file'])[0];

            $refundSheet = (new ExcelImport)->toArray($mail->attachments[1]['file'])[0];


            $this->checkRefundsFile($refundSheet[0], $payment1, $fullRefund);

            $this->checkClaimFile($claimSheet[0], $payment1);

            $this->assertCount(2, $mail->attachments);
            //
            // Marking netbanking transaction as reconciled after sending in bank file
            //
            $refundTransaction = $this->getLastEntity('transaction', true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

            return true;
        });
    }


    protected function checkRefundsFile(array $refundData, $payment, $refund)
    {
        $this->assertCount(6, $refundData);

        $this->fixtures->stripSign($payment['id']);

        $this->assertEquals($payment['id'], $refundData['merchant_ref_no']);

        $refundAmount = number_format($refund['amount'] / 100, 2, '.', '');

        $paymentAmount = number_format($payment['amount'] / 100, 2, '.', '');

        $this->assertEquals($refundAmount, $refundData['refund_amount']);

        $this->assertEquals($paymentAmount, $refundData['transaction_amount']);
    }

    protected function checkClaimFile(array $claimData, $payment)
    {
        $this->assertCount(5, $claimData);

        $this->fixtures->stripSign($payment['id']);

        $this->assertEquals($payment['id'], $claimData['merchant_ref_no']);

        $paymentAmount = number_format($payment['amount'] / 100, 2, '.', '');

        $this->assertEquals($paymentAmount, $claimData['transaction_amount']);
    }

    protected function createRefund($amount = -1)
    {
        $payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $payment = $this->doAuthAndCapturePayment($payment);

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
