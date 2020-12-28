<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Excel;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Mail\Gateway\RefundFile\Constants as RefundFileMailConstants;

class NetbankingIbkRefundFileTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingIbkRefundFileTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_netbanking_ibk_terminal');

        $this->markTestSkipped('gateway is migrated to nbplus service');
    }

    public function testNetbankingIbkRefundFile()
    {
        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('IDIB');

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        // Netbanking Equitas refunds have moved to scrooge
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
            'type'        => 'ibk_netbanking_refund',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertQueued(RefundFileMail::class, function ($mail) use ($file)
        {
            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $expectedSubject = RefundFileMailConstants::SUBJECT_MAP[Gateway::NETBANKING_IBK] . $today;

            $this->assertEquals($expectedSubject, $mail->subject);

            $testData = [
                'body'          => RefundFileMailConstants::BODY_MAP[Gateway::NETBANKING_IBK],
                'file_name'     => "Ibk_Netbanking_REFUND_$today.xlsx",
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertNotEmpty($mail->attachments);

            $sheet = Excel::load($mail->attachments[0]['file'])->all()->toArray();

            $this->assertCount(11, $sheet[0]);
            $this->assertEquals($sheet[0]['refund_amount_rs_ps'], 500);

            return ($mail->hasFrom('refunds@razorpay.com') and
                    ($mail->hasTo(RefundFileMailConstants::RECIPIENT_EMAILS_MAP[Gateway::NETBANKING_IBK])));
        });
    }
}
