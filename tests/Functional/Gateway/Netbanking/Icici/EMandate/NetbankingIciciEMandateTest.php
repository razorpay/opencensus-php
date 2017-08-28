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
     * This is the case that the payment failed, but the SI
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

        // The payment failed, but the SI request passed.
        $this->assertEquals('Y', $netbanking['si_status']);

        $this->assertNotNull($netbanking['si_ref_id']);

        $this->assertEquals('9999999999', $netbanking['bank_payment_id']);
    }

    protected function mockSiPaymentFailure()
    {
        $this->mockServerContentFunction(
            function(&$content, $action = null)
            {
                $content['PAID'] = 'N';
                $content['SCHMSG'] = 'Failure';
            });
    }
}
