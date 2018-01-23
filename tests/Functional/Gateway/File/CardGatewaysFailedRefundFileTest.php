<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\FailedRefund\Base as FailedRefundMail;

class CardGatewaysFailedRefundFileTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/CardGatewaysFailedRefundFileTestData.php';

        parent::setUp();
    }

    public function testAxisMigsFailedRefundFile()
    {
        Mail::fake();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_axis_terminal');

        $this->fixtures->create('terminal:shared_migs_recurring_terminals');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->create('merchant:bank_account', ['merchant_id' => '10000000000000']);

        $this->createFailedRefundsforOldpayments();

        $this->ba->appAuth();

        $data = $this->startTest();

        $entity_id = $data['items']['0']['id'];

        $file = $this->getLastEntity('file_store', true);

         $expectedFileContent = [
            'type'        => 'axis_migs_failed_refund',
            'entity_type' => 'gateway_file',
            'entity_id'   => $entity_id,
            'extension'   => 'xlsx',
        ];
        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertSent(FailedRefundMail::class, function ($mail)
        {
            $this->assertNotEmpty($mail->attachments);

            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $subject = 'Axis Migs failed refunds for ' . $date;

            $body = 'Please process the attached refunds for Axis Migs';

            $fileName = 'Axis_Migs_Failed_Refunds_test_'. $date  . '.xlsx';

            $this->assertEquals($subject, $mail->subject);

            $this->assertEquals($body, $mail->viewData['body']);

            $this->assertEquals($fileName, $mail->viewData['file_name']);

            return true;
        });
    }

    public function testFirstDatadRefundFile()
    {
        Mail::fake();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_first_data_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->payment = $this->getDefaultPaymentArray();

        $authResponse = $this->doAuthPayment($this->payment);

        $this->createFailedRefundsforOldpayments();

        $this->ba->appAuth();

        $data = $this->startTest();

        $entity_id = $data['items']['0']['id'];

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'icic_first_data_failed_refund',
            'entity_type' => 'gateway_file',
            'entity_id'   => $entity_id,
            'extension'   => 'xlsx',
        ];
        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertSent(FailedRefundMail::class, function ($mail)
        {
            $this->assertNotEmpty($mail->attachments);

            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $subject = 'FirstData failed refunds for ' . $date;

            $body = 'Please process the attached refunds for ICICI FirstData';

            $fileName = 'Icic_FirstData_Failed_Refunds_test_'. $date  . '.xlsx';

            $this->assertEquals($subject, $mail->subject);

            $this->assertEquals($body, $mail->viewData['body']);

            $this->assertEquals($fileName, $mail->viewData['file_name']);

            return true;
        });
    }

    public function testHdfcCybersourcedRefundFile()
    {
        Mail::fake();

        $this->mockTokenex();

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->createFailedRefundsforOldpayments();

        $this->ba->appAuth();

        $data = $this->startTest();

        $entity_id = $data['items']['0']['id'];

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'hdfc_cybersource_failed_refund',
            'entity_type' => 'gateway_file',
            'entity_id'   => $entity_id,
            'extension'   => 'xlsx',
        ];
        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertSent(FailedRefundMail::class, function ($mail)
        {
            $this->assertNotEmpty($mail->attachments);

            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $subject = 'HDFC Cybersource failed refunds for ' . $date;

            $body = 'Please process the attached refunds for HDFC Cybersource';

            $fileName = 'Hdfc_Cybersource_Failed_Refunds_test_'. $date  . '.xlsx';

            $this->assertEquals($subject, $mail->subject);

            $this->assertEquals($body, $mail->viewData['body']);

            $this->assertEquals($fileName, $mail->viewData['file_name']);

            return true;
        });
    }

    public function testAxisCybersourcedRefundFile()
    {
        Mail::fake();

        $this->mockTokenex();

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->payment = $this->getDefaultPaymentArray();

        $authResponse = $this->doAuthPayment($this->payment);

        $this->createFailedRefundsforOldpayments();

        $this->ba->appAuth();

        $data = $this->startTest();

        $entity_id = $data['items']['0']['id'];

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'axis_cybersource_failed_refund',
            'entity_type' => 'gateway_file',
            'entity_id'   => $entity_id,
            'extension'   => 'xlsx',
        ];
        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertSent(FailedRefundMail::class, function ($mail)
        {
            $this->assertNotEmpty($mail->attachments);

            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $subject = 'Axis Cybersource failed refunds for ' . $date;

            $body = 'Please process the attached refunds for Axis Cybersource';

            $fileName = 'Axis_Cybersource_Failed_Refunds_test_'. $date  . '.xlsx';

            $this->assertEquals($subject, $mail->subject);

            $this->assertEquals($body, $mail->viewData['body']);

            $this->assertEquals($fileName, $mail->viewData['file_name']);

            return true;
        });
    }

    public function testfssFaileddRefundFile()
    {
        Mail::fake();

        $this->fixtures->create('terminal:shared_hdfc_recurring_terminals');

        $this->payment = $this->getDefaultPaymentArray();

        $authResponse = $this->doAuthPayment($this->payment);

        $this->createFailedRefundsforOldpayments();

        $this->ba->appAuth();

        $data = $this->startTest();

        $entity_id = $data['items']['0']['id'];

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'hdfc_fss_failed_refund',
            'entity_type' => 'gateway_file',
            'entity_id'   => $entity_id,
            'extension'   => 'xlsx',
        ];
        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertSent(FailedRefundMail::class, function ($mail)
        {
            $this->assertNotEmpty($mail->attachments);

            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $subject = 'HDFC FSS failed refunds for ' . $date;

            $body = 'Please process the attached refunds for HDFC FSS';

            $fileName = 'Hdfc_FSS_Failed_Refunds_test_'. $date  . '.xlsx';

            $this->assertEquals($subject, $mail->subject);

            $this->assertEquals($body, $mail->viewData['body']);

            $this->assertEquals($fileName, $mail->viewData['file_name']);

            return true;
        });
    }

    protected function createFailedRefundsforOldpayments()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment = $this->doAuthAndCapturePayment($payment);

        $this->refundPayment($payment['id'], 100);

        $this->refundPayment($payment['id'], 100);

        $this->refundPayment($payment['id'], 100);

        $refunds = $this->getEntities('refund', [], true);

        foreach ($refunds['items'] as $refund)
        {
            $this->fixtures->edit('refund', $refund['id'], ['status' => 'failed']);

            // Only those refunds that cannot be processed via API needs to appear
            $six_months_ago = $refund['created_at'] - 15552000;

            $this->fixtures->edit('payment', $payment['id'], ['created_at' => $six_months_ago]);
        }
    }
}
