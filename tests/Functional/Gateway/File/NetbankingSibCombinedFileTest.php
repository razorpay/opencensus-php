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

        // full refund
        $paymentArray    = $this->getDefaultNetbankingPaymentArray($this->bank);

        $payment1     = $this->doAuthAndCapturePayment($paymentArray);
        $refundFull   = $this->refundPayment($payment1['id']);

        //partial refund
        $payment2        = $this->doAuthAndCapturePayment($paymentArray);
        $refundPartial   = $this->refundPayment($payment2['id'], 500);

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

        Mail::assertSent(DailyFileMail::class, function ($mail) use ($payment1, $payment2, $refundFull, $refundPartial)
        {
            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $testData = [
                'subject' => 'SIB Netbanking claims and refund files for '.$date,
                'amount' => [
                    'claims'  => '1000.00',
                    'refunds' => '505.00',
                    'total'   => '495.00'
                ],
                'count' => [
                    'claims'  => 2,
                    'refunds' => 2,
                ],
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->checkRefundsFile(
                                    $mail->viewData['refundsFile'],
                                    $payment1['id'],
                                    [$refundFull['amount'], $refundPartial['amount']]);

            $this->assertCount(1, $mail->attachments);

            return true;
        });
    }


    protected function checkRefundsFile(array $refundFileData, $paymentId, $refundAmts)
    {
        $refundsFileContents = file($refundFileData['url']);

        $this->assertCount(2, $refundsFileContents);

        $fullRefundRowData = explode('||',$refundsFileContents[0]);
        $fullRefundRowData = array_combine(self::REFUND_FIELDS, $fullRefundRowData);

        $partialRefundRowData = explode('||',$refundsFileContents[1]);
        $partialRefundRowData = array_combine(self::REFUND_FIELDS, $partialRefundRowData);

        $this->assertCount(6, $fullRefundRowData);

        $this->fixtures->stripSign($paymentId);

        $this->assertEquals($paymentId, $fullRefundRowData[Sib::PAYMENT_ID]);


        // validating if partial refund amount is reflected in the file
        $refundAmount = number_format(($refundAmts[0]) / 100, 2, '.', '');
        $this->assertEquals($refundAmount, $fullRefundRowData[Sib::REFUND_AMOUNT]);

        $refundAmount = number_format(($refundAmts[1]) / 100, 2, '.', '');
        $this->assertEquals($refundAmount, $partialRefundRowData[Sib::REFUND_AMOUNT]);
    }
}
