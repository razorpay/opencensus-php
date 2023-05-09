<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Mockery;
use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Mail\Gateway\RefundFile\Constants as RefundFileMailConstants;

class NetbankingHdfcRefundFileTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingHdfcRefundFileTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $this->app['rzp.mode'] = Mode::TEST;
        $this->nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlus\Netbanking', [$this->app])->makePartial();
        $this->app->instance('nbplus.payments', $this->nbPlusService);
    }

    public function testNetbankingHdfcRefundFile()
    {
        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        // Netbanking Hdfc refunds have moved to scrooge
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
            'type'        => 'hdfc_netbanking_refund',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertQueued(RefundFileMail::class, function ($mail) use ($file)
        {
            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $expectedSubject = RefundFileMailConstants::SUBJECT_MAP[Gateway::NETBANKING_HDFC] . $today;

            $this->assertEquals($expectedSubject, $mail->subject);

            $testData = [
                'body'        => RefundFileMailConstants::BODY_MAP[Gateway::NETBANKING_HDFC],
                'file_name'   => "HDFC_Netbanking_Refunds_test_$today.xlsx",
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertNotEmpty($mail->attachments);

            //
            // Marking netbanking transaction as reconciled after sending in bank file
            //
            $refundTransaction = $this->getLastEntity('transaction', true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

            return ($mail->hasFrom('refunds@razorpay.com') and
                    ($mail->hasTo(RefundFileMailConstants::RECIPIENT_EMAILS_MAP[Gateway::NETBANKING_HDFC])));
        });
    }

    public function testGenerateNetbankingHdfcRefundFile()
    {
        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        // Netbanking Hdfc refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $file = $this->getLastEntity('file_store', true);

        $today = Carbon::now(Timezone::IST)->format('d-m-Y');

        $expectedFileContent = [
            'name' => 'Hdfc/Refund/Netbanking/HDFC_Netbanking_Refunds_test_'.$today,
            'location' => 'Hdfc/Refund/Netbanking/HDFC_Netbanking_Refunds_test_'.$today.'.xlsx'
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertQueued(RefundFileMail::class, function ($mail) use ($file)
        {
            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $expectedSubject = RefundFileMailConstants::SUBJECT_MAP[Gateway::NETBANKING_HDFC] . $today;

            $this->assertEquals($expectedSubject, $mail->subject);

            $testData = [
                'body'        => RefundFileMailConstants::BODY_MAP[Gateway::NETBANKING_HDFC],
                'file_name'   => "HDFC_Netbanking_Refunds_test_$today.xlsx",
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertNotEmpty($mail->attachments);

            //
            // Marking netbanking transaction as reconciled after sending in bank file
            //
            $refundTransaction = $this->getLastEntity('transaction', true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

            return ($mail->hasFrom('refunds@razorpay.com') and
                ($mail->hasTo(RefundFileMailConstants::RECIPIENT_EMAILS_MAP[Gateway::NETBANKING_HDFC])));
        });
    }
}
