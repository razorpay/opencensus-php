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

class NetbankingCorpCombinedFileTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->markTestSkipped();

        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingCorpCombinedFileTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_netbanking_corporation_terminal');
    }

    public function testNetbankingCorpcombinedFile()
    {
        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('CORP');

        $payment = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $refund = $this->refundPayment($payment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        // Netbanking Corporation refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'corporation_netbanking_refund',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'txt',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertSent(DailyFileMail::class, function ($mail)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Corporation Netbanking claims and refund files for '. $date,
                'amount' => [
                    'claims'  => 500,
                    'refunds' => 500,
                    'total'   => 0,
                ],
                'count'   => [
                    'claims'  => 1,
                    'refunds' => 1,
                ]
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkRefundsFile($mail->viewData['refundsFile']);

            $this->assertCount(1, $mail->attachments);

            //
            // Marking netbanking transaction as reconciled after sending in bank file
            //
            $refundTransaction = $this->getLastEntity('transaction', true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

            return true;
        });
    }

    protected function checkRefundsFile(array $refundsFileData)
    {
        $refund = $this->getDbLastEntity('refund');

        $date = Carbon::today(Timezone::IST)->format('dmY');

        $this->assertFileExists($refundsFileData['url']);

        $refundsFileContents = file($refundsFileData['url']);

        $this->assertCount(3, $refundsFileContents); // 3 because this includes header and footer

        $refundsFileRow = explode('|', $refundsFileContents[1]);

        $this->assertCount(10, $refundsFileRow);

        $this->assertEquals(trim($refundsFileRow[1]), $date);

        $this->assertEquals(trim($refundsFileRow[7]), $date);

        $this->assertEquals(trim($refundsFileRow[4]), '500.00');

        $this->assertEquals(trim($refundsFileRow[6]), '500.00');

        $this->assertEquals(trim($refundsFileRow[9]), $refund['id']);
    }
}
