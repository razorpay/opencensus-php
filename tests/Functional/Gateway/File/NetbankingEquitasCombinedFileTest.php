<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Services\Mock\Scrooge;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingEquitasCombinedFileTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $terminal;

    protected function setUp(): void
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingEquitasCombinedFileTestData.php';

        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_equitas_terminal');

        $connector = $this->mockSqlConnectorWithReplicaLag(0);

        $this->app->instance('db.connector.mysql', $connector);
    }

    public function testNetbankingEquitasCombinedFile()
    {
        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('ESFB');

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $paymentEntity = $this->getDbLastEntityToArray(\RZP\Constants\Entity::PAYMENT);

        $this->fixtures->edit('transaction', $paymentEntity['transaction_id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $refundEntity = $this->getDbLastEntity('refund');

        // Netbanking Equitas refunds have moved to scrooge
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
            'count' => 1
        ], true);

        $time = Carbon::now(Timezone::IST)->format('d-m-y');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 1,
            'items' => [
                [
                    'type' => 'equitas_netbanking_refund',
                    'location' => 'Equitas/Refund/Netbanking/Equitas_Netbanking_Refunds' . '_' . $time . '.txt',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Equitas Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  => 500,
                    'refunds' => 500,
                    'total'   => 0
                ],
                'count' => [
                    'claims'  => 1,
                    'refunds' => 1,
                    'total'   => 2
                ],
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkRefundsFile($mail->viewData['refundsFile']);

            $this->assertCount(1, $mail->attachments);

            return true;
        });
    }

    protected function checkRefundsFile(array $refundFileData)
    {
        $refundsFileContents = file($refundFileData['url']);

        $this->assertCount(2, $refundsFileContents);

        $refundsFileLine1 = explode('|', $refundsFileContents[0]);

        $this->assertCount(9, $refundsFileLine1);
    }
}
