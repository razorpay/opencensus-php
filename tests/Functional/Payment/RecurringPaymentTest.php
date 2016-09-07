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

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->merchant->editFeatures('recurring');

        $this->mockTokenex();
    }

    public function testRecurringPaymentCreateFeatureDisabled()
    {
        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = 500000;
        $payment['recurring'] = true;
        $payment['customer_id'] = 'cust_100000customer';
        $payment['card']['number'] = '4012001038443335';

        $this->fixtures->merchant->editFeatures('');

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });
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

        $this->assertEquals($paymentEntity['terminal_id'], '1RecurringTerm');

        $this->assertEquals(true, $tokenEntity['recurring']);

        $tokenId = 'token_' . $paymentEntity['token_id'];

        unset($payment['card']);

        $payment['token'] = $tokenId;

        $content = $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentEntity['terminal_id'], '2RecurringTerm');
    }

    public function testRecurringPaymentFailedCardNotSupported()
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
    }

    public function testRecurringPaymentUsingSavedCardTokenNotRecurring()
    {
        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = 500000;
        $payment['recurring'] = true;
        $payment['token'] = '10000cardtoken';
        $payment['customer_id'] = 'cust_100000customer';
        unset($payment['card']);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment) {
            $this->doAuthPayment($payment);
        });
    }

    public function testRecurringPaymentUsingSavedCardTokenRecurring()
    {
        $this->ba->publicAuth();

        $payment = $this->getDefaultPaymentArray();

        $payment['amount'] = 500000;
        $payment['recurring'] = true;
        $payment['token'] = '10000cardtoken';
        $payment['customer_id'] = 'cust_100000customer';
        unset($payment['card']);

        $this->fixtures->base->editEntity('card', '100000000lcard', ["type" => 'credit']);
        $this->fixtures->base->editEntity('token', '100000custcard', ["recurring" => true]);

        $content = $this->doAuthAndCapturePayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);

        $this->assertEquals($paymentEntity['terminal_id'], '2RecurringTerm');
    }
}
