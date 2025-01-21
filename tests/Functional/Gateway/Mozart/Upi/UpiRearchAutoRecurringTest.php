<?php

namespace RZP\Tests\Functional\Gateway\Mozart\Upi;

use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\PaymentsUpiRecurringTrait;

class UpiRearchAutoRecurringTest extends TestCase
{
    use PaymentTrait;
    use PaymentsUpiRecurringTrait;

    protected $payment;
    protected $terminal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'mozart';

        $this->terminal = $this->fixtures->create('terminal:dedicated_mindgate_recurring_terminal');

        $this->terminalId = $this->terminal->getId();

        $this->fixtures->create('customer');

        $this->fixtures->merchant->enableUpi('10000000000000');

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->payment = $this->getDefaultUpiRecurringPaymentArray();

        $this->setMockGatewayTrue();

        // Enable UPI payment service in config
        $this->app['config']->set(['applications.upi_payment_service.enabled' => true]);

        $this->mockSplitzTreatmentForAutopayRearch('variant_on');
    }

    public function testAutoRecurringPreDebitSuccess()
    {
        Carbon::setTestNow(Carbon::parse('first day of this month', 'UTC'));

        $this->createDbUpiMandate(['frequency' => 'as_presented']);

        $this->createDbUpiToken();

        $input = $this->getDbUpiAutoRecurringPayment();

        $input['description'] = 'notify_success';

        // The request which we have sent to create the reminder
        $this->assertReminderRequest('createReminder', $createReminder, $pending);

        $response = $this->doS2SRecurringPayment($input);

        $payment = $this->assertUpiDbLastEntity('payment', [
            'gateway' => 'upi_mindgate',
            'cps_route' => 4,
        ]);

        $this->assertArraySubset([
            'razorpay_payment_id'   => $payment->getPublicId(),
            'razorpay_order_id'     => $this->order->getPublicId(),
        ], $response);

        $this->assertArrayHasKey('razorpay_signature', $response);

        // The first reminder call will trigger an update reminder
        $this->assertReminderRequest('updateReminder', $updateReminder, $pending);

        // Making first call from RS, This will call preDebit action on HDFC Gateway
        $this->sendReminderRequest($createReminder);

        $this->assertUpiDbLastEntity('upi_metadata', [
            'vpa'               => 'localuser@icici',
            'rrn'               => '615519221388',
            'npci_txn_id'       => '615519221388',
            'internal_status'   => 'reminder_in_progress_for_authorize',
            'remind_at'         => $updateReminder['reminder_data']['remind_at'],
        ]);

    }
}
