<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Payment\NbPlusPaymentServiceNetbankingTest;

class NbplusNetbankingFederalCombinedFileTest extends NbPlusPaymentServiceNetbankingTest
{

    protected function setUp(): void
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NebtankingFederalCombinedFileTestData.php';

        parent::setUp();

        /**
         * @var array
         */
        $this->fixtures->create('terminal:shared_netbanking_federal_terminal');

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);
    }

    public function testGenerateCombinedFile()
    {
        $this->markTestSkipped("Combined file in not required for nbplus gateway integration, claims are settled through dashboard provided by bank");

        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('FDRL');

        $payment = $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getDbLastEntityToArray(\RZP\Constants\Entity::PAYMENT);

        $this->fixtures->edit('transaction', $paymentEntity['transaction_id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $refund = $this->refundPayment($payment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        // Netbanking Federal refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity]);

        $this->ba->adminAuth();

        $content = $this->startTest();
        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $date = Carbon::now(Timezone::IST)->format('d_m_Y');

        $expectedFilesContent = [
            'type'      => 'federal_netbanking_refund',
            'location'  => 'FBK_REFUND_' . $date . '.txt',
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $file);

        Mail::assertSent(DailyFileMail::class, function ($mail) {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Federal Netbanking claims and refund files for ' . $date,
                'amount' => [
                    'claims'  => 500,
                    'refunds' => 500,
                    'total'   => 0,
                ],
                'count' => [
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
        $this->assertFileExists($refundsFileData['url']);

        $refundsFileContents = file($refundsFileData['url']);

        $this->assertCount(1, $refundsFileContents);

        $refundsFileRow = explode('|', $refundsFileContents[0]);

        $this->assertCount(7, $refundsFileRow);

        $this->assertEquals($refundsFileRow[3], '00000000');

        $this->assertEquals(trim($refundsFileRow[6]), 500);
    }
}
