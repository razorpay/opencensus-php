<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Mail\Gateway\FailedRefund\Base as FailedRefundMail;

class CardGatewaysFailedRefundFileTest extends TestCase
{
    use PaymentTrait;
    use FileHandlerTrait;
    use DbEntityFetchTrait;

    protected $payment;

    protected function setUp(): void
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

        $this->ba->adminAuth();

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

        Mail::assertQueued(FailedRefundMail::class, function ($mail)
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

        $this->doAuthPayment($this->payment);

        $this->createFailedRefundsforOldpayments();

        $payment = $this->createFailedRefundsForOldPayment();

        $gatewayEntities = $this->getDbEntities('first_data', [
            'payment_id' => $payment->getId(),
        ]);

        // Deleting gateway entities to test missing gateway entity check
        $gatewayEntities->each(
            function($entity)
            {
                $entity->delete();
            });

        $this->ba->adminAuth();

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

        Mail::assertQueued(FailedRefundMail::class, function ($mail)
        {
            $this->assertNotEmpty($mail->attachments);

            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $subject = 'FirstData failed refunds for ' . $date;

            $body = 'Please process the attached refunds for ICICI FirstData';

            $fileName = 'Icic_FirstData_Failed_Refunds_test_'. $date  . '.xlsx';

            $this->assertEquals($subject, $mail->subject);

            $attachment = $mail->attachments[0]['file'];

            $sheets = $this->parseExcelFile($attachment);

            $fileContentCount = count($sheets[0]);

            $this->assertEquals($fileContentCount, 3);

            $this->assertEquals($body, $mail->viewData['body']);

            $this->assertEquals($body, $mail->viewData['body']);

            $this->assertEquals($fileName, $mail->viewData['file_name']);

            return true;
        });
    }

    public function testHdfcCybersourcedRefundFile()
    {
        Mail::fake();

        $this->mockCardVault();

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->createFailedRefundsforOldpayments();

        $this->ba->adminAuth();

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

        Mail::assertQueued(FailedRefundMail::class, function ($mail)
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

        $this->mockCardVault();

        $this->fixtures->create('terminal:shared_cybersource_axis_terminal');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->payment = $this->getDefaultPaymentArray();

        $authResponse = $this->doAuthPayment($this->payment);

        $this->createFailedRefundsforOldpayments();

        $this->ba->adminAuth();

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

        Mail::assertQueued(FailedRefundMail::class, function ($mail)
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

        $this->ba->adminAuth();

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

        Mail::assertQueued(FailedRefundMail::class, function ($mail)
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

    /**
     * Creates one older payment with 3 failed refunds,
     * And create one current payment with one failed
     */
    protected function createFailedRefundsforOldpayments()
    {
        $this->createFailedRefundsForOldPayment(3);

        $this->createFailedRefundsForOldPayment(1, 0);
    }

    /**
     * Creates a payment and makes $refundCount refund on that
     * Sets the payment.created_at to $paymentCreatedBefore
     *
     * @param int $refundCount
     * @param int $paymentCreatedBefore
     * @return \RZP\Models\Payment\Entity
     */
    protected function createFailedRefundsForOldPayment(
        int $refundCount = 1,
        int $paymentCreatedBefore = 15552000)
    {
        // Create payment for default amount 500, By using refund count,
        // we can decide for partial refunds, as each refund will of 100
        $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getDbLastPayment();

        while($refundCount-- > 0)
        {
            $this->refundPayment($payment->getPublicId(), 100);

            $refund = $this->getDbLastRefund();

            $refund->setStatus('failed');

            $refund->saveOrFail();
        }

        $payment->setCreatedAt($payment->getCreatedAt() - $paymentCreatedBefore)
                ->save();

        return $payment;
    }
}
