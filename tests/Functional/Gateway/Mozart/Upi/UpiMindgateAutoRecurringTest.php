<?php

namespace RZP\Tests\Functional\Gateway\Mozart\Upi;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Gateway\Upi\Base;
use RZP\Models\Customer\Token;
use RZP\Models\UpiMandate\Entity;
use RZP\Models\UpiMandate\Status;
use RZP\Models\Base\PublicEntity;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\PaymentsUpiRecurringTrait;

class UpiMindgateAutoRecurringTest extends TestCase
{
    use PaymentTrait;
    use PaymentsUpiRecurringTrait;

    protected $payment;
    protected $terminal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'mozart';

        $this->terminal = $this->fixtures->create('terminal:dedicated_mindgate_recurring_terminal', [
            'gateway_merchant_id' => '400660',
        ]);

        $this->terminalId = $this->terminal->getId();

        $this->fixtures->create('customer');

        $this->fixtures->merchant->enableUpi('10000000000000');

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->payment = $this->getDefaultUpiRecurringPaymentArray();

        $this->setMockGatewayTrue();

        $this->setAutopayPricing();

        // Enable UPI payment service in config
        $this->app['config']->set(['applications.upi_payment_service.enabled' => true]);

        $this->mockSplitzTreatmentForAutopayRearch('variant_off');

    }

    public function testAutoRecurringPreDebitInitiationSuccess()
    {
        Carbon::setTestNow(Carbon::parse('first day of this month', 'UTC'));

        $this->createDbUpiMandate(['frequency' => 'as_presented']);

        $this->createDbUpiToken();

        $input = $this->getDbUpiAutoRecurringPayment();

        // The request which we have sent to create the reminder
        $this->assertReminderRequest('createReminder', $createReminder, $pending);

        $response = $this->doS2SRecurringPayment($input);

        $payment = $this->assertUpiDbLastEntity('payment', [
            'gateway' => 'upi_mindgate',
            'cps_route' => 0,
        ]);

        $this->assertArraySubset([
            'razorpay_payment_id'   => $payment->getPublicId(),
            'razorpay_order_id'     => $this->order->getPublicId(),
        ], $response);

        $this->assertArrayHasKey('razorpay_signature', $response);

        // The first reminder call will trigger an update reminder
        $this->assertReminderRequest('updateReminder', $updateReminder, $pending);

        $requestAsserted = [
            'notify'    => false,
            'pay_init'  => false,
        ];

        // Gateway request will be sent in next step
        $this->mockServerRequestFunction(function (& $content, $action) use (& $requestAsserted)
        {
            if ($action === 'notify')
            {
                $requestAsserted['notify'] = true;

                $paymentId = $content['payment']['id'];
                $paymentCreatedAt = $content['payment']['created_at'];

                $this->assertArraySubset([
                    'act'   => 'notify',
                    'ano'   => 1,
                    'ext'   => $paymentCreatedAt + 90000,
                    'sno'   => 2,
                    'id'    => $paymentId . '0notify' . 1,
                ], $content['upi']['gateway_data']);

                // All the entities sent to mozart
                $this->assertSame([
                    'action',
                    'gateway',
                    'terminal',
                    'payment',
                    'merchant',
                    'upi_mandate',
                    'upi',
                ], array_keys($content));

                return;
            }
        });

        // Making first call from RS, This will call preDebit action on HDFC Gateway
        $this->sendReminderRequest($createReminder);

        $this->assertTrue($requestAsserted['notify']);

        $this->assertUpiDbLastEntity('upi_metadata', [
            'vpa'               => 'localuser@icici',
            'rrn'               => '615519221396',
            'umn'               => 'FirstUpiRecPayment@razorpay',
            'internal_status'   => 'reminder_in_progress_for_authorize',
            'remind_at'         => $updateReminder['reminder_data']['remind_at'],
        ]);

        $this->assertUpiDbLastEntity('upi', [
            'status_code'       => '0',
            'gateway_data'      => [
                'act'   => 'notify',
                'ano'   => 1,
                'ext'   => $payment->getCreatedAt() + 90000,
                'sno'   => 2,
            ],
        ]);
    }

    public function testAutoRecurringPaymentNotifyFails()
    {
        $this->createDbUpiMandate(['frequency' => 'as_presented']);

        $this->createDbUpiToken();

        $input = $this->getDbUpiAutoRecurringPayment([
            'description' => 'notify_fails',
        ]);

        // The request which we have sent to create the reminder
        $this->assertReminderRequest('createReminder', $createReminder, $pending);

        $this->doS2SRecurringPayment($input);

        $payment = $this->assertUpiDbLastEntity('payment', [
            'gateway'   => 'upi_mindgate',
            'status'    => 'created',
            'verify_at' => null,
        ]);

        // The first reminder call will trigger an update reminder
        $this->assertReminderRequest('updateReminder', $updateReminder, $pending);

        $calls = [];

        // Gateway request will be sent in next step
        $this->mockServerRequestFunction(function (& $content, $action) use ($calls)
        {
            if ($action === 'notify')
            {
                $gatewayData = $content['upi']['gateway_data'];
                $calls[$gatewayData['ano']] = $gatewayData;
            }
        });

        // Making first call from RS, This will call preDebit action on Gateway
        $this->sendReminderRequest($createReminder);

        $payment = $this->assertUpiDbLastEntity('payment', [
            'gateway'   => 'upi_mindgate',
            'status'    => 'created',
            'verify_at' => null,
        ], false);

        $metadata = $this->assertUpiDbLastEntity('upi_metadata', [
            'vpa'               => 'localuser@icici',
            'rrn'               => '615519221396',
            'umn'               => 'FirstUpiRecPayment@razorpay',
            'internal_status'   => 'reminder_in_progress_for_pre_debit',
            'remind_at'         => $updateReminder['reminder_data']['remind_at'],
        ]);

        $this->assertUpiDbLastEntity('upi', [
            'status_code'       => '08',
            'gateway_data'      => [
                'act'   => 'notify',
                'ano'   => 1,
                'ext'   => $payment->getCreatedAt() + 90000,
                'sno'   => 2,
            ],
        ]);

        // Skipping to first retry time, with 90 seconds buffer
        Carbon::setTestNow(Carbon::now()->addMinutes(10)->addSeconds(90));

        // Remind at should be in last 3 minutes
        $this->assertLessThan(Carbon::now()->getTimestamp(), $metadata->getRemindAt());
        $this->assertGreaterThan(Carbon::now()->subMinute(3)->getTimestamp(), $metadata->getRemindAt());

        // Making first retry call from RS, This will again call preDebit action on Gateway
        $this->sendReminderRequest($updateReminder);

        $payment = $this->assertUpiDbLastEntity('payment', [
            'gateway'   => 'upi_mindgate',
            'status'    => 'created',
            'verify_at' => null,
        ], false);

        $metadata = $this->assertUpiDbLastEntity('upi_metadata', [
            'internal_status'   => 'reminder_in_progress_for_pre_debit',
            'remind_at'         => $updateReminder['reminder_data']['remind_at'],
        ]);

        $this->assertUpiDbLastEntity('upi', [
            'status_code'       => '08',
            'gateway_data'      => [
                'ano'   => 2,
            ],
        ]);

        // Skipping to second retry time, with 90 seconds buffer
        Carbon::setTestNow(Carbon::now()->addMinutes(20)->addSeconds(90));

        // Remind at should be in last 3 minutes
        $this->assertLessThan(Carbon::now()->getTimestamp(), $metadata->getRemindAt());
        $this->assertGreaterThan(Carbon::now()->subMinute(3)->getTimestamp(), $metadata->getRemindAt());

        // Making second retry call from RS, This will again call preDebit action on Gateway
        $this->sendReminderRequest($updateReminder);

        $payment = $this->assertUpiDbLastEntity('payment', [
            'gateway'               => 'upi_mindgate',
            'status'                => 'failed',
            'verify_at'             => null,
            'internal_error_code'   => 'GATEWAY_ERROR_BANK_OFFLINE',
        ], false);

        $metadata = $this->assertUpiDbLastEntity('upi_metadata', [
            'internal_status'   => 'pre_debit_failed',
            'remind_at'         => null,
        ]);

        $this->assertUpiDbLastEntity('upi', [
            'status_code'       => '08',
            'gateway_data'      => [
                'ano'   => 3,
            ],
        ]);
    }

    public function testAutoRecurringPaymentDebitInitiationSuccess()
    {
        Carbon::setTestNow(Carbon::parse('first day of this month', 'UTC'));

        $this->createDbUpiMandate([
            'frequency' => 'as_presented',
            'start_time' => Carbon::now()->getTimestamp(),
            'end_time' => Carbon::now()->addDays(60)->getTimestamp(),
            'recurring_value' => null,
        ]);

        $this->createDbUpiToken();

        $input = $this->getDbUpiAutoRecurringPayment();

        // The request which we have sent to create the reminder
        $this->assertReminderRequest('createReminder', $createReminder, $pending);

        $response = $this->doS2SRecurringPayment($input);

        $payment = $this->assertUpiDbLastEntity('payment', [
            'gateway' => 'upi_mindgate',
        ]);

        $this->assertArraySubset([
            'razorpay_payment_id' => $payment->getPublicId(),
            'razorpay_order_id' => $this->order->getPublicId(),
        ], $response);

        $this->assertArrayHasKey('razorpay_signature', $response);

        // The first reminder call will trigger an update reminder
        $this->assertReminderRequest('updateReminder', $updateReminder, $pending);

        $requestAsserted = [
            'notify' => false,
            'pay_init' => false,
        ];

        // Gateway request will be sent in next step
        $this->mockServerRequestFunction(function (&$content, $action) use (& $requestAsserted) {
            $this->assertSame($content['merchant']['category'], '5399');
            $this->assertSame($content['merchant']['billing_label'], 'Test Merchant');

            if ($action === 'notify') {
                $requestAsserted['notify'] = true;

                $paymentId = $content['payment']['id'];
                $paymentCreatedAt = $content['payment']['created_at'];

                $this->assertSame($content['upi_mandate']['used_count'], 2);

                $this->assertArraySubset([
                    'act' => 'notify',
                    'ano' => 1,
                    'ext' => $paymentCreatedAt + 90000,
                    'sno' => 2,
                    'id' => $paymentId . '0notify' . 1,
                ], $content['upi']['gateway_data']);

                // All the entities sent to mozart
                $this->assertSame([
                    'action',
                    'gateway',
                    'terminal',
                    'payment',
                    'merchant',
                    'upi_mandate',
                    'upi',
                ], array_keys($content));

                return;
            }
        });

        // Making first call from RS, This will call preDebit action on HDFC Gateway
        $this->sendReminderRequest($createReminder);

        $this->assertTrue($requestAsserted['notify']);

        $metadata = $this->assertUpiDbLastEntity('upi_metadata', [
            'vpa' => 'localuser@icici',
            'rrn' => '615519221396',
            'umn' => 'FirstUpiRecPayment@razorpay',
            'internal_status' => 'reminder_in_progress_for_authorize',
            'remind_at' => $updateReminder['reminder_data']['remind_at'],
        ]);

        $this->assertUpiDbLastEntity('upi', [
            'status_code' => '0',
            'gateway_data' => [
                'act' => 'notify',
                'ano' => 1,
                'ext' => $payment->getCreatedAt() + 90000,
                'sno' => 2,
            ],
        ]);

        // Skipping to execution time, with 90 seconds buffer
        Carbon::setTestNow(Carbon::now()->addHours(25)->addSeconds(90));

        // Remind at should be in last 3 minutes
        $this->assertLessThan(Carbon::now()->getTimestamp(), $metadata->getRemindAt());
        $this->assertGreaterThan(Carbon::now()->subMinute(3)->getTimestamp(), $metadata->getRemindAt());

        // Triggering the actual authorization call from RS
        $this->sendReminderRequest($updateReminder);

        $this->assertUpiDbLastEntity('upi', [
            'action' => 'authorize',
            'status_code' => '0',
            'gateway_data' => [
                'act' => 'execte',
                'ano' => 1,
                'ext' => null,
                'sno' => 2,
            ]
        ], false);

        $this->assertUpiDbLastEntity('payment', [
            'status' => 'created',
            'reference1' => null,
            'reference16' => null,
        ], false);
    }

    public function setAutopayPricing()
    {
        $this->ba->adminAuth();

        $upiAutopayPlan = [
            'plan_name'              => 'TestPlan1',
            'procurer'               => 'razorpay',
            'payment_method'         => 'upi',
            'payment_method_subtype' => 'initial',
            'feature'                => 'payment',
            'payment_method_type'    => null,
            'payment_network'        => null,
            'payment_issuer'         => null,
            'percent_rate'           => 100,
            'fixed_rate'             => 200,
            'type'                   => 'pricing',
            'international'          => 0,
            'amount_range_active'    => '0',
            'amount_range_min'       => null,
            'amount_range_max'       => null,
        ];

        $planId = $this->createPricingPlan($upiAutopayPlan)['id'];

        $upiPricingPlan = [
            'plan_name'              => 'TestPlan1',
            'procurer'               => 'razorpay',
            'payment_method'         => 'upi',
            'feature'                => 'payment',
            'payment_method_type'    => null,
            'payment_network'        => null,
            'payment_issuer'         => null,
            'percent_rate'           => 100,
            'fixed_rate'             => 100,
            'type'                   => 'pricing',
            'international'          => 0,
            'amount_range_active'    => '0',
            'amount_range_min'       => null,
            'amount_range_max'       => null,
        ];

        $recurringPricingPlan = [
            'plan_name'              => 'TestPlan1',
            'procurer'               => 'razorpay',
            'payment_method'         => 'upi',
            'feature'                => 'recurring',
            'payment_method_type'    => null,
            'payment_network'        => null,
            'payment_issuer'         => null,
            'percent_rate'           => 300,
            'fixed_rate'             => 300,
            'type'                   => 'pricing',
            'international'          => 0,
            'amount_range_active'    => '0',
            'amount_range_min'       => null,
            'amount_range_max'       => null,
        ];

        $upiAutoAutopayPlan = [
            'plan_name'              => 'TestPlan1',
            'procurer'               => 'razorpay',
            'payment_method'         => 'upi',
            'payment_method_subtype' => 'auto',
            'feature'                => 'payment',
            'payment_method_type'    => null,
            'payment_network'        => null,
            'payment_issuer'         => null,
            'percent_rate'           => 100,
            'fixed_rate'             => 600,
            'type'                   => 'pricing',
            'international'          => 0,
            'amount_range_active'    => '0',
            'amount_range_min'       => null,
            'amount_range_max'       => null,
        ];

        $this->addPricingPlanRule($planId, $upiPricingPlan);

        $this->addPricingPlanRule($planId, $upiAutoAutopayPlan);

        $this->addPricingPlanRule($planId, $recurringPricingPlan);

        $this->fixtures->merchant->edit('10000000000000', ['pricing_plan_id' => $planId]);
    }

    protected function addPricingPlanRule($id, $rule = [])
    {
        $defaultRule = [
            'payment_method' => 'card',
            'payment_method_type'  => 'credit',
            'payment_network' => 'MAES',
            'payment_issuer' => 'HDFC',
            'percent_rate' => 1000,
            'international' => 0,
            'amount_range_active' => '0',
            'amount_range_min' => null,
            'amount_range_max' => null,
        ];

        $rule = array_merge($defaultRule, $rule);

        $request = array(
            'method' => 'POST',
            'url' => '/pricing/'.$id.'/rule',
            'content' => $rule);

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }
}
