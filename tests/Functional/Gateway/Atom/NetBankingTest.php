<?php

namespace RZP\Tests\Functional\Gateway\Atom;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class NetbankingTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/netbanking.php';

        parent::setUp();

        $this->fixtures->create('terminal:atom_terminal');

        $this->payment = array(
            'method' => 'netbanking',
            'bank' => 'ICIC',
            'amount' => '5000',
            'email' => 'ab@g.com',
            'contact' => '9431495816',
            'currency' => 'INR');

        $this->gateway = 'atom';

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_atom_terminal');
    }

    public function testNetbankingPaymentAuthorize()
    {
        $this->ba->publicAuth();

        $content = $this->startTest();

        $this->assertArrayHasKey('razorpay_payment_id', $content);
    }

    public function testNetbankingPaymentCapture()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->assertTestResponse($payment);
    }

    public function testNetbankingPaymentRefund()
    {
        $payment = $this->fixtures->create('payment:netbanking_captured', ['gateway' => 'atom']);

        $refund = $this->refundPayment($payment->getPublicId());

        $this->assertTestResponse($refund);
    }

    public function testMockOnLiveMode()
    {
        $this->setMockGatewayTrue();

        $this->ba->publicLiveAuth();

        $this->fixtures
            ->on('live')
            ->create('terminal:atom_terminal');

        $this->startTest();
    }

    public function testAtomVerifyPayment()
    {
        //
        // Just after a payment, on verification atom sends false response
        // irrespective of the result.
        // Their doc states that we can only verify after 15 mins, which is kinda weird.
        // So, for testing purposes, we need to keep the mock as true.
        //
        $this->setMockGatewayTrue();

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $id = $payment['id'];

        $data = $this->verifyPayment($id);

        $this->assertEquals($data['payment']['verified'], 1);
    }

    public function startTest($testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func];

        $this->replaceValuesRecursively($this->payment, $testData['request']['content']);

        $testData['request']['content'] = $this->payment;

        $this->currentTestData = $testData;

        return $this->runRequestResponseFlow($testData);
    }
}
