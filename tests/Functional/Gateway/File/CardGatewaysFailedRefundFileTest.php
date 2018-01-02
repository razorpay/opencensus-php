<?php

namespace RZP\Tests\Functional\Gateway\File;

use Carbon\Carbon;
use Mail;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;

class CardGatewaysFailedRefundFileTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        Carbon::setTestNow();

        $this->testDataFilePath = __DIR__ . '/helpers/CardGatewaysFailedRefundFileTestData.php';

        parent::setUp();

        $this->payment = $this->getDefaultPaymentArray();

        $this->payment['card']['number'] = '341111111111111';

        $this->payment['card']['cvv'] = '8888';

    }


    public function testAmexFailedRefundFile()
    {
        Mail::fake();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_amex_terminal');

        $this->fixtures->merchant->enableMethod('10000000000000', 'amex');

        $this->fixtures->create('merchant:bank_account', ['merchant_id' => '10000000000000']);

        $this->doAuthPayment($this->payment);

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->refundPayment($payment['id'], 100);

        $this->refundPayment($payment['id'], 100);

        $this->refundPayment($payment['id'], 100);

        $refunds = $this->getEntities('refund', [], true);

        $time = Carbon::now()->getTimestamp() - 15780000;

        foreach ($refunds['items'] as $refund)
        {
            $this->fixtures->edit('refund', $refund['id'], ['status' => 'failed', 'created_at' => $time]);
        }

        $this->ba->appAuth();

        $data = $this->startTest();

        $entity_id = $data['items']['0']['id'];

        $file = $this->getLastEntity('file_store', true);

         $expectedFileContent = [
            'type'        => 'amex_refund',
            'entity_type' => 'gateway_file',
            'entity_id'   => $entity_id,
            'extension'   => 'xlsx',
        ];
        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertSent(RefundFileMail::class);
    }

    public function testFirstDatadRefundFile()
    {
        Mail::fake();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_first_data_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->payment = $this->getDefaultPaymentArray();

        $authResponse = $this->doAuthPayment($this->payment);

        $payment = $this->capturePayment($authResponse['razorpay_payment_id'], $this->payment['amount']);

        $this->refundPayment($payment['id'], 100);

        $this->refundPayment($payment['id'], 100);

        $this->refundPayment($payment['id'], 100);

        $refunds = $this->getEntities('refund', [], true);

        $time = Carbon::now()->getTimestamp() - 15780000;

        foreach ($refunds['items'] as $refund)
        {
            $this->fixtures->edit('refund', $refund['id'], ['status' => 'failed', 'created_at' => $time]);
        }

        $this->ba->appAuth();

        $data = $this->startTest();

        $entity_id = $data['items']['0']['id'];

        $file = $this->getLastEntity('file_store', true);

        $expectedFileContent = [
            'type'        => 'first_data_refund',
            'entity_type' => 'gateway_file',
            'entity_id'   => $entity_id,
            'extension'   => 'xlsx',
        ];
        $this->assertArraySelectiveEquals($expectedFileContent, $file);

        Mail::assertSent(RefundFileMail::class);
    }

}
