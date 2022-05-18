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

class NetbankingIciciRefundFileTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/NetbankingIciciRefundFileTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_netbanking_icici_terminal');

        $this->markTestSkipped('this flow is depricated and is moved to nbplus service');
    }

    public function testNetbankingIciciRefundFile()
    {
        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('ICIC');

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        // Netbanking Icici refunds have moved to scrooge
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
            'type'        => 'icici_netbanking_refund',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertQueued(RefundFileMail::class, function ($mail) use ($file)
        {
            $today = Carbon::now(Timezone::IST)->format('d-m-Y');

            $expectedSubject = RefundFileMailConstants::SUBJECT_MAP[Gateway::NETBANKING_ICICI] . $today;

            $this->assertEquals($expectedSubject, $mail->subject);

            $testData = [
                'body'          => RefundFileMailConstants::BODY_MAP[Gateway::NETBANKING_ICICI],
                'file_name'     => "Icici_Netbanking_Refunds_test_$today.xlsx",
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertNotEmpty($mail->attachments);

            $sheet = (new ExcelImport)->toArray($mail->attachments[0]['file'])[0];

            $this->assertCount(10, $sheet[0]);
            $this->assertEquals($sheet[0]['refund_amount'], 500);

            //
            // Marking netbanking transaction as reconciled after sending in bank file
            //
            $refundTransaction = $this->getLastEntity('transaction', true);

            $this->assertNotNull($refundTransaction['reconciled_at']);

            return ($mail->hasFrom('refunds@razorpay.com') and
                    ($mail->hasTo(RefundFileMailConstants::RECIPIENT_EMAILS_MAP[Gateway::NETBANKING_ICICI])));
        });
    }

    public function testRefundFileDirectSettlement()
    {
        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('ICIC');

        $payment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($payment['id']);

        $refundEntity1 = $this->getDbLastEntity('refund');

        $payment = $this->getDefaultNetbankingPaymentArray('ICIC');

        $payment['amount'] = 60000;

        $payment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($payment['id']);

        $refundEntity2 = $this->getDbLastEntity('refund');

        $this->fixtures->terminal->disableTerminal($this->sharedTerminal['id']);

        $this->fixtures->create('terminal:direct_settlement_refund_icici_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray('ICIC');

        $payment = $this->doAuthPayment($payment);

        $this->refundPayment($payment['razorpay_payment_id']);

        $refundEntity3 = $this->getDbLastEntity('refund');

        // Netbanking Icici refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity1['is_scrooge']);
        $this->assertEquals(1, $refundEntity2['is_scrooge']);
        $this->assertEquals(1, $refundEntity3['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity1, $refundEntity2, $refundEntity3]);

        $this->ba->adminAuth();

        $testData = $this->testData['testNetbankingIciciRefundFile'];

        $content = $this->runRequestResponseFlow($testData);

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $files = $this->getEntities('file_store', ['count' => 3], true);

        foreach ($files['items'] as $file)
        {
            $expectedFileContent = [
                'entity_type' => 'gateway_file',
                'entity_id' => $content['id'],
                'extension' => 'xlsx',
            ];

            $this->assertArraySelectiveEquals($expectedFileContent, $file);

            $this->assertContains($file['type'],
                ['icici_netbanking_refund', 'icici_netbanking_refund_direct_settlement', 'icici_netbanking_refund_emi']);

            Mail::assertQueued(RefundFileMail::class, function ($mail) use ($file) {

                $today = Carbon::now(Timezone::IST)->format('d-m-Y');

                $expectedSubject = RefundFileMailConstants::SUBJECT_MAP[Gateway::NETBANKING_ICICI] . $today;

                $this->assertEquals($expectedSubject, $mail->subject);

                $testData = [
                    'body' => RefundFileMailConstants::BODY_MAP[Gateway::NETBANKING_ICICI],
                    'file_name' => "Icici_Netbanking_Refunds_test_$today.xlsx",
                ];

                $this->assertArraySelectiveEquals($testData, $mail->viewData);

                $this->assertNotEmpty($mail->attachments);

                $sheet = (new ExcelImport)->toArray($mail->attachments[0]['file'])[0];

                $this->assertCount(10, $sheet[0]);

                if ((strpos($sheet[0]['bank_reference_no'], 'CFL-') !== false))
                {
                    $this->assertEquals(600, $sheet[0]['refund_amount']);

                }
                else
                {
                    $this->assertEquals(500, $sheet[0]['refund_amount']);
                }

                //
                // Marking netbanking transaction as reconciled after sending in bank file
                //
                $refundTransaction = $this->getLastEntity('transaction', true);

                $this->assertNotNull($refundTransaction['reconciled_at']);

                return ($mail->hasFrom('refunds@razorpay.com') and
                    ($mail->hasTo(RefundFileMailConstants::RECIPIENT_EMAILS_MAP[Gateway::NETBANKING_ICICI])));
            });
        }
    }
}
