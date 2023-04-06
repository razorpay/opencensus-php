<?php

namespace RZP\Tests\Functional\Payment\TerminalRotator;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\GatewayTimeoutException;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Payment\Analytics;
use RZP\Models\Payment;
use RZP\Models\Merchant\Account;

class TerminalRotatorTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
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

    public function testTerminalRotatorForInvalidEnrollementStatus()
    {

        $this->markTestSkipped();

        // fail the payment with a card that throws invalid enrollment status and
        // succeed wih another terminal and assert so.

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');
        $this->fixtures->create('terminal:shared_hdfc_terminal');

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4012001036298889';

        $newTerminalsUsed = $this->doValidPaymentAndFetchUsedTerminals($payment);

        $this->assertEquals(2, count($newTerminalsUsed));

        $payment = $this->getLastPayment(true);

        $this->assertEquals($payment['gateway'], 'cybersource');
    }

    public function testCheckoutMultipleAttempts()
    {
        $this->markTestSkipped();

        $this->mockCardVault();

        $this->fixtures->times(5)->create('terminal:dynamic_shared_hdfc_terminal');

        $this->fixtures->times(5)->create('terminal:dynamic_shared_cybersource_hdfc_terminal');

        // first fail the payment and on next attempt with
        // only a checkout id, ensure the payment goes through
        // the other terminal

        $payment1 = $this->getDefaultPaymentArray();

        $checkoutId = UniqueIdEntity::generateUniqueIdWithCheckDigit();

        $payment1['_'][Analytics\Entity::CHECKOUT_ID] = $checkoutId;

        // $terminalsUsed = $this->doPaymentAndFetchUsedTerminals($payment1);
        $this->doAuthPayment($payment1);
        $payment = $this->getLastPayment(true);
        $this->fixtures->payment->edit($payment['id'], ['authorized_at' => null, 'status' => 'failed']);
        $terminalsUsed[] = $payment['terminal_id'];

        $payment2 = $this->getDefaultPaymentArray();
        $payment2['_'][Analytics\Entity::CHECKOUT_ID] = $checkoutId;

        $newTerminalsUsed = $this->doValidPaymentAndFetchUsedTerminals($payment2);

        $intersection = array_intersect($terminalsUsed, $newTerminalsUsed);

        $this->assertEquals(count($intersection), 0);
    }

    public function testOrderMultipleAttempts()
    {
        $this->markTestSkipped();

        // first fail the payment and on next attempt with
        // only a order id, ensure the payment goes through
        // the other terminal

        $this->config['app.throw_exception_in_testing'] = false;

        $order = $this->createTestOrder();

        $this->fixtures->times(5)->create('terminal:dynamic_shared_hdfc_terminal');
        $this->fixtures->times(5)->create('terminal:dynamic_shared_cybersource_hdfc_terminal');

        $payment1 = $this->getDefaultPaymentArray();

        $payment1['order_id'] = $order['id'];

        $this->ba->publicAuth();

        // This card number will cause signature validation failure.
        $payment1['card']['number'] = '4012001036853337';
        $this->doAuthPayment($payment1);
        $payment = $this->getLastPayment(true);
        $terminalsUsed[] = $payment['terminal_id'];

        $payment2 = $this->getDefaultPaymentArray();

        $payment2['order_id'] = $order['id'];

        $newTerminalsUsed = $this->doValidPaymentAndFetchUsedTerminals($payment2);

        $intersection = array_intersect($terminalsUsed, $newTerminalsUsed);

        $this->assertEquals(count($intersection), 0);

        $this->assertEquals($payment['order_id'], $order['id']);

    }

    public function testMultipleAttemptsFail()
    {
        $this->markTestSkipped();
        // payment simply fails here since neither checkout id not order
        // id is provided here.

        $this->fixtures->times(5)->create('terminal:dynamic_shared_hdfc_terminal');

        $this->fixtures->times(5)->create('terminal:dynamic_shared_cybersource_hdfc_terminal');

        // Throw timeout exception from cybersource server
        $this->mockServerContentFunction(function(&$content, $action = null)
        {
            if ($action === 'auth_init')
            {
                throw new GatewayTimeoutException('curl error 38', null, true);
            }
        }, 'cybersource');

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

        $order = $this->createTestOrder();

        $this->fixtures->create('terminal:shared_hdfc_terminal');

        $payment1 = $this->getPaymentArray();

        $payment1['order_id'] = $order['id'];

        $this->ba->publicAuth();

        $data = $this->testData['testMultipleFailAttemptsWithSameTerminals'];

        $this->runRequestResponseFlow(
            $data,
            function() use ($payment1)
            {
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

        $order = $this->createTestOrder();

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

        $this->assertEquals($payment['terminal_id'], '1n25f6uN5S1Z5a');

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

    protected function createTestOrder($amount = 50000)
    {
        $input = array(
                'amount'        => $amount,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
            );

        return $this->createOrder($input);
    }
}
