<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\FailedRefund\Base as FailedRefundMail;

class UpiMindgateFailedRefundFileTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/UpiMindgateFailedRefundFileTestData.php';

        parent::setUp();
    }

    public function testUpiFailedRefundFile()
    {
        Mail::fake();

        $this->fixtures->create('terminal:shared_upi_mindgate_terminal');

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $paymentId1 = $this->createAndCaptureUpiPayment();

        $paymentId2 =  $this->createAndCaptureUpiPayment();

        $this->refundPayment($paymentId1, 10000);
        $this->refundPayment($paymentId2, 10000);
        $this->refundPayment($paymentId2);

        $refunds = $this->getEntities('refund', [], true);

        foreach ($refunds['items'] as $refund)
        {
            $this->fixtures->edit('refund', $refund['id'], ['status' => 'failed']);
        }

        $this->ba->adminAuth();

        $data = $this->startTest();

        $entity_id = $data['items']['0']['id'];

        $file = $this->getLastEntity('file_store', true);

         $expectedFileContent = [
            'type'        => 'mindgate_upi_refund',
            'entity_type' => 'gateway_file',
            'entity_id'   => $entity_id,
            'extension'   => 'csv',
        ];

        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertQueued(FailedRefundMail::class, function ($mail)
        {
            $this->assertNotEmpty($mail->attachments);

            $date = Carbon::today(Timezone::IST)->format('d-m-Y');

            $body = 'Please find attached failed refunds information for UPI Mindgate';

            $fileName = 'Mindgate_Upi_Failed_Refunds_test_'. $date  . '.csv';

            $subject = 'UPI Mindgate failed refunds file for ' . $date;

            $this->assertEquals($subject, $mail->subject);

            $this->assertEquals($body, $mail->viewData['body']);

            $this->assertEquals($fileName, $mail->viewData['file_name']);

            return true;
        });
    }

    public function testNoFailedRefunds()
    {
        Mail::fake();

        $this->ba->adminAuth();

        $this->startTest();
    }

    protected function createAndCaptureUpiPayment()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['payment_id'];

        $this->gateway = 'upi_mindgate';

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->capturePayment($payment['id'], 50000);

        return $paymentId;
    }
}
