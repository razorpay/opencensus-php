<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingCanaraCombinedFileTest extends TestCase
{
    use PaymentTrait;

    protected $terminal;

    public function setUp()
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingCanaraCombinedFileTestData.php';

        parent::setUp();

        $this->bank = 'CNRB';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_canara_terminal');
    }

    public function testNetbankingCanaraCombinedFile()
    {
        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $date = Carbon::now(Timezone::IST)->format('d_m_Y');

        $expectedFilesContent = [
                                'type'      => 'canara_netbanking_refund',
                                'location'  => 'Canara_Netbanking_Refunds_' . $date . '.txt',
                                'extension' => 'txt'
                                ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $file);

        Mail::assertSent(DailyFileMail::class, function ($mail)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'Canara Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  => '500.00',
                    'refunds' => '500.00',
                    'total'   => '0.00'
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
    }
}
