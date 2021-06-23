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

class NetbankingRblCombinedFileTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NebtankingRblCombinedFileTestData.php';

        $this->markTestSkipped('this flow is depricated and is moved to nbplus service');

        parent::setUp();

        $this->fixtures->create('terminal:shared_netbanking_rbl_terminal');

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    public function testGenerateCombinedFile()
    {
        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('RATN');

        $payment['amount'] = 10000012;

        $payment = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $refund = $this->refundPayment($payment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        // Netbanking Rbl refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity]);

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

        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'rbl_netbanking_claim',
                    'location' => 'Rbl/Claims/Netbanking/Rbl_Netbanking_Claims_test' . '_' . $time . '.txt',
                ],
                [
                    'type' => 'rbl_netbanking_refund',
                    'location' => 'Rbl/Refund/Netbanking/Rbl_Netbanking_Refunds_test' . '_' . $time . '.xlsx',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Rbl Netbanking claims and refund files for '. $date,
                'count' => [
                    'claims' => 1,
                    'refunds' => 1,
                ]
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertFileExists($mail->viewData['claimsFile']['url']);

            $this->assertFileExists($mail->viewData['refundsFile']['url']);

            $this->assertCount(2, $mail->attachments);

            //
            // Marking netbanking transaction as reconciled after sending in bank file
            //
            $refundTransaction = $this->getLastEntity('transaction', true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

            $this->assertNotNull($refundTransaction['reconciled_type']);

            return true;
        });
    }
}
