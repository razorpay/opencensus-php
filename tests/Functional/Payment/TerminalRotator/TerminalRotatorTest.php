<?php

namespace RZP\Tests\Functional\Payment\TerminalRotator;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\GatewayTimeoutException;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Payment\Analytics\Entity as AnalyticsEntity;
use RZP\Models\Payment;
use RZP\Models\Merchant\Account;

class TerminalRotatorTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/TerminalRotatorTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();

        $this->sharedMerchantAccount = Account::SHARED_ACCOUNT;
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
        $this->fixtures->times(5)->create('terminal:dynamic_shared_hdfc_terminal');

        $this->fixtures->times(5)->create('terminal:dynamic_shared_cybersource_hdfc_terminal');

        // first fail the payment and on next attempt with
        // only a checkout id, ensure the payment goes through
        // the other terminal

        $payment1 = $this->getPaymentArray();

        $checkoutId = UniqueIdEntity::generateUniqueIdWithCheckDigit();

        $payment1['_'][AnalyticsEntity::CHECKOUT_ID] = $checkoutId;

        $terminalsUsed = $this->doPaymentAndFetchUsedTerminals($payment1);

        $payment2 = $this->getPaymentArray();

        $payment2['_'][AnalyticsEntity::CHECKOUT_ID] = $checkoutId;

        $newTerminalsUsed = $this->doValidPaymentAndFetchUsedTerminals($payment2);


        $intersection = array_intersect($terminalsUsed, $newTerminalsUsed);

        $this->assertEquals(count($intersection), 0);
    }

    public function testOrderMultipleAttempts()
    {
        // first fail the payment and on next attempt with
        // only a order id, ensure the payment goes through
        // the other terminal

        $order = $this->createOrder();

        $this->fixtures->times(5)->create('terminal:dynamic_shared_hdfc_terminal');

        $this->fixtures->times(5)->create('terminal:dynamic_shared_cybersource_hdfc_terminal');

        $payment1 = $this->getPaymentArray();

        $payment1['order_id'] = $order['id'];

        $this->ba->publicAuth();

        $terminalsUsed = $this->doPaymentAndFetchUsedTerminals($payment1);

        $payment = $this->getLastPayment();

        $payment2 = $this->getPaymentArray();

        $payment2['order_id'] = $order['id'];

        $newTerminalsUsed = $this->doValidPaymentAndFetchUsedTerminals($payment2);

        $intersection = array_intersect($terminalsUsed, $newTerminalsUsed);

        $this->assertEquals(count($intersection), 0);

        $this->assertEquals($payment['order_id'], $order['id']);

    }

    public function testMultipleAttemptsFail()
    {
        // payment simply fails here since neither checkout id not order
        // id is provided here.

        $this->fixtures->times(5)->create('terminal:dynamic_shared_hdfc_terminal');

        $this->fixtures->times(5)->create('terminal:dynamic_shared_cybersource_hdfc_terminal');

        $payment1 = $this->getPaymentArray();

        $terminalsUsed = $this->doPaymentAndFetchUsedTerminals($payment1);

        $payment2 = $this->getPaymentArray();

        $newTerminalsUsed = $this->doPaymentAndFetchUsedTerminals($payment2);

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

        $data = $this->testData['testMultipleFailAttemptsWithSameTerminals'];

        $this->runRequestResponseFlow($data, function() use ($payment1) {
            $this->doAuthPayment($payment1);
        });

        $payment2 = $this->getPaymentArray();

        $payment2['order_id'] = $order['id'];

        $this->runRequestResponseFlow($data, function() use ($payment2) {
            $this->doAuthPayment($payment2);
        });
    }

    public function testExclusionWithMultipleAvailableTerminals()
    {
        $data = $this->testData['testMultipleFailAttemptsWithSameTerminals'];

        $order = $this->createOrder();

        $this->fixtures->create('terminal:shared_hdfc_terminal');

        $this->fixtures->create('terminal:shared_billdesk_terminal');

        $payment1 = $this->getPaymentArray();

        $payment1['order_id'] = $order['id'];

        $this->ba->publicAuth();

        $this->runRequestResponseFlow($data, function() use ($payment1) {
            $this->doAuthPayment($payment1);
        });

        $payment2 = $this->getPaymentArray();

        $payment2['order_id'] = $order['id'];

        $this->runRequestResponseFlow($data, function() use ($payment1) {
            $this->doAuthPayment($payment1);
        });

        $payment = $this->getLastPayment(true);

        $this->assertEquals($payment['terminal_id'], '1000HdfcShared');

        $this->assertEquals($payment['order_id'], $order['id']);
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

    protected function doValidPaymentAndFetchUsedTerminals($payment)
    {
        $this->doAuthPayment($payment);

        $payment  = $this->getLastPayment(true);

        Payment\Entity::verifyIdAndStripSign($payment['id']);

        $analytics = $this->getEntities('terminal_analytics', array('payment_id' => $payment['id']), true);

        $terminalsUsed = $this->fetchUsedTerminals($analytics);

        return $terminalsUsed;
    }
    protected function doPaymentAndFetchUsedTerminals($payment)
    {
        $data = $this->testData['testMultipleFailAttemptsWithSameTerminals'];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });

        $payment  = $this->getLastPayment(true);

        Payment\Entity::verifyIdAndStripSign($payment['id']);

        $analytics = $this->getEntities('terminal_analytics', array('payment_id' => $payment['id']), true);

        $terminalsUsed = $this->fetchUsedTerminals($analytics);

        return $terminalsUsed;
    }
}
