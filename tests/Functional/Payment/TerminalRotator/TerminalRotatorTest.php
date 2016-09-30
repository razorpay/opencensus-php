<?php

namespace RZP\Tests\Functional\Payment\TerminalRotator;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\GatewayTimeoutException;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Payment\Analytics\Entity as AnalyticsEntity;

class TerminalRotatorTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        //$this->testDataFilePath = __DIR__.'/AnalyticsTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();
    }

    public function testTerminalRotator()
    {
        // fail the payment with a card that throws timeout and
        // succeed wih another terminal and assert so.

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hdfc_terminal');

        $payment = $this->getPaymentArray();

        $this->doAuthPayment($payment);

        $payment = $this->getLastPayment(true);

        $this->assertEquals($payment['gateway'], 'cybersource');
    }

    public function testCheckoutMultipleAttempts()
    {
        // first fail the payment and on next attempt with
        // only a checkout id, ensure the payment goes through
        // the other terminal
        $this->fixtures->create('terminal:shared_hdfc_terminal');

        $payment1 = $this->getPaymentArray();

        $checkoutId = UniqueIdEntity::generateUniqueIdWithCheckDigit();

        $payment1['_'][AnalyticsEntity::CHECKOUT_ID] = $checkoutId;

        try
        {
            $this->doAuthPayment($payment1);
        }
        catch(\Exception $e)
        {
            $this->assertExceptionClass($e, GatewayTimeoutException::CLASS);
        }

        $payment1  = $this->getLastPayment(true);

        $this->stripSign($payment1['id']);

        $analytics = $this->getEntities('terminal_analytics', array('payment_id' => $payment1['id']), true);

        $terminalsUsed = $this->fetchUsedTerminals($analytics);

        $payment2 = $this->getPaymentArray();

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $payment2['_'][AnalyticsEntity::CHECKOUT_ID] = $checkoutId;

        $this->doAuthPayment($payment2);

        $payment = $this->getLastPayment(true);

        $this->stripSign($payment['id']);

        $analytics = $this->getEntities('terminal_analytics', array('payment_id' => $payment['id']), true);

        $newTerminalsUsed = $this->fetchUsedTerminals($analytics);

        $intersection = array_intersect($terminalsUsed, $newTerminalsUsed);

        $this->assertEquals(count($intersection), 0);
    }

    public function testOrderMultipleAttempts()
    {
        // first fail the payment and on next attempt with
        // only a order id, ensure the payment goes through
        // the other terminal
        $order = $this->createOrder();

        $this->fixtures->create('terminal:shared_hdfc_terminal');

        $payment1 = $this->getPaymentArray();

        $payment1['order_id'] = $order['id'];

        $this->ba->publicAuth();

        try
        {
            $this->doAuthPayment($payment1);
        }
        catch(\Exception $e)
        {
            $this->assertExceptionClass($e, GatewayTimeoutException::CLASS);
        }

        $payment1  = $this->getLastPayment(true);

        $this->stripSign($payment1['id']);

        $analytics = $this->getEntities('terminal_analytics', array('payment_id' => $payment1['id']), true);

        $terminalsUsed = $this->fetchUsedTerminals($analytics);

        $payment2 = $this->getPaymentArray();

        $payment2['order_id'] = $order['id'];

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->doAuthPayment($payment2);

        $payment = $this->getLastPayment(true);

        $this->stripSign($payment['id']);

        $analytics = $this->getEntities('terminal_analytics', array('payment_id' => $payment['id']), true);

        $newTerminalsUsed = $this->fetchUsedTerminals($analytics);

        $intersection = array_intersect($terminalsUsed, $newTerminalsUsed);

        $this->assertEquals(count($intersection), 0);

        $this->assertEquals($payment['order_id'], $order['id']);

    }

    public function testMultipleAttemptsFail()
    {
        // payment simply fails here since neither checkout id not order
        // id is provided here.
        $this->fixtures->create('terminal:shared_hdfc_terminal');

        $payment1 = $this->getPaymentArray();

        try
        {
            $this->doAuthPayment($payment1);
        }
        catch(\Exception $e)
        {
            $this->assertExceptionClass($e, GatewayTimeoutException::CLASS);
        }

        $this->stripSign($payment1['id']);

        $analytics = $this->getEntities('terminal_analytics', array('payment_id' => $payment1['id']), true);

        $terminalsUsed = $this->fetchUsedTerminals($analytics);

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $payment2 = $this->getPaymentArray();

        try
        {
            $this->doAuthPayment($payment2);
        }
        catch(\Exception $e)
        {
            $this->assertExceptionClass($e, GatewayTimeoutException::CLASS);
        }

        $payment = $this->getLastPayment(true);

        $this->stripSign($payment['id']);

        $analytics = $this->getEntities('terminal_analytics', array('payment_id' => $payment['id']), true);

        $newTerminalsUsed = $this->fetchUsedTerminals($analytics);

        $intersection = array_intersect($terminalsUsed, $newTerminalsUsed);

        $this->assertEquals(count($intersection), count($terminalsUsed));
    }

    public function testMultipleFailAttemptsWithSameTerminals()
    {
        // first fail the payment and on next attempt with
        // only a order id, enusre the same terminals are picked up
        // not excluded and the payment fails again with the
        // same exception that it failed before.
        $order = $this->createOrder();

        $this->fixtures->create('terminal:shared_hdfc_terminal');

        $payment1 = $this->getPaymentArray();

        $payment1['order_id'] = $order['id'];

        $this->ba->publicAuth();

        try
        {
            $this->doAuthPayment($payment1);
        }
        catch(\Exception $e)
        {
            $this->assertExceptionClass($e, GatewayTimeoutException::CLASS);
        }

        $payment2 = $this->getPaymentArray();

        $payment2['order_id'] = $order['id'];

        try
        {
            $this->doAuthPayment($payment2);
        }
        catch(\Exception $e)
        {
            $this->assertExceptionClass($e, GatewayTimeoutException::CLASS);
        }

    }



    //-- helpers----

    protected function getPaymentArray()
    {

        $defaultPayment = $this->getDefaultPaymentArray();

        $defaultPayment['card']['number'] = '4012001036275556';

        $payment = array();

        $payment = array_merge($defaultPayment, $payment);


        return $payment;
    }

    protected function fetchUsedTerminals($analytics)
    {
        $terminalsUsed = array();

        foreach($analytics['items'] as $data)
        {
            $terminalsUsed[] = $data['terminal_id'];
        }

        return $terminalsUsed;
    }

    protected function stripSign(& $id)
    {
        $ix = strpos($id, '_');
        if ($ix !== false)
        {
            $id = substr($id, $ix + 1);
        }
    }

    protected function createOrder()
    {
        $request = array(
            'content' => array(
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
            ),
            'method' => 'POST',
            'url' => '/orders'
        );

        $this->ba->privateAuth();

        $order = $this->makeRequestAndGetContent($request);

        return $order;
    }


}