<?php

namespace RZP\Tests\Functional\Payment;

use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class RecurringPaymentTest extends TestCase
{
    use PaymentTrait;

    protected $recurringPlan;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/RecurringPaymentTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->payment = $this->getDefaultPaymentArray();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->mockTokenex();
    }

    public function testRecurringPaymentCreate()
    {
        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = 500000;
        $payment['recurring'] = true;
        $payment['customer_id'] = 'cust_100000customer';
        $payment['card']['number'] = '4012001038443335';

        $content = $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);
        $tokenEntity   = $this->getLastEntity('token', true);

        $this->assertEquals(true, $tokenEntity['authenticated']);

        $token = $paymentEntity['token'];

        $payment['card'] = [];

        $payment['token'] = $token;

        $content = $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);
    }

    public function testRecurringPaymentFailed()
    {
        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = 500000;
        $payment['recurring'] = true;
        $payment['customer_id'] = 'cust_100000customer';
        $payment['card']['number'] = '4000000000000002';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });

        $tokenEntity = $this->getLastEntity('token', true);

        $this->assertEquals(true, $tokenEntity['recurring']);
        $this->assertEquals(false, $tokenEntity['authenticated']);
    }

    public function testRecurringPaymentUsingSavedCardToken()
    {
        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = 500000;
        $payment['recurring'] = true;
        $payment['token'] = '10000cardtoken';
        $payment['customer_id'] = 'cust_100000customer';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });
    }
}
