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

        $this->fixtures->create('terminal:shared_netbanking_icici_recurring_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->create('customer');

        $this->fixtures->plan->create();

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->mockTokenex();
    }

    public function testEMandateInitialPayment()
    {
        $payment = $this->getNetbankingRecurringPaymentArray('ICIC');

        $this->ba->publicAuth();

        $this->doAuthPayment($payment);

        $netbanking = $this->getLastEntity('netbanking', true);

        $this->assertEquals('9999999999', $netbanking['bank_payment_id']);
        $this->assertNotNull($netbanking['bank_payment_id']);
        $this->assertNotNull($netbanking['schedule_ref_id']);
    }

    public function testEMandateScheduledPayment()
    {
        $subscription = $this->createSubscription();

        $payment = $this->getNetbankingRecurringPaymentArray('ICIC');
        $payment['subscription_id'] = $subscription['id'];

        $this->ba->publicAuth();

        $this->doAuthPayment($payment);

        $subscription = $this->getLastEntity('subscription', true);

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
    }
}
