<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Axis\EMandate;

use RZP\Constants\Entity;
use RZP\Gateway\Netbanking\Axis\Emandate;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Customer\Token;

class NetbankingAxisEMandateTest extends TestCase
{
    use PaymentTrait;

    protected $payment;

    public function setUp()
    {
        $this->gateway = 'netbanking_axis';

        $this->testDataFilePath = __DIR__.'/NetbankingAxisEMandateTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_netbanking_axis_recurring_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->create('customer');

        $this->fixtures->merchant->addFeatures(['charge_at_will', 'e_mandate']);

        $this->payment = $this->getNetbankingRecurringPaymentArray('UTIB');

        $this->payment['account_number'] = '914010009305862';

        unset($this->payment[Entity::CARD]);

        $this->mockTokenex();
    }

    public function testEmandateInitialPayment()
    {
        $payment = $this->payment;

        $this->doAuthPayment($payment);

        $this->assertEMandateEntities();
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthPayment($this->payment);

        $verify = $this->verifyPayment($payment['razorpay_payment_id']);

        assert($verify['payment']['verified'] === 1);

        $verifyResponseContent = $verify['gateway']['verifyResponseContent'];

        $this->assertTestResponse($verifyResponseContent);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertEquals($gatewayPayment[NetbankingEntity::STATUS], Emandate\StatusCode::SUCCESS);

        $this->assertEquals($gatewayPayment[NetbankingEntity::RECEIVED], true);
    }

    public function testPaymentVerifyFailure()
    {
        $payment = $this->doAuthPayment($this->payment);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify_emandate')
            {
                $content[Emandate\ResponseFields::STATUS_CODE] = Emandate\StatusCode::FAILED;
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['razorpay_payment_id']);
        });
    }

    public function testPaymentVerifyAmountMismatch()
    {
        $payment = $this->doAuthPayment($this->payment);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if ($action === 'verify_emandate')
            {
                // Don't need to change status code, since status code would be success from gateway
                $content[Emandate\ResponseFields::AMOUNT] = 12;
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['razorpay_payment_id']);
        });
    }


    protected function assertEmandateEntities()
    {
        $netbanking = $this->getLastEntity('netbanking', true);

        $this->assertEquals('9999999999', $netbanking['bank_payment_id']);
        $this->assertNotNull($netbanking['bank_payment_id']);
        $this->assertNotNull($netbanking['si_token']);

        $token = $this->getLastEntity('token', true);
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals('pay_' . $netbanking['payment_id'], $payment['id']);
        $this->assertEquals($payment['token_id'], $token['id']);
        $this->assertEquals($netbanking['si_token'], $token['gateway_token']);

        $this->assertEquals(Token\RecurringStatus::CONFIRMED, $token[Token\Entity::RECURRING_STATUS]);
    }
}
