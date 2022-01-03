<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingCanaraCombinedFileTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $terminal;

    protected function setUp(): void
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingCanaraCombinedFileTestData.php';

        parent::setUp();

        $this->bank = 'CNRB';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_canara_terminal');

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);

        $this->markTestSkipped('this flow is depricated and is moved to nbplus service');
    }

    public function testNetbankingCanaraCombinedFile()
    {
        Mail::fake();

        $payment  = $this->getDefaultNetbankingPaymentArray($this->bank);
        $payment1 = $this->doAuthAndCapturePayment($payment);

        $transaction1 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction1['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $payment = $this->getDefaultNetbankingPaymentArray($this->bank);
        $payment2 = $this->doAuthAndCapturePayment($payment);

        $transaction2 = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction2['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $refundFull    = $this->refundPayment($payment1['id']);

        $refundEntity1 = $this->getDbLastEntity('refund');

        $refundPartial = $this->refundPayment($payment2['id'], 500);

        $refundEntity2 = $this->getDbLastEntity('refund');

        // Netbanking Canara refunds have moved to scrooge
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

        $date = Carbon::now(Timezone::IST)->format('d_m_Y');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'canara_netbanking_claims',
                ],
                [
                    'type'      => 'canara_netbanking_refund',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($payment1, $refundFull, $refundPartial)
        {
            $date = Carbon::today(Timezone::IST)->format('dmY');

            $testData = [
                'subject' => 'PG RECON DATA '. $date,
                'amount' => [
                    'claims'  => '1000.00',
                    'refunds' => '505.00',
                    'total'   => '495.00',
                ],
                'count' => [
                    'claims'  => 2,
                    'refunds' => 2,
                    'total'   => 4
                ],
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkRefundsFile($mail->viewData['refundsFile'], $payment1, $refundFull, $refundPartial);

            $this->checkClaimsFile($mail->viewData['claimsFile'], $payment1);

            $this->assertCount(2, $mail->attachments);

            return true;
        });
    }

    protected function checkRefundsFile(array $refundFileData, $payment, $refundfull, $refundPartial)
    {
        $refundsFileContents = file($refundFileData['url']);

        $this->assertCount(3, $refundsFileContents);

        $rowFullRefund    = explode('|', $refundsFileContents[1]);
        $rowPartialRefund = explode('|', $refundsFileContents[2]);

        $this->assertCount(7, $rowFullRefund);

        $this->fixtures->stripSign($payment['id']);
        $this->fixtures->stripSign($refundfull['id']);

        $this->assertEquals($payment['id'], $rowFullRefund[3]);

        $this->assertEquals($refundfull['id'], $rowFullRefund[4]);

        $this->assertEquals(
                 number_format(
                     $payment['amount'] / 100,
                     2,
                     '.',
                     ''
                 ),
                 $rowFullRefund[5]
               );

        $this->assertEquals(
                 number_format(
                     $refundfull['amount'] / 100,
                     2,
                     '.',
                     ''
                 ),
                 trim($rowFullRefund[6])
               );

        $this->assertEquals(
            number_format(
                $refundPartial['amount'] / 100,
                2,
                '.',
                ''
            ),
            trim($rowPartialRefund[6])
        );
    }

    protected function checkClaimsFile(array $claimsFileData, $payment)
    {
        $claimsFileContents = file($claimsFileData['url']);

        $this->assertCount(3, $claimsFileContents);

        $row = explode('|', $claimsFileContents[1]);

        $this->assertCount(5, $row);

        $this->fixtures->stripSign($payment['id']);

        $this->assertEquals($payment['id'], $row[0]);

        $this->assertEquals(number_format($payment['amount'] / 100, 2, '.', ''), $row[3]);
    }
}
