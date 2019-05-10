<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Models\Gateway\File\Processor\Refund\Sib;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingSibCombinedFileTest extends TestCase
{
    use PaymentTrait;

    protected $terminal;

    const REFUND_FIELDS = [
        Sib::SERIAL_NO,
        Sib::PAYMENT_ID,
        Sib::REFUND_MODE,
        Sib::PAYEE_ID,
        Sib::REFUND_AMOUNT,
        Sib::BANK_REFERENCE_ID
    ];

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingSibCombinedFileTestData.php';

        parent::setUp();

        $this->bank = 'SIBL';

        $this->terminal = $this->fixtures->create('terminal:shared_netbanking_sib_terminal');
    }

    public function testNetbankingSibCombinedFile()
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

        $expectedFilesContent = [
            'type'      => 'sib_netbanking_refund',
            'extension' => 'txt'
        ];

        $this->assertArraySelectiveEquals($expectedFilesContent, $file);

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($payment, $refund)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'SIB Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  => '500.00',
                    'refunds' => '500.00',
                    'total'   => '0.00'
                ],
                'count' => [
                    'claims'  => 1,
                    'refunds' => 1,
                ],
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkRefundsFile($mail->viewData['refundsFile'], $payment, $refund);

            $this->assertCount(1, $mail->attachments);

            return true;
        });
    }


    protected function checkRefundsFile(array $refundFileData, $payment, $refund)
    {
        $refundsFileContents = file($refundFileData['url']);

        $this->assertCount(1, $refundsFileContents);

        $refundFileRowData = explode('||',$refundsFileContents[0]);

        $this->assertCount(6, $refundFileRowData);

        $refundFileRowData = array_combine(self::REFUND_FIELDS, $refundFileRowData);

        $this->fixtures->stripSign($payment['id']);

        $this->assertEquals($payment['id'], $refundFileRowData[Sib::PAYMENT_ID]);

        $refundAmount = number_format($refund['amount'] / 100, 2, '.', '');

        $this->assertEquals($refundAmount, $refundFileRowData[Sib::REFUND_AMOUNT]);
    }
}
