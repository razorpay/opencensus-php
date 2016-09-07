<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Error\ErrorCode;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PaymentCancelTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/authorize.php';

        parent::setUp();

        $this->ba->publicAuth();
    }

    public function testCancelPayment()
    {
        $data = [
            'response' => [
                'content' => [
                    'error' => [
                        'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    ],
                    'http_status_code' => 400,
                ],
                'status_code' => 200,
            ],
            'exception' => [
                'class' => 'RZP\Exception\BadRequestException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER
            ],
        ];

        $payment = $this->fixtures->create(
            'payment',
            ['created_at' => time() - 10 * 60, 'status' => 'created', 'terminal_id' => '1n25f6uN5S1Z5a']);

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->cancelPayment($payment->getPublicId());
        });
    }

    public function testCancelPaymentAfterRecentlyProcessed()
    {
        $content = $this->doAuthPayment();
        $pid = $content['razorpay_payment_id'];

        $content2 = $this->cancelPayment($pid);
        unset($content2['http_status_code']);

        $this->assertEquals($content, $content2);
    }

    public function testCancelPaymentAfterAutoCaptureAndRecentlyProcessed()
    {
        $order = $this->createOrder(['payment_capture' => '1']);

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $content = $this->doAuthPayment($payment);

        $pid = $content['razorpay_payment_id'];

        $content2 = $this->cancelPayment($pid);
        unset($content2['http_status_code']);
        $this->assertEquals($content, $content2);
    }
}