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

        $content = $this->startTest();

        $this->assertArrayHasKey('razorpay_payment_id', $content);


    }

    public function startTest()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func];

        $this->replaceValuesRecursively($this->payment, $testData['request']['content']);

        $testData['request']['content'] = $this->payment;

        return $this->runRequestResponseFlow($testData);
    }
}
