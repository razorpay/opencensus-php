<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingSbiCombinedFileTest extends TestCase
{
    use PaymentTrait;

    protected $terminal;

    public function setUp()
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingSbiCombinedFileTestData.php';

        parent::setUp();

        $this->setMockGatewayTrue();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_sbi_terminal');
    }

    public function testGenerateCombinedFile()
    {
        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('SBIN');

        $payment = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        $this->refundPayment($payment['id']);

        $this->ba->adminAuth();

        $content = $this->startTest();
        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $files = $this->getEntities('file_store', ['count' => 2], true);

        $date = Carbon::now(Timezone::IST)->format('dmY');

        $rfDate = Carbon::now(Timezone::IST)->format('d.m.y');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'sbi_netbanking_claim',
                    'location' => 'SBI_CLAIM' . '_' . $date . '.txt'
                ],
                [
                    'type' => 'sbi_netbanking_refund',
                    'location' => 'RZPY_SBI_Refund' . '_' . $rfDate . '.txt'
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Sbi Netbanking claims and refund files for '. $date,
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

            $this->assertCount(2, $mail->attachments);

            return true;
        });
    }

    protected function checkRefundsFile(array $refundsFileData)
    {
        $this->assertFileExists($refundsFileData['url']);

        $refundsFileContents = file($refundsFileData['url']);

        $this->assertCount(1, $refundsFileContents);

        $refundsFileRow = explode('|', $refundsFileContents[0]);

        $this->assertCount(6, $refundsFileRow);

        $this->assertEquals($refundsFileRow[4], 500);
    }
}
