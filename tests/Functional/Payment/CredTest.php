<?php

use RZP\Models\Payment;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Exception\BadRequestException;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class CredTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        parent::setUp();

        $this->gateway = 'cred';

        $this->sharedTerminal = $this->fixtures->create('terminal:direct_cred_terminal');

        $this->fixtures->merchant->enableApp('10000000000000', 'cred');
    }

    public function testCredPaymentCreateResponseIntentFlow()
    {
        $payment = $this->getDefaultCredPayment();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments',
            'content' => $payment
        ];
        $this->ba->publicAuth();

        $response = $this->makeRequestAndGetContent($request);

        $payment = $this->getLastPayment('payment', 'true');

        $this->assertEquals('intent', $response['type']);

        $this->assertEquals('cred://pay?am=100000&cu=INRPAISE&mc=5411', $response['data']['intent_url']);

        $this->assertEquals('cred', $payment['gateway']);

        $this->assertEquals('created', $payment['status']);

        $this->assertEquals('100DiCreDTrmnl', $payment['terminal_id']);

        $payment = $this->getLastPayment('payment', true);

        $content = $this->getMockServer()->getAsyncCallbackContentCred($payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'cred');

        $payment = $this->getLastPayment('payment', 'true');

        $this->assertEquals('cred', $payment['gateway']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertTrue($payment['gateway_captured']);

        $this->capturePayment($payment['id'], $payment['amount']);

        $payment = $this->getLastPayment('payment', 'true');

        $this->assertEquals('captured', $payment['status']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(17700, $transaction['fee']);

        $this->assertEquals(82300, $transaction['credit']);
    }



    public function testCredPaymentCreateResponseCollectFlowWithOrder()
    {
        $order = $this->createOrder(['app_offer' => true, 'amount' =>100000]);



        $payment = $this->getDefaultCredPayment();
        $payment['order_id'] = $order['id'];
        unset($payment['app_present']);


        $request = [
            'method'  => 'POST',
            'url'     => '/payments',
            'content' => $payment
        ];

        $this->ba->publicAuth();

        $response = $this->makeRequestAndGetContent($request);

        $payment = $this->getLastPayment('payment', 'true');

        $this->assertEquals('async', $response['type']);

        $this->assertEquals('cred_merchant', $response['data']['vpa']);

         $this->assertEquals('cred', $payment['gateway']);

        $this->assertEquals('created', $payment['status']);

        $this->assertEquals('100DiCreDTrmnl', $payment['terminal_id']);

        $payment = $this->getLastPayment('payment', true);

        $content = $this->getMockServer()->getAsyncCallbackContentCred($payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'cred');

        $payment = $this->getLastPayment('payment', 'true');

        $this->assertEquals('cred', $payment['gateway']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertTrue($payment['gateway_captured']);

        $this->capturePayment($payment['id'], $payment['amount']);

        $payment = $this->getLastPayment('payment', 'true');

        $this->assertEquals('captured', $payment['status']);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(17700, $transaction['fee']);

        $this->assertEquals(82300, $transaction['credit']);
    }
}
