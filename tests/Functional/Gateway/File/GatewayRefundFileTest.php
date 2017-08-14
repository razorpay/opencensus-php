<?php

namespace RZP\Functional\Gateway\File;

use Mail;
use Excel;
use Mockery;
use Carbon\Carbon;
use RZP\Models\Gateway\File;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Gateway\File\ProcessorFactory;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Mail\Gateway\RefundFile\Constants as RefundFileMailConstants;

class GatewayRefundFileTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/GatewayRefundFileTestData.php';

        parent::setUp();
    }

    public function testRefundFileProcessor()
    {
        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $this->ba->appAuth();

        $content = $this->startTest();

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        Mail::assertSent(RefundFileMail::class, function ($mail)
        {
            $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

            $expectedSubject = RefundFileMailConstants::SUBJECT_MAP[Gateway::NETBANKING_HDFC] . $today;

            $this->assertEquals($expectedSubject, $mail->subject);

            return ($mail->hasFrom('refunds@razorpay.com') and
                    ($mail->hasTo(RefundFileMailConstants::RECIPIENT_EMAILS_MAP[Gateway::NETBANKING_HDFC])));
        });

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'hdfc_netbanking_refund',
            'entity_type' => File\Entity::class,
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);
    }

    public function testProcessGatewayFileWithInvalidType()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testProcessGatewayFileWithInvalidGateway()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testProcessGatewayFileWithInvalidBank()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testProcessGatewayFileWithInvalidRecipients()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testProcessGatewayFileStartingInFuture()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testProcessGatewayFileWithInvalidTimeRange()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testRefundFileProcessorWithCustomRecipients()
    {
        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $this->ba->appAuth();

        $content = $this->startTest();

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        Mail::assertSent(RefundFileMail::class, function ($mail)
        {
            $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

            $expectedSubject = RefundFileMailConstants::SUBJECT_MAP[Gateway::NETBANKING_HDFC] . $today;

            $this->assertEquals($expectedSubject, $mail->subject);

            return ($mail->hasFrom('refunds@razorpay.com') and
                    ($mail->hasTo(['test@razorpay.com'])));
        });

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'hdfc_netbanking_refund',
            'entity_type' => File\Entity::class,
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);
    }

    public function testRefundFileProcessorWithNoRefundData()
    {
        Mail::fake();

        $this->ba->appAuth();

        $content = $this->startTest();

        $this->assertNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNull($content[File\Entity::SENT_AT]);
        $this->assertNotNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        Mail::assertNotSent(RefundFileMail::class);
    }

    public function testRefundFileProcessorWithFileGenerationError()
    {
        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        Mail::fake();

        Excel::shouldReceive('create')->andThrow(new \Exception('file_generation_exception'));

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $this->ba->appAuth();

        $content = $this->startTest();

        $this->assertNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNull($content[File\Entity::SENT_AT]);
        $this->assertNotNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        Mail::assertNotSent(RefundFileMail::class);

        $file = $this->getLastEntity('file_store', true);

        $this->assertNull($file);
    }

    public function testRefundFileProcessingWithMailSendError()
    {
        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        Mail::shouldReceive('send')->andThrow(new \Exception('mail_send_exceptiopn'));

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $this->ba->appAuth();

        $content = $this->startTest();

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNull($content[File\Entity::SENT_AT]);
        $this->assertNotNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'hdfc_netbanking_refund',
            'entity_type' => File\Entity::class,
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);
    }

    public function testRefundFileFileGenErrorRetryProcessing()
    {
        $this->markTestSkipped();
        $this->testRefundFileProcessorWithFileGenerationError();

        ProcessorFactory::flushProcessors();

        Mockery::close();
        Mail::fake();

        $gatewayFile = $this->getLastEntity('gateway_file', true);

        $this->testData[__FUNCTION__]['request']['url'] = '/gateway/files/' . $gatewayFile['id'] . '/retry';

        $this->ba->appAuth();

        $content = $this->startTest();

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        Mail::assertSent(RefundFileMail::class, function ($mail)
        {
            $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

            $expectedSubject = RefundFileMailConstants::SUBJECT_MAP[Gateway::NETBANKING_HDFC] . $today;

            $this->assertEquals($expectedSubject, $mail->subject);

            return ($mail->hasFrom('refunds@razorpay.com') and
                    ($mail->hasTo(RefundFileMailConstants::RECIPIENT_EMAILS_MAP[Gateway::NETBANKING_HDFC])));
        });

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'hdfc_netbanking_refund',
            'entity_type' => File\Entity::class,
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);
    }

    public function testRefundFileMailSendErrorRetryProcessing()
    {
        $this->testRefundFileProcessingWithMailSendError();

        ProcessorFactory::flushProcessors();

        Mail::fake();

        $gatewayFile = $this->getLastEntity('gateway_file', true);

        $this->testData[__FUNCTION__]['request']['url'] = '/gateway/files/' . $gatewayFile['id'] . '/retry';

        $this->ba->appAuth();

        $content = $this->startTest();

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNotNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        Mail::assertSent(RefundFileMail::class, function ($mail)
        {
            $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

            $expectedSubject = RefundFileMailConstants::SUBJECT_MAP[Gateway::NETBANKING_HDFC] . $today;

            $this->assertEquals($expectedSubject, $mail->subject);

            return ($mail->hasFrom('refunds@razorpay.com') and
                    ($mail->hasTo(RefundFileMailConstants::RECIPIENT_EMAILS_MAP[Gateway::NETBANKING_HDFC])));
        });

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'hdfc_netbanking_refund',
            'entity_type' => File\Entity::class,
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);
    }

    public function testGatewayFileAcknowledge()
    {
        $this->testRefundFileProcessor();

        $gatewayFile = $this->getLastEntity('gateway_file', true);

        $this->testData[__FUNCTION__]['request']['url'] = '/gateway/files/' . $gatewayFile['id'] . '/acknowledge';

        $this->ba->appAuth();

        $content = $this->startTest();

        $this->assertNotNull($content[File\Entity::ACKNOWLEDGED_AT]);
    }

    public function testGatewayFileAcknowledgePartiallyProcessed()
    {
        $this->testRefundFileProcessor();

        $gatewayFile = $this->getLastEntity('gateway_file', true);

        $this->testData[__FUNCTION__]['request']['url'] = '/gateway/files/' . $gatewayFile['id'] . '/acknowledge';

        $this->ba->appAuth();

        $content = $this->startTest();

        $this->assertNotNull($content[File\Entity::ACKNOWLEDGED_AT]);
    }

    public function testGenerateGatewayFilesBulk()
    {
        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        Mail::fake();

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $this->ba->appAuth();

        $content = $this->startTest();
    }

    public function testGenerateGatewayFilesBulkWithInvalidType()
    {
        $this->ba->appAuth();

        $this->startTest();
    }
}
