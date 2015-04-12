<?php

namespace Tests\Functional\AtomGateway;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class NetBankingTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/netbanking.php';

        parent::setUp();

        $this->fixtures->create('terminal:atom_terminal');

        $this->payment = array(
            'method' => 'netbanking',
            'bank' => 'SBIN',
            'amount' => '5000',
            'email' => 'ab@g.com',
            'contact' => '9431495816',
            'currency' => 'INR');

        $this->gateway = 'atom';
    }

    public function testNetBankingPaymentAuthorize()
    {
        $this->ba->publicAuth();

        $content = $this->startTest();

        $this->assertArrayHasKey('razorpay_payment_id', $content);
    }

    public function testNetBankingPaymentCapture()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $this->assertTestResponse($payment);
    }

    public function testNetBankingPaymentRefund()
    {
        $payment = $this->fixtures->create('payment:netbanking_captured');

        $refund = $this->refundPayment($payment->getPublicId());

        $this->assertTestResponse($refund);
    }

    public function testNBPaymentFailureAtBank()
    {
        $this->markTestIncomplete();
        $this->ba->publicAuth();

        $content = $this->startTest();

        $payment = $this->getLastEntity('payment', true);
        $this->assertTestResponse($payment, 'testNBPaymentFailureAtBankEntity');

        $this->assertEquals('atom', $payment['gateway']);
    }

    public function testAtomCardPayment()
    {
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $payment = $this->doAuthAndCapturePayment();

        $this->assertTestResponse($payment);
    }

    public function testMockOnLiveMode()
    {
        $this->app['config']->set('gateway.mock_atom', true);

        $this->ba->publicLiveAuth();

        $this->fixtures
            ->on('live')
            ->create('terminal:atom_terminal');

        $this->startTest();
    }

    public function testAtomVerifyPayment()
    {
        $this->markTestIncomplete();

        //
        // Just after a payment, on verification atom sends false response
        // irrespective of the result.
        // Their doc states that we can only verify after 15 mins, whic is kinda weird.
        // So, for testing purposes, we need to keep the mock as true.
        //
        $this->app['config']->set('gateway.mock_atom', true);

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $id = $payment['id'];

        $payment = $this->verifyPayment($id);

        $this->assertEquals($payment['verified'], true);
    }

    public function testAtomVerifyFailedPayment()
    {
        $this->markTestIncomplete();

        //
        // Just after a payment, on verification atom sends false response
        // irrespective of the result.
        // Their doc states that we can only verify after 15 mins, whic is kinda weird.
        // So, for testing purposes, we need to keep the mock as true.
        //
        $this->app['config']->set('gateway.mock_atom', true);

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $id = $payment['id'];

        $payment = $this->verifyPayment($id);

        $this->assertEquals($payment['verified'], true);
    }

    public function startTest()
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