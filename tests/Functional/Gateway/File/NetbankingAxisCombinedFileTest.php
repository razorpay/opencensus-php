<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingAxisCombinedFileTest extends TestCase
{
    use PaymentTrait;

    protected $terminal;

    public function setUp()
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingAxisCombinedFileTestData.php';

        parent::setUp();

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_axis_terminal');
    }

    public function testNetbankingAxisCombinedFile()
    {
        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('UTIB');

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $this->ba->appAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $files = $this->getEntities('file_store', [
            'count' => 2
        ], true);

        $time = Carbon::now(Timezone::IST)->format('Ymd');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'axis_netbanking_claims',
                    'location' => 'IConnect_Claim_RAZORPAY' . '_' . $time . '_' . 'test_1.txt'
                ],
                [
                    'type' => 'axis_netbanking_refund',
                    'location' => 'IConnect_Refund_RAZORPAY' . '_' . $time . '_' . 'test_1.txt',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Axis Netbanking claims and refund files for '.$date,
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

            $this->assertCount(2, $mail->attachments);

            return true;
        });
    }

    public function testNetbankingAxisCombinedFileForCorporate()
    {
        Mail::fake();

        $this->fixtures->terminal->edit($this->terminal->getId(), ['corporate' => 1]);

        $payment = $this->getDefaultNetbankingPaymentArray('UTIB');

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $this->ba->appAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $files = $this->getEntities('file_store', [
            'count' => 2
        ], true);

        $time = Carbon::now(Timezone::IST)->format('Ymd');

        $expectedFilesContent = [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                [
                    'type' => 'axis_netbanking_claims',
                    'location' => 'IConnect_Claim_RAZORPAY_CORP' . '_' . $time . '_' . 'test_1.txt'
                ],
                [
                    'type' => 'axis_netbanking_refund',
                    'location' => 'IConnect_Refund_RAZORPAY_CORP' . '_' . $time . '_' . 'test_1.txt',
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $files);

        Mail::assertSent(DailyFileMail::class, function ($mail)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Corporate Axis Netbanking claims and refund files for '.$date,
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

            $this->assertCount(2, $mail->attachments);

            return true;
        });

        $this->fixtures->terminal->edit($this->terminal->getId(), ['corporate' => 0]);
    }

    protected function checkRefundsFile(array $refundFileData)
    {
        $refundsFileContents = file($refundFileData['url']);

        $this->assertCount(2, $refundsFileContents);

        $refundsFileLine1 = explode('~~', $refundsFileContents[1]);

        $this->assertCount(8, $refundsFileLine1);
    }

    protected function checkClaimsFile(array $claimsFileData)
    {
        $claimsFileContents = file($claimsFileData['url']);

        $this->assertCount(2, $claimsFileContents);

        $claimsFileLine1 = explode('~~', $claimsFileContents[1]);

        $this->assertCount(7, $claimsFileLine1);
    }
}
