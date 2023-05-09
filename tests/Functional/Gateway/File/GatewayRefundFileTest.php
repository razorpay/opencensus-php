<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Queue;
use Excel;
use Mockery;
use Carbon\Carbon;

use RZP\Jobs\BeamJob;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File;
use RZP\Models\Admin\ConfigKey;
use RZP\Tests\Functional\TestCase;
use RZP\Jobs\GatewayFile as GatewayFileJob;
use RZP\Models\Admin\Service as AdminService;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;

class GatewayRefundFileTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/GatewayRefundFileTestData.php';

        parent::setUp();

        $this->app['rzp.mode'] = Mode::TEST;
        $this->nbPlusService = Mockery::mock('RZP\Services\Mock\NbPlus\Netbanking', [$this->app])->makePartial();
        $this->app->instance('nbplus.payments', $this->nbPlusService);
    }

    private function makeEmiPaymentOnCard($card, $emiDuration)
    {

        $payment = $this->getDefaultPaymentArray();
        $payment['amount'] = 500000;
        $payment['method'] = 'emi';
        $payment['emi_duration'] = $emiDuration;
        $payment['card']['number'] = $card;
        $payment['currency'] = 'INR';

        return $this->doAuthAndCapturePayment($payment);
    }

    public function testProcessRefundFileIciciEmi()
    {
        Mail::fake();

        Queue::fake();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->emiPlan = $this->fixtures->create('emi_plan:default_emi_plans');

        $this->mockCardVault();

        $this->fixtures->merchant->enableEmi();

        $this->fixtures->edit('iin', '411146', [
            'issuer' => 'ICIC'
        ]);

        $this->ba->publicAuth();

        $payment = $this->makeEmiPaymentOnCard('4111460212312338', 9);

        $this->refundPayment($payment['id']);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull(File\Entity::SENT_AT);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => FileStore\Type::ICICI_EMI_REFUND_FILE,
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'zip',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        $this->fixtures->merchant->disableEmi();

        Queue::assertPushed(BeamJob::class, 1);

        Queue::assertPushedOn('beam_test', BeamJob::class);

        Mail::assertQueued(RefundFileMail::class);
    }

    public function testProcessRefundFile()
    {
        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

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
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'hdfc_netbanking_refund',
            'entity_type' => 'gateway_file',
            'entity_id'   => $content['id'],
            'extension'   => 'xlsx',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertQueued(RefundFileMail::class);
    }

    public function testProcessRefundFileAsync()
    {
        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        // Netbanking Hdfc refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity]);

        Queue::fake();

        $this->ba->cronAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        Queue::assertPushed(GatewayFileJob::class);
    }

    public function testProcessGatewayFileWithInvalidType()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testProcessGatewayFileWithInvalidSource()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testProcessGatewayFileWithInvalidRecipients()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testProcessGatewayFileStartingInFuture()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testProcessGatewayFileWithInvalidTimeRange()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testProcessRefundFileWithCustomRecipients()
    {
        $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

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
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        Mail::assertQueued(RefundFileMail::class);
    }

    public function testProcessRefundFileWithNoRefundData()
    {
        Mail::fake();

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNull($content[File\Entity::SENT_AT]);
        $this->assertNull($content[File\Entity::FAILED_AT]);
        $this->assertNotNull($content[File\Entity::ACKNOWLEDGED_AT]);

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

        $refundEntity = $this->getDbLastEntity('refund');

        // Netbanking Hdfc refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

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

        $refundEntity = $this->getDbLastEntity('refund');

        // Netbanking Hdfc refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity]);

        $this->ba->adminAuth();

        $content = $this->startTest();

        $content = $content['items'][0];

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNull($content[File\Entity::SENT_AT]);
        $this->assertNotNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'hdfc_netbanking_refund',
            'entity_type' => 'gateway_file',
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
            'target'     => 'hdfc',
            'type'       => 'refund',
            'sender'     => 'refunds@razorpay.com',
            'status'     => 'failed',
            'error_code' => 'error_creating_file',
            'failed_at'  => time(),
            'attempts'   => 1,
            'begin'      => Carbon::today(Timezone::IST)->timestamp,
            'end'        => Carbon::tomorrow(Timezone::IST)->timestamp,
        ]);

        $payment = $this->getDefaultNetbankingPaymentArray('HDFC');

        $payment = $this->doAuthAndCapturePayment($payment);

        $refund = $this->refundPayment($payment['id']);

        $refundEntity = $this->getDbLastEntity('refund');

        // Netbanking Hdfc refunds have moved to scrooge
        $this->assertEquals(1, $refundEntity['is_scrooge']);

        $this->setFetchFileBasedRefundsFromScroogeMockResponse([$refundEntity]);

        $this->testData[__FUNCTION__]['request']['url'] = '/gateway/files/' . $gatewayFile->getId() . '/retry';

        $this->ba->adminAuth();

        $content = $this->startTest();

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNotNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        Mail::assertQueued(RefundFileMail::class);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'hdfc_netbanking_refund',
            'entity_type' => 'gateway_file',
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

        $this->ba->adminAuth();

        $content = $this->startTest();

        $this->assertNotNull($content[File\Entity::FILE_GENERATED_AT]);
        $this->assertNotNull($content[File\Entity::SENT_AT]);
        $this->assertNotNull($content[File\Entity::FAILED_AT]);
        $this->assertNull($content[File\Entity::ACKNOWLEDGED_AT]);

        Mail::assertQueued(RefundFileMail::class);

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'hdfc_netbanking_refund',
            'entity_type' => 'gateway_file',
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

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGatewayFileAcknowledge()
    {
        $this->testProcessRefundFile();

        $gatewayFile = $this->getLastEntity('gateway_file', true);

        $this->testData[__FUNCTION__]['request']['url'] = '/gateway/files/' . $gatewayFile['id'] . '/acknowledge';

        $this->ba->adminAuth();

        $content = $this->startTest();

        $this->assertNotNull($content[File\Entity::ACKNOWLEDGED_AT]);
    }

    public function testAcknowledgedGatewayFileRetry()
    {
        $this->testGatewayFileAcknowledge();

        $gatewayFile = $this->getLastEntity('gateway_file', true);

        $this->testData[__FUNCTION__]['request']['url'] = '/gateway/files/' . $gatewayFile['id'] . '/retry';

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGatewayFileAcknowledgePartiallyProcessed()
    {
        $this->testProcessRefundFile();

        $gatewayFile = $this->getLastEntity('gateway_file', true);

        $this->testData[__FUNCTION__]['request']['url'] = '/gateway/files/' . $gatewayFile['id'] . '/acknowledge';

        $this->ba->adminAuth();

        $content = $this->startTest();

        $this->assertNotNull($content[File\Entity::ACKNOWLEDGED_AT]);
    }

    public function testGenerateGatewayFilesBulkWithNoTargets()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }
}
