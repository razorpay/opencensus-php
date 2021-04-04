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

class NetbankingVijayaCombinedFileTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $terminal;

    protected $bank = 'VIJB';

    protected function setUp(): void
    {
        $this->markTestSkipped();

        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingVijayaCombinedFileTestData.php';

        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_vijaya_terminal');

        $this->markTestSkipped();
    }

    public function testNetbankingVijayaCombinedFile()
    {
        Mail::fake();

        foreach (range(1,3) as $i)
        {
            $this->createReconciledPayment();
        }

        $payment = $this->getLastEntity('payment', true);

        $this->fixtures->edit('payment', $payment['id'], [
            'created_at' => Carbon::tomorrow(Timezone::IST)->addHours(4)->timestamp
        ]);

        $this->refundPayment($payment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        // Netbanking Vijaya refunds have moved to scrooge
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

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'vijaya_netbanking_claim',
                ],
                [
                    'type' => 'vijaya_netbanking_refund',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Vijaya Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  => '1500.00',
                    'refunds' => '500.00',
                    'total'   => '1000.00'
                ],
                'count' => [
                    'claims'  => 3,
                    'refunds' => 1,
                    'total'   => 4
                ],
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkRefundsFile($mail->viewData['refundsFile']);

            $this->checkClaimsFile($mail->viewData['claimsFile']);

            $this->assertCount(2, $mail->attachments);

            return true;
        });
    }


    protected function checkRefundsFile(array $refundFileData)
    {
        $refundsFileContents = file($refundFileData['url']);

        $refundsFileLine1 = explode('||', $refundsFileContents[0]);

        $this->assertCount(6, $refundsFileLine1);

        $this->assertEquals($refundsFileLine1[2], 'RFND');

        $this->assertEquals($refundsFileLine1[3], 'VijayaBank');

        $this->assertEquals($refundsFileLine1[4], '500.00');
    }

    protected function checkClaimsFile(array $claimsFileData)
    {
        $date = Carbon::today(Timezone::IST)->format('dmY');

        $name = 'RazorPay-MIS-' . $date . '.xls';

        $this->assertEquals($claimsFileData['name'], $name);
    }

    protected function createReconciledPayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $payment = $this->doAuthAndCapturePayment($payment);

        $transaction = $this->getLastEntity('transaction', true);

        $this->fixtures->edit('transaction', $transaction['id'], [
            'reconciled_at' => Carbon::tomorrow(Timezone::IST)->addHours(8)->timestamp
        ]);

        return $payment;
    }
}
