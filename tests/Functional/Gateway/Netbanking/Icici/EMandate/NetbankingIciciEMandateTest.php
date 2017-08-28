<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Icici\EMandate;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingIciciEMandateTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/NetbankingIciciEMandateTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_icici';

        $this->fixtures->create('terminal:shared_netbanking_icici_recurring_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->create('customer');

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->payment = $this->getNetbankingRecurringPaymentArray('ICIC');
        unset($this->payment['card']);

        $this->mockTokenex();
    }

    public function testEMandateInitialPayment()
    {
        $payment = $this->payment;

        $this->doAuthPayment($payment);

        $this->assertEMandateEntities();
    }

    /**
     * This is the case that the payment failed
     */
    public function testEMandateInitialPaymentFailure()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockSiPaymentFailure();

        $this->runRequestResponseFlow(
            $data,
            function()
            {
                $this->doAuthPayment($this->payment);
            });

        $this->assertEMandateInitialFailEntities();
    }

    public function testEMandateScheduledPayment()
    {
        $payment = $this->payment;

        $this->doAuthPayment($payment);

        $paymentEntity = $this->getLastEntity('payment', true);
        $tokenEntity   = $this->getLastEntity('token', true);

        $payment['token'] = $paymentEntity['token_id'];

        //
        // Second auth payment for the recurring product
        //
        $this->doS2SRecurringPayment($payment);

        $this->assertEMandateEntities(false);
    }


    public function testScheduledPaymentWithRejectedToken()
    {
        $payment = $this->payment;

        $this->mockRejectedToken();

        $this->doAuthPayment($payment);

        // Assert that the token was rejected
        $netbanking = $this->getLastEntity('netbanking', true);
        $this->assertEquals('N', $netbanking['si_status']);
        $this->assertEquals('Failure', $netbanking['si_message']);

        $paymentEntity = $this->getLastEntity('payment', true);

        $payment['token'] = $paymentEntity['token_id'];

        $this->mockRejectedToken(false);

        //
        // Second auth payment for the recurring product.
        // Since an invalid recurring token is used here, the
        // payment will go through like a regular non-recurring payment.
        // This is because it is not a "First Recurring" payment/
        //
        $this->doS2SRecurringPayment($payment);

        $netbanking = $this->getLastEntity('netbanking', true);

        $this->assertNull($netbanking['si_ref_id']);
        $this->assertNull($netbanking['si_message']);
        $this->assertNull($netbanking['si_status']);
        $this->assertEquals('9999999999', $netbanking['bank_payment_id']);
    }

    protected function assertEMandateEntities($initial = true)
    {
        $netbanking = $this->getLastEntity('netbanking', true);

        $this->assertNotNull($netbanking['si_ref_id']);

        if ($initial === true)
        {
            $this->assertEquals('9999999999', $netbanking['bank_payment_id']);
            $this->assertEquals('Y', $netbanking['si_status']);
            $this->assertEquals('Success', $netbanking['si_message']);
        }

        $token = $this->getLastEntity('token', true);
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['token_id'], $token['id']);
        $this->assertEquals($netbanking['si_ref_id'], $token['gateway_token']);

        $this->assertEquals(true, $token['recurring']);
        $this->assertEquals(100000, $token['max_amount']);
        $this->assertEquals('confirmed', $token['recurring_status']);
    }

    protected function assertEMandateInitialFailEntities()
    {
        $netbanking = $this->getLastEntity('netbanking', true);
        $token = $this->getLastEntity('token', true);
        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['token_id'], $token['id']);
        $this->assertEquals(null, $token['gateway_token']);
        $this->assertNotNull($netbanking['si_ref_id']);

        $this->assertEquals('N', $netbanking['si_status']);

        $this->assertNotNull($netbanking['si_ref_id']);

        $this->assertEquals('9999999999', $netbanking['bank_payment_id']);
    }

    protected function mockSiPaymentFailure()
    {
        $this->mockServerContentFunction(
            function(&$content, $action = null)
            {
                $content['PAID'] = 'N';
                $content['SCHSTATUS'] = 'N';
                $content['SCHMSG'] = 'Failure';
            });
    }

    protected function mockRejectedToken($apply = true)
    {
        $this->mockServerContentFunction(
            function(&$content, $action = null) use ($apply)
            {
                if ($apply === true)
                {
                    $content['SCHSTATUS'] = 'N';
                    $content['SCHMSG'] = 'Failure';
                }
            });
    }
}
