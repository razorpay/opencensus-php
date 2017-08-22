<?php

namespace RZP\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Mail\Gateway\RefundFile\Constants as RefundFileMailConstants;

class NetbankingIciciRefundFileTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingIciciRefundFileTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_netbanking_icici_terminal');
    }

    public function testNetbankingIciciRefundFile()
    {
        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('ICIC');

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $this->ba->appAuth();

        $content = $this->startTest();

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'icici_netbanking_refund',
            'entity_type' => File\Entity::class,
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertSent(RefundFileMail::class, function ($mail) use ($file)
        {
            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $expectedSubject = RefundFileMailConstants::SUBJECT_MAP[Gateway::NETBANKING_ICICI] . $today;

            $this->assertEquals($expectedSubject, $mail->subject);

            $testData = [
                'body'      => RefundFileMailConstants::BODY_MAP[Gateway::NETBANKING_ICICI],
                'file_name' => "Icici_Netbanking_Refunds_test_$today.xlsx",
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertNotEmpty($mail->attachments);

            return ($mail->hasFrom('refunds@razorpay.com') and
                    ($mail->hasTo(RefundFileMailConstants::RECIPIENT_EMAILS_MAP[Gateway::NETBANKING_ICICI])));
        });
    }
}
