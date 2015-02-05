<?php

namespace Tests\Functional\Payment;

use Tests\Functional\TestCase;
use Tests\Functional\Payment\PaymentAuthFlowTrait;

class SignatureTest extends TestCase
{
    use PaymentAuthFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/SignatureTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();
    }

    public function testValidSignature()
    {
        $payment = &$this->payment;

        $payment['signature'] = $this->signPayment($payment, 'TheKeySecretForTests');

        $testData = $this->testData[__FUNCTION__];

        $this->replaceValuesRecursively($this->payment, $testData['request']['content']);

        $testData['request']['content'] = $this->payment;

        $content = $this->startTest();

        $this->assertArrayHasKey('razorpay_payment_id', $content);

        $this->assertSignatureMatches($content, 'TheKeySecretForTests');

        return $content;
    }

    public function testPaymentStatusAfterSignedRequest()
    {
        $content = $this->testValidSignature();

        $id = $content['razorpay_payment_id'];

        $this->testData[__FUNCTION__]['request']['url'] = '/payments/'.$id.'/capture';

        $this->startTest();
    }

    public function startTest()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func];

        return $this->runRequestResponseFlow($testData);
    }
}
