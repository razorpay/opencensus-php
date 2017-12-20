<?php

namespace RZP\Tests\Functional\Gateway\File;

use Mail;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiFailedRefundFileTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/UpiFailedRefundFileTestData.php';

        parent::setUp();
    }

    public function testUpiFailedRefundFile()
    {
        Mail::fake();

        $this->fixtures->create('terminal:shared_upi_icici_terminal');

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

        $this->ba->appAuth();

        $data = $this->startTest();

        $entity_id = $data['items']['0']['id'];

        $file = $this->getLastEntity('file_store', true);

         $expectedFileContent = [
            'type'        => 'icici_upi_refund',
            'entity_type' => 'gateway_file',
            'entity_id'   => $entity_id,
            'extension'   => 'txt',
        ];
        $this->assertArraySelectiveEquals($expectedFileContent, $file);
        Mail::assertSent(RefundFileMail::class);
    }

    public function testNoFailedRefunds()
    {
        Mail::fake();

        $this->ba->appAuth();

        $this->startTest();
    }

    protected function createAndCaptureUpiPayment()
    {
        $payment = $this->getDefaultUpiPaymentArray();

        $response = $this->doAuthPayment($payment);

        $paymentId = $response['payment_id'];

        $this->gateway = 'upi_icici';

        $upiEntity = $this->getLastEntity('upi', true);

        $payment = $this->getEntityById('payment', $paymentId, true);

        $content = $this->mockServer()->getAsyncCallbackContent($upiEntity, $payment);

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->capturePayment($payment['id'], 50000);

        return $paymentId;
    }
}
