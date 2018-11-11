<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingIdfcCombinedFileTest extends TestCase
{
    use PaymentTrait;

    protected $terminal;

    public function setUp()
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingIdfcCombinedFileTestData.php';

        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_idfc_terminal');
    }

    public function testNetbankingIdfcCombinedFile()
    {
        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('IDFB');

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $files = $this->getEntities('file_store', [
            'count' => 3
        ], true);

        $time = Carbon::now(Timezone::IST)->format('Ymd');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 3,
            'items' => [
                [
                    'type' => 'idfc_netbanking_summary',
                ],
                [
                    'type' => 'idfc_netbanking_claims',
                ],
                [
                    'type' => 'idfc_netbanking_refund',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Idfc Netbanking claims and refund files for '.$date,
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

            $this->checkClaimsFile($mail->viewData['claimsFile']);

            $this->assertCount(3, $mail->attachments);

            return true;
        });
    }

    protected function checkRefundsFile(array $refundFileData)
    {
        $date  = Carbon::today(Timezone::IST)->format('Ymd');

        $testData = [
            'name' => "IDN_REFUND_$date.xlsx"
        ];

        $this->assertArraySelectiveEquals($testData, $refundFileData);
    }

    protected function checkClaimsFile(array $claimFileData)
    {
        $date  = Carbon::today(Timezone::IST)->format('dmY');

        $testData = [
            'name' => "IDFC_RAZORPAY_RECON_$date.xlsx"
        ];

        $this->assertArraySelectiveEquals($testData, $claimFileData);
    }
}
