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

    public function testCredGetPayment()
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

        $request = [
            'method'  => 'GET',
            'url'     => '/payments/' . $payment['id'],
        ];

        $this->ba->privateAuth();
        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals('cred', $response['provider']);
        $this->assertEquals(false, array_key_exists('wallet', $response));
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

    public function testCredPaymentWithCoinDiscount()
    {
        $order = $this->createOrder(['app_offer' => true, 'amount' =>200000]);

        $payment = $this->getDefaultCredPayment();
        $payment['amount'] = 200000;
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

        $payment = $this->getLastPayment('payment', true);

        $content = $this->getMockServer()->getAsyncCallbackContentCred($payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'cred');

        $payment = $this->getLastPayment('payment', 'true');

        $this->capturePayment($payment['id'], $payment['amount']);

        $payment = $this->getLastPayment('payment', 'true');

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(35400, $transaction['fee']);

        // coins = 20% of p1 amount
        // 200000 - 40000 - 35400 = netamount/credit
        $this->assertEquals(124600, $transaction['credit']);
    }

    public function testCredPaymentWithCoinDiscountWithoutOrder()
    {
        $payment = $this->getDefaultCredPayment();
        $payment['amount'] = 200000;
        unset($payment['app_present']);

        $request = [
            'method'  => 'POST',
            'url'     => '/payments',
            'content' => $payment
        ];

        $this->ba->publicAuth();

        $response = $this->makeRequestAndGetContent($request);

        $payment = $this->getLastPayment('payment', 'true');

        $payment = $this->getLastPayment('payment', true);

        $content = $this->getMockServer()->getAsyncCallbackContentCred($payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'cred');

        $payment = $this->getLastPayment('payment', 'true');

        $this->capturePayment($payment['id'], $payment['amount']);

        $payment = $this->getLastPayment('payment', 'true');

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertEquals(35400, $transaction['fee']);

        // coins = 20% of p1 amount
        // 200000 - 40000 - 35400 = netamount/credit
        $this->assertEquals(124600, $transaction['credit']);
    }

    public function testCredGetPaymentAcquirerData()
    {
        $payment = $this->getDefaultCredPayment();
        $payment['amount'] = 200000;
        unset($payment['app_present']);

        $request = [
            'method'  => 'POST',
            'url'     => '/payments',
            'content' => $payment
        ];

        $this->ba->publicAuth();

        $response = $this->makeRequestAndGetContent($request);

        $payment = $this->getLastPayment('payment', 'true');

        $payment = $this->getLastPayment('payment', true);

        $content = $this->getMockServer()->getAsyncCallbackContentCred($payment);

        $response = $this->makeS2SCallbackAndGetContent($content, 'cred');

        $payment = $this->getLastPayment('payment', 'true');

        $this->capturePayment($payment['id'], $payment['amount']);

        $payment = $this->getLastPayment('payment', 'true');

        $request = [
            'method'  => 'GET',
            'url'     => '/payments/' . $payment['id'],
        ];

        $this->ba->privateAuth();
        $response = $this->makeRequestAndGetContent($request);
        $this->assertEquals(400, $response['acquirer_data']['discount']);
        $this->assertEquals(1600, $response['acquirer_data']['amount']);

    }
}
