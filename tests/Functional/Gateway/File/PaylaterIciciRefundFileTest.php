<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Excel;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Excel\Import as ExcelImport;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Mail\Gateway\RefundFile\Constants as RefundFileMailConstants;

class PaylaterIciciRefundFileTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/PaylaterIciciRefundFileTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:paylater_icici_terminal');

        $this->fixtures->merchant->enablePayLater('10000000000000');

    }

    public function testPaylaterIciciRefundFile()
    {
        Mail::fake();

        $payment = $this->getDefaultPayLaterPaymentArray('icic');

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $this->assertEquals($refund['status'], 'processed');

        $refundEntity = $this->getDbLastEntity('refund');

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

        $today = Carbon::now(Timezone::IST)->format('d-m-Y');

        $expectedFileContent = [
            'type'        => 'icici_paylater_refund',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertQueued(RefundFileMail::class, function ($mail) use ($file)
        {
            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $expectedSubject = RefundFileMailConstants::SUBJECT_MAP[Gateway::PAYLATER_ICICI] . $today;

            $this->assertEquals($expectedSubject, $mail->subject);

            $testData = [
                'body'          => RefundFileMailConstants::BODY_MAP[Gateway::PAYLATER_ICICI],
                'file_name'     => "Icici_Paylater_Refunds$today.xlsx",
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertNotEmpty($mail->attachments);

            $sheet = (new ExcelImport)->toArray($mail->attachments[0]['file'])[0];

            $this->assertCount(10, $sheet[0]);
            $this->assertEquals($sheet[0]['transaction_amount'], 500);

            return ($mail->hasFrom('refunds@razorpay.com') and
                ($mail->hasTo(RefundFileMailConstants::RECIPIENT_EMAILS_MAP[Gateway::PAYLATER_ICICI])));
        });
    }
}
