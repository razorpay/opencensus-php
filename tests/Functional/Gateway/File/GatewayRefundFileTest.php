<?php

namespace RZP\Functional\Gateway\File;

use Mail;
use Excel;
use Mockery;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;
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

    public function testProcessRefundFile()
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

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'hdfc_netbanking_refund',
            'entity_type' => File\Entity::class,
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertSent(RefundFileMail::class);
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

    public function testProcessRefundFileWithCustomRecipients()
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

        Mail::assertSent(RefundFileMail::class);
    }

    public function testProcessRefundFileWithNoRefundData()
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

    public function testProcessRefundFileWithFileGenerationError()
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

    public function testProcessRefundFileWithMailSendError()
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
        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        Mail::fake();

        $gatewayFile = $this->fixtures->create('gateway_file', [
            'gateway'      => 'netbanking_hdfc',
            'bank'         => 'HDFC',
            'type'         => 'refund',
            'sender'       => 'refunds@razorpay.com',
            'status'       => 'failed',
            'failure_code' => 'error_creating_file',
            'failed_at'    => time(),
            'attempts'     => 1,
            'from'         => Carbon::today(Timezone::IST)->timestamp,
            'to'           => Carbon::tomorrow(Timezone::IST)->timestamp,
        ]);

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $this->testData[__FUNCTION__]['request']['url'] = '/gateway/files/' . $gatewayFile->getId() . '/retry';

        $this->ba->appAuth();

        $content = $this->startTest();

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNotNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        Mail::assertSent(RefundFileMail::class);

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
        $this->testProcessRefundFileWithMailSendError();

        Mail::fake();

        $gatewayFile = $this->getLastEntity('gateway_file', true);

        $this->testData[__FUNCTION__]['request']['url'] = '/gateway/files/' . $gatewayFile['id'] . '/retry';

        $this->ba->appAuth();

        $content = $this->startTest();

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNotNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        Mail::assertSent(RefundFileMail::class);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'hdfc_netbanking_refund',
            'entity_type' => File\Entity::class,
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);
    }

    /**
     * Retries processing of a refund file with no refunds available in given period
     */
    public function testRefundFileNoDataAvailableRetryProcessing()
    {
        $this->testProcessRefundFileWithNoRefundData();

        $gatewayFile = $this->getLastEntity('gateway_file', true);

        $this->testData[__FUNCTION__]['request']['url'] = '/gateway/files/' . $gatewayFile['id'] . '/retry';

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testGatewayFileAcknowledge()
    {
        $this->testProcessRefundFile();

        $gatewayFile = $this->getLastEntity('gateway_file', true);

        $this->testData[__FUNCTION__]['request']['url'] = '/gateway/files/' . $gatewayFile['id'] . '/acknowledge';

        $this->ba->appAuth();

        $content = $this->startTest();

        $this->assertNotNull($content[File\Entity::ACKNOWLEDGED_AT]);
    }

    public function testAcknowledgedGatewayFileRetry()
    {
        $this->testGatewayFileAcknowledge();

        $gatewayFile = $this->getLastEntity('gateway_file', true);

        $this->testData[__FUNCTION__]['request']['url'] = '/gateway/files/' . $gatewayFile['id'] . '/retry';

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testGatewayFileAcknowledgePartiallyProcessed()
    {
        $this->testProcessRefundFile();

        $gatewayFile = $this->getLastEntity('gateway_file', true);

        $this->testData[__FUNCTION__]['request']['url'] = '/gateway/files/' . $gatewayFile['id'] . '/acknowledge';

        $this->ba->appAuth();

        $content = $this->startTest();

        $this->assertNotNull($content[File\Entity::ACKNOWLEDGED_AT]);
    }

    public function testGenerateGatewayFilesBulk()
    {
        // @todo Use correct timestamps in this test
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
