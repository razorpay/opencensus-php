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

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/AuthorizeTestData.php';

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
                    // 'http_status_code' => 400,
                ],
                'status_code' => 400,
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

    public function testCancelPaymentWithReason()
    {
        $data = $this->testData[__FUNCTION__];

        $payment = $this->fixtures->create(
            'payment',
            ['created_at' => time() - 10 * 60, 'status' => 'created', 'terminal_id' => '1n25f6uN5S1Z5a']);

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->cancelPayment($payment->getPublicId(), ['_' => ['reason' => 'failed_in_app']]);
        });

        $payment = $this->getEntityById('payment', $payment->getId(), true);

        $this->assertEquals('failed_in_app', $payment['cancellation_reason']);
    }

    public function testCancelPaymentWithArrayReason()
    {
        $data = $this->testData[__FUNCTION__];

        $payment = $this->fixtures->create(
            'payment',
            ['created_at' => time() - 10 * 60, 'status' => 'created', 'terminal_id' => '1n25f6uN5S1Z5a']);

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->cancelPayment($payment->getPublicId(), ['_' => ['reason' => ['failed_in_app']]]);
        });

        $payment = $this->getEntityById('payment', $payment->getId(), true);

        $this->assertEquals(null, $payment['cancellation_reason']);
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

    public function testCancelPaymentWithUnselectedMethod()
    {
        $order = $this->createOrder(['payment_capture' => '1']);

        $this->fixtures->merchant->addFeatures(['gpay']);

        $this->fixtures->create(
            'terminal',
            [
                'merchant_id' => '10000000000000',
                'gateway'     => 'cybersource',
            ]
        );

        $payment = $this->getDefaultPaymentArray();
        $payment['order_id'] = $order['id'];
        $payment['provider'] = 'google_pay';
        unset($payment['method']);

        $content = $this->doAuthPayment($payment);

        $data = $this->testData['testCancelPaymentWithReason'];

        $this->runRequestResponseFlow($data, function() use ($content)
        {
            $this->cancelPayment($content['payment_id']);
        });
    }
}
