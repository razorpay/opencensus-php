<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Axis\EMandate;

use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Gateway\Netbanking\Axis\Emandate;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

use Carbon\Carbon;

class NetbankingAxisEMandateTest extends TestCase
{
    use PaymentTrait;

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

        unset($this->payment[Entity::CARD]);

        $this->mockTokenex();
    }

    public function testEMandateInitialPayment()
    {
        $payment = $this->payment;

        $this->doAuthPayment($payment);

        $this->assertEMandateEntities();
    }

    public function testEMandateScheduledPayment()
    {
        $payment = $this->payment;

        $this->doAuthPayment($payment);



        $paymentEntity = $this->getLastEntity('payment', true);
        $tokenEntity   = $this->getLastEntity('token', true);

        $payment['token'] = $paymentEntity['token_id'];

        // Second auth payment for the recurring product
        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if($action === 'second_payment')
            {
                $content = true;
            }
        });

        $this->doS2SRecurringPayment($payment);

        $this->assertEMandateEntities(false);
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthPayment($this->payment);

        $verify = $this->verifyPayment($payment['razorpay_payment_id']);

        assert($verify['payment']['verified'] === 1);

        $verifyResponseContent = $verify['gateway']['verifyResponseContent'];

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertEquals($gatewayPayment['status'], Emandate\StatusCode::SUCCESS);
    }

    public function testPaymentVerifyFailure()
    {
        $payment = $this->doAuthPayment($this->payment);

        $this->mockServerContentFunction(function (& $content, $action = null)
        {
            if($action === 'verify_emandate')
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


    protected function assertEMandateEntities()
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
    }
}
