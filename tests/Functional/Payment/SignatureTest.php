<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class SignatureTest extends TestCase
{
    use PaymentTrait;

    protected $payment;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/SignatureTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();
        $this->payment['notes']['merchant_order_id'] = 'Grü-1234';
    }

    public function testValidSignature()
    {
        $payment = $this->payment;
        $payment['card']['number'] = '4012001037167778';

        $payment['signature'] = $this->signPayment($payment, 'TheKeySecretForTests');

        $testData = &$this->testData[__FUNCTION__];

        $this->replaceValuesRecursively($payment, $testData['request']['content']);

        $testData['request']['content'] = $payment;

        $content = $this->startTest();

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $this->assertSignatureMatches($content, 'TheKeySecretForTests');

        return $content;
    }

    public function testInvalidMerchantOrderId()
    {
        $payment = $this->payment;
        $payment['card']['number'] = '4012001037167778';

        $payment['signature'] = $this->signPayment($payment, 'TheKeySecretForTests');

        $testData = &$this->testData[__FUNCTION__];

        $payment['notes'] = [];

        $this->replaceValuesRecursively($payment, $testData['request']['content']);

        $testData['request']['content'] = $payment;

        $content = $this->startTest();
    }

    public function testPaymentStatusAfterSignedRequestWith3dSecure()
    {
        $content = $this->testValidSignature();

        $id = $content['razorpay_payment_id'];

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/'.$id;

        $this->startTest();
    }

    public function testPaymentStatusAfterSignedRequestWithout3dSecure()
    {
        $payment = &$this->payment;
        $payment['card']['number'] = '4111111111111111';

        $payment['signature'] = $this->signPayment($payment, 'TheKeySecretForTests');

        $content = $this->doAuthPayment($payment);

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $this->assertSignatureMatches($content, 'TheKeySecretForTests');
    }

    public function testCaptureFailAfterSignedRequest()
    {
        $content = $this->testValidSignature();

        $id = $content['razorpay_payment_id'];

        $this->ba->privateAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/'.$id.'/capture';

        $this->startTest();
    }

    public function startTest($testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func];

        return $this->runRequestResponseFlow($testData);
    }
}
