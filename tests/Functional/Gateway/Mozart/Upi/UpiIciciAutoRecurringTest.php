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
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\PaymentsUpiRecurringTrait;

class UpiIciciAutoRecurringTest extends TestCase
{
    use PaymentTrait;
    use PaymentsUpiRecurringTrait;

    protected $payment;
    protected $terminal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'mozart';

        $this->terminal = $this->fixtures->create('terminal:shared_icici_recurring_terminal', [
            'gateway_merchant_id' => '400660',
        ]);

        $this->terminalId = $this->terminal->getId();

        $this->fixtures->create('customer');

        $this->fixtures->merchant->enableUpi('10000000000000');

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->payment = $this->getDefaultUpiRecurringPaymentArray();

        $this->setMockGatewayTrue();
    }

    public function testAutoRecurringPaymentSuccess()
    {
        Carbon::setTestNow(Carbon::parse('first day of this month', 'UTC'));

        $this->createDbUpiMandate();

        $this->createDbUpiToken();

        $requestAsserted = false;

        $input = $this->getDbUpiAutoRecurringPayment();

        // The request which we have sent to create the reminder
        $this->assertReminderRequest('createReminder', $createReminder, $pending);

        $response = $this->doS2SRecurringPayment($input);

        $payment = $this->assertUpiDbLastEntity('payment', [
            'gateway' => 'upi_icici',
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

        // Making first call from RS, This will call preDebit action no ICICI Gateway
        $this->sendReminderRequest($createReminder);

        $this->assertTrue($requestAsserted['notify']);

        $metadata = $this->assertUpiDbLastEntity('upi_metadata', [
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

        // Skipping to execution time, with 90 seconds buffer
        Carbon::setTestNow(Carbon::now()->addHours(25)->addSeconds(90));

        // Remind at should be in last 3 minutes
        $this->assertLessThan(Carbon::now()->getTimestamp(), $metadata->getRemindAt());
        $this->assertGreaterThan(Carbon::now()->subMinute(3)->getTimestamp(), $metadata->getRemindAt());

        // Triggering the actual authorization call from RS
        $this->sendReminderRequest($updateReminder);

        $this->assertUpiDbLastEntity('upi', [
            'action'        => 'authorize',
            'status_code'   => '0',
            'gateway_data'  => [
                'act'   => 'execte',
                'ano'   => 1,
                'ext'   => null,
                'sno'   => 2,
            ]
        ], false);

        $this->assertUpiDbLastEntity('payment', [
            'status'        => 'created',
            'reference1'    => null,
            'reference16'   => null,
        ], false);

        $content = $this->mockServer()->getAsyncCallbackResponseAutoDebitForIcici($payment);

        $this->makeS2sCallbackAndGetContent($content, 'upi_icici');

        $this->assertUpiDbLastEntity('payment', [
            'status'        => 'captured',
            'reference1'    => 'HDFC00001124',
            'reference16'   => '019721040510',
        ], false);

        $this->assertUpiDbLastEntity('upi', [
            'action'                => 'authorize',
            'merchant_reference'    => $this->upiMandate->getId(),
            'gateway_payment_id'    => 'GatewayPaymentIdDebit',
            'status_code'           => '0',
            'npci_txn_id'           => 'HDFC00001124',
            'npci_reference_id'     => '019721040510',
        ]);
    }

    public function testAutoRecurringPaymentOnCancelledToken()
    {
        $this->createDbUpiMandate();

        $this->createDbUpiToken();

        $this->token->setRecurringStatus(Token\RecurringStatus::CANCELLED);
        $this->token->saveOrFail();

        $input = $this->getDbUpiAutoRecurringPayment();

        $this->makeRequestAndCatchException(
            function() use ($input)
            {
                $this->doS2SRecurringPayment($input);
            },
            Exception\BadRequestException::class,
            'Token is not confirmed for recurring payments');
    }

    public function testAutoRecurringPaymentSubsequentDebitSuccessOnRetry()
    {
        Carbon::setTestNow(Carbon::parse('first day of this month', 'UTC'));

        $this->createDbUpiMandate();

        $this->createDbUpiToken();

        $requestAsserted = false;

        $input = $this->getDbUpiAutoRecurringPayment();

        // The request which we have sent to create the reminder
        $this->assertReminderRequest('createReminder', $createReminder, $pending);

        $response = $this->doS2SRecurringPayment($input);

        $payment = $this->assertUpiDbLastEntity('payment', [
            'gateway' => 'upi_icici',
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
        ];

        $calls = [];

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

        // Making first call from RS, This will call preDebit action no ICICI Gateway
        $this->sendReminderRequest($createReminder);

        $this->assertTrue($requestAsserted['notify']);

        $metadata = $this->assertUpiDbLastEntity('upi_metadata', [
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

        // Skipping to execution time, with 90 seconds buffer
        Carbon::setTestNow(Carbon::now()->addHours(25)->addSeconds(90));

        // Remind at should be in last 3 minutes
        $this->assertLessThan(Carbon::now()->getTimestamp(), $metadata->getRemindAt());
        $this->assertGreaterThan(Carbon::now()->subMinute(3)->getTimestamp(), $metadata->getRemindAt());

        // Mock the response for debit request to the gateway
        $this->mockServerContentFunction(function (& $content, $action) use (& $calls)
        {
            if ($action === 'pay_init')
            {
                $content = array_merge_recursive($content, [
                    'success'   => false,
                    'error'     => [
                        'gateway_error_code'        => '5009',
                        'internal_error_code'       => ErrorCode::GATEWAY_ERROR_SYSTEM_UNAVAILABLE,
                        'gateway_error_description' => 'Service unavailable.'
                    ],
                ]);

                $calls[$action]['attempts'] = 1 + ($calls[$action]['attempts'] ?? 0);
            }
        });

        // Triggering the actual authorization call from RS - 1st Subsequent Debit request
        $this->sendReminderRequest($updateReminder);

        // Assert payment is not marked as failed
        $this->assertUpiDbLastEntity('payment', [
            'gateway'   => 'upi_icici',
            'status'    => 'created',
            'verify_at' => null,
        ], false);

        // Assert internal status in metadata is still reminder_in_progress_for_authorize
        // and remind_at value is set
        $metadata = $this->assertUpiDbLastEntity('upi_metadata', [
            'internal_status'   => 'reminder_in_progress_for_authorize',
            'remind_at'         => $updateReminder['reminder_data']['remind_at'],
        ], false);

        // Assert action and attempt number (ano)
        $this->assertUpiDbLastEntity('upi', [
            'action'            => 'authorize',
            'status_code'       => '0',
            'gateway_data'      => [
                'act'   => 'execte',
                'ano'   => $calls['pay_init']['attempts'],
                'ext'   => null,
                'sno'   => 2,
            ],
        ], false);

        // Skipping to execution time (by 30 minutes for 1st Retry), with 90 seconds buffer
        Carbon::setTestNow(Carbon::now()->addMinutes(30)->addSeconds(90));

        // Remind at should be in last 3 minutes
        $this->assertLessThan(Carbon::now()->getTimestamp(), $metadata->getRemindAt());
        $this->assertGreaterThan(Carbon::now()->subMinute(3)->getTimestamp(), $metadata->getRemindAt());

        // Triggering the authorization call from RS - 2nd Subsequent Debit request
        $this->sendReminderRequest($updateReminder);

        // Assert payment is not marked as failed
        $this->assertUpiDbLastEntity('payment', [
            'gateway'   => 'upi_icici',
            'status'    => 'created',
            'verify_at' => null,
        ], false);

        // Assert internal status in metadata is still reminder_in_progress_for_authorize
        // and remind_at value is set
        $metadata = $this->assertUpiDbLastEntity('upi_metadata', [
            'internal_status'   => 'reminder_in_progress_for_authorize',
            'remind_at'         => $updateReminder['reminder_data']['remind_at'],
        ]);

        // Assert attempt number (ano)
        $this->assertUpiDbLastEntity('upi', [
            'status_code'       => '0',
            'gateway_data'      => [
                'ano'   => $calls['pay_init']['attempts'],
            ],
        ]);

        // Skipping to execution time (by 60 minutes for 2nd Retry), with 90 seconds buffer
        Carbon::setTestNow(Carbon::now()->addMinutes(60)->addSeconds(90));

        // Remind at should be in last 3 minutes
        $this->assertLessThan(Carbon::now()->getTimestamp(), $metadata->getRemindAt());
        $this->assertGreaterThan(Carbon::now()->subMinute(3)->getTimestamp(), $metadata->getRemindAt());

        // Mock the response for debit request to the gateway (for successful response)
        $this->mockServerContentFunction(function (& $content, $action) use (& $calls)
        {
            if ($action === 'pay_init')
            {
                $calls[$action]['attempts'] = 1 + ($calls[$action]['attempts'] ?? 0);

                return $content;
            }
        });

        // Triggering the authorization call from RS - 3nd Subsequent Debit request
        $this->sendReminderRequest($updateReminder);

        $this->assertUpiDbLastEntity('payment', [
            'status'        => 'created',
            'reference1'    => null,
            'reference16'   => null,
        ], false);

        // Assert internal status in metadata is authorize_initiated
        // and remind_at is null
        $this->assertUpiDbLastEntity('upi_metadata', [
            'internal_status'   => 'authorize_initiated',
            'remind_at'         =>  null,
        ]);

        // Assert attempt number (ano)
        $this->assertUpiDbLastEntity('upi', [
            'status_code'   => '0',
            'gateway_data'  => [
                'ano'   => $calls['pay_init']['attempts'],
            ]
        ], false);

        $content = $this->mockServer()->getAsyncCallbackResponseAutoDebitForIcici($payment);

        $this->makeS2sCallbackAndGetContent($content, 'upi_icici');

        // Assert Payment Captured
        $this->assertUpiDbLastEntity('payment', [
            'status'        => 'captured',
            'reference1'    => 'HDFC00001124',
            'reference16'   => '019721040510',
        ], false);


        // Assert internal status in metadata is authorized
        // and remind_at is null
        $this->assertUpiDbLastEntity('upi_metadata', [
            'internal_status'   => 'authorized',
            'remind_at'         =>  null,
        ], false);

        $this->assertUpiDbLastEntity('upi', [
            'action'                => 'authorize',
            'merchant_reference'    => $this->upiMandate->getId(),
            'gateway_payment_id'    => 'GatewayPaymentIdDebit',
            'status_code'           => '0',
            'npci_txn_id'           => 'HDFC00001124',
            'npci_reference_id'     => '019721040510',
        ]);
    }

    public function testAutoRecurringPaymentSubsequentDebitFailureOnRetry()
    {
        Carbon::setTestNow(Carbon::parse('first day of this month', 'UTC'));

        $this->createDbUpiMandate();

        $this->createDbUpiToken();

        $requestAsserted = false;

        $input = $this->getDbUpiAutoRecurringPayment();

        // The request which we have sent to create the reminder
        $this->assertReminderRequest('createReminder', $createReminder, $pending);

        $response = $this->doS2SRecurringPayment($input);

        $payment = $this->assertUpiDbLastEntity('payment', [
            'gateway' => 'upi_icici',
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
        ];

        $calls = [];

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

        // Making first call from RS, This will call preDebit action no ICICI Gateway
        $this->sendReminderRequest($createReminder);

        $this->assertTrue($requestAsserted['notify']);

        $metadata = $this->assertUpiDbLastEntity('upi_metadata', [
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

        // Skipping to execution time, with 90 seconds buffer
        Carbon::setTestNow(Carbon::now()->addHours(25)->addSeconds(90));

        // Remind at should be in last 3 minutes
        $this->assertLessThan(Carbon::now()->getTimestamp(), $metadata->getRemindAt());
        $this->assertGreaterThan(Carbon::now()->subMinute(3)->getTimestamp(), $metadata->getRemindAt());

        // Mock the response for debit request to the gateway
        $this->mockServerContentFunction(function (& $content, $action) use (& $calls)
        {
            if ($action === 'pay_init')
            {
                $content = array_merge_recursive($content, [
                    'success'   => false,
                    'error'     => [
                        'gateway_error_code'        => '5009',
                        'internal_error_code'       => ErrorCode::GATEWAY_ERROR_SYSTEM_UNAVAILABLE,
                        'gateway_error_description' => 'Service unavailable.'
                    ],
                ]);

                $calls[$action]['attempts'] = 1 + ($calls[$action]['attempts'] ?? 0);
            }
        });

        // Triggering the actual authorization call from RS - 1st Subsequent Debit request
        $this->sendReminderRequest($updateReminder);

        // Assert payment is not marked as failed
        $this->assertUpiDbLastEntity('payment', [
            'gateway'   => 'upi_icici',
            'status'    => 'created',
            'verify_at' => null,
        ], false);

        // Assert internal status in metadata is still reminder_in_progress_for_authorize
        // and remind_at value is set
        $metadata = $this->assertUpiDbLastEntity('upi_metadata', [
            'internal_status'   => 'reminder_in_progress_for_authorize',
            'remind_at'         => $updateReminder['reminder_data']['remind_at'],
        ], false);

        // Assert action and attempt number (ano)
        $this->assertUpiDbLastEntity('upi', [
            'action'            => 'authorize',
            'status_code'       => '0',
            'gateway_data'      => [
                'act'   => 'execte',
                'ano'   => $calls['pay_init']['attempts'],
                'ext'   => null,
                'sno'   => 2,
            ],
        ], false);

        // Skipping to execution time (by 30 minutes for 1st Retry), with 90 seconds buffer
        Carbon::setTestNow(Carbon::now()->addMinutes(30)->addSeconds(90));

        // Remind at should be in last 3 minutes
        $this->assertLessThan(Carbon::now()->getTimestamp(), $metadata->getRemindAt());
        $this->assertGreaterThan(Carbon::now()->subMinute(3)->getTimestamp(), $metadata->getRemindAt());

        // Triggering the authorization call from RS - 2nd Subsequent Debit request
        $this->sendReminderRequest($updateReminder);

        // Assert payment is not marked as failed
        $this->assertUpiDbLastEntity('payment', [
            'gateway'   => 'upi_icici',
            'status'    => 'created',
            'verify_at' => null,
        ], false);

        // Assert internal status in metadata is still reminder_in_progress_for_authorize
        // and remind_at value is set
        $metadata = $this->assertUpiDbLastEntity('upi_metadata', [
            'internal_status'   => 'reminder_in_progress_for_authorize',
            'remind_at'         => $updateReminder['reminder_data']['remind_at'],
        ]);

        // Assert attempt number (ano)
        $this->assertUpiDbLastEntity('upi', [
            'status_code'       => '0',
            'gateway_data'      => [
                'ano'   => $calls['pay_init']['attempts'],
            ],
        ]);

        // Skipping to execution time (by 60 minutes for 2nd Retry), with 90 seconds buffer
        Carbon::setTestNow(Carbon::now()->addMinutes(60)->addSeconds(90));

        // Remind at should be in last 3 minutes
        $this->assertLessThan(Carbon::now()->getTimestamp(), $metadata->getRemindAt());
        $this->assertGreaterThan(Carbon::now()->subMinute(3)->getTimestamp(), $metadata->getRemindAt());

        // Triggering the authorization call from RS - 3nd Subsequent Debit request
        $this->sendReminderRequest($updateReminder);

        // Assert payment is marked as failed and error related fields
        // because after 2nd retry fails, no more retries should happen
        // and payment should be marked as failed
        $this->assertUpiDbLastEntity('payment', [
            'gateway'               => 'upi_icici',
            'status'                => 'failed',
            'error_code'            => 'GATEWAY_ERROR',
            'internal_error_code'   => 'GATEWAY_ERROR_SYSTEM_UNAVAILABLE',
            'error_description'     => 'Payment was unsuccessful due to a temporary issue. Any amount deducted will be refunded within 5-7 working days.'
        ], false);

        // Assert internal status in metadata is still reminder_in_progress_for_authorize
        // and remind_at is null
        $this->assertUpiDbLastEntity('upi_metadata', [
            'internal_status'   => 'failed',
            'remind_at'         =>  null,
        ]);

        // Assert attempt number (ano)
        $this->assertUpiDbLastEntity('upi', [
            'status_code'       => '0',
            'gateway_data'      => [
                'ano'   => $calls['pay_init']['attempts'],
            ],
        ]);
    }

    public function testAutoRecurringPaymentNotifyRetry()
    {
        $this->createDbUpiMandate();

        $this->createDbUpiToken();

        $input = $this->getDbUpiAutoRecurringPayment([
            'description' => 'notify_fails_twice',
        ]);

        // The request which we have sent to create the reminder
        $this->assertReminderRequest('createReminder', $createReminder, $pending);

        $this->doS2SRecurringPayment($input);

        $payment = $this->assertUpiDbLastEntity('payment', [
            'gateway'   => 'upi_icici',
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
            'gateway'   => 'upi_icici',
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
            'gateway'   => 'upi_icici',
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
            'gateway'   => 'upi_icici',
            'status'    => 'created',
            'verify_at' => null,
        ], false);

        $metadata = $this->assertUpiDbLastEntity('upi_metadata', [
            'internal_status'   => 'reminder_in_progress_for_authorize',
            'remind_at'         => $updateReminder['reminder_data']['remind_at'],
        ]);

        $this->assertUpiDbLastEntity('upi', [
            'status_code'       => '0',
            'gateway_data'      => [
                'ano'   => 3,
            ],
        ]);

        // Skipping to final time of authorization, with 90 seconds buffer
        Carbon::setTestNow(Carbon::now()->addHours(25)->addSeconds(90));

        // Remind at should be in last 3 minutes
        $this->assertLessThan(Carbon::now()->getTimestamp(), $metadata->getRemindAt());
        $this->assertGreaterThan(Carbon::now()->subMinute(3)->getTimestamp(), $metadata->getRemindAt());

        // TODO: Add authorization part here too
    }

    public function testAutoRecurringPaymentNotifyFails()
    {
        $this->createDbUpiMandate();

        $this->createDbUpiToken();

        $input = $this->getDbUpiAutoRecurringPayment([
            'description' => 'notify_fails',
        ]);

        // The request which we have sent to create the reminder
        $this->assertReminderRequest('createReminder', $createReminder, $pending);

        $this->doS2SRecurringPayment($input);

        $payment = $this->assertUpiDbLastEntity('payment', [
            'gateway'   => 'upi_icici',
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
            'gateway'   => 'upi_icici',
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
            'gateway'   => 'upi_icici',
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
            'gateway'               => 'upi_icici',
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

    public function testAutoRecurringPaymentNotifyFatal()
    {
        $this->createDbUpiMandate();

        $this->createDbUpiToken();

        $input = $this->getDbUpiAutoRecurringPayment([
            'description' => 'notify_fails',
        ]);

        // The request which we have sent to create the reminder
        $this->assertReminderRequest('createReminder', $createReminder, $pending);

        $this->doS2SRecurringPayment($input);

        $payment = $this->assertUpiDbLastEntity('payment', [
            'gateway'   => 'upi_icici',
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
                throw new Exception\ServerErrorException('Application down', ErrorCode::GATEWAY_ERROR_FATAL_ERROR);
            }
        });

        // Making first call from RS, This will call preDebit action on Gateway
        $this->makeRequestAndCatchException(function() use ($createReminder)
            {
                $this->sendReminderRequest($createReminder);
            },
            Exception\ServerErrorException::class,
            'Application down');

        $payment = $this->assertUpiDbLastEntity('payment', [
            'gateway'   => 'upi_icici',
            'status'    => 'created',
            'verify_at' => null,
        ], false);

        $metadata = $this->assertUpiDbLastEntity('upi_metadata', [
            'internal_status'   => 'pre_debit_initiated',
            'reminder_id'       => 'TestReminderId',
        ]);

        $this->assertUpiDbLastEntity('upi', [
            'status_code'       => 'pending',
            'gateway_data'      => [
                'act'   => 'notify',
                'ano'   => 1,
                'ext'   => $payment->getCreatedAt() + 90000,
                'sno'   => 2,
            ],
        ]);

        // TODO: Add cron code here
    }

    public function testAutoRecurringPaymentDescription()
    {
        $this->createDbUpiMandate();

        $this->createDbUpiToken();

        $input = $this->getDbUpiAutoRecurringPayment([
            'description' => 'I am test payment to be execute at 1998530840 with seqno 45'
        ]);

        // The request which we have sent to create the reminder
        $this->assertReminderRequest('createReminder', $createReminder, $pending);

        $this->doS2SRecurringPayment($input);

        // The first reminder call will trigger an update reminder
        $this->assertReminderRequest('updateReminder', $updateReminder, $pending);

        // Making first call from RS, This will call preDebit action no ICICI Gateway
        $this->sendReminderRequest($createReminder);

        $this->assertUpiDbLastEntity('upi', [
            'status_code'       => '0',
            'gateway_data'      => [
                'act'   => 'notify',
                'ano'   => 1,
                'ext'   => 1998530840,
                'sno'   => '45',
            ],
        ]);
    }

    /***** Tests for as presented mandates *******/

    public function testAutoRecurringPaymentSuccessForAsPresented()
    {
        Carbon::setTestNow(Carbon::parse('first day of this month', 'UTC'));

        $this->createDbUpiMandate([
            'frequency'         => 'as_presented',
            'start_time'        => Carbon::now()->getTimestamp(),
            'end_time'          => null,
            'recurring_value'   => null,
            ]);

        $this->createDbUpiToken();

        $input = $this->getDbUpiAutoRecurringPayment();

        // The request which we have sent to create the reminder
        $this->assertReminderRequest('createReminder', $createReminder, $pending);

        $response = $this->doS2SRecurringPayment($input);

        $payment = $this->assertUpiDbLastEntity('payment', [
            'gateway' => 'upi_icici',
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

                $this->assertSame($content['upi_mandate']['used_count'], 2);

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

        // Making first call from RS, This will call preDebit action no ICICI Gateway
        $this->sendReminderRequest($createReminder);

        $this->assertTrue($requestAsserted['notify']);

        $metadata = $this->assertUpiDbLastEntity('upi_metadata', [
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

        // Skipping to execution time, with 90 seconds buffer
        Carbon::setTestNow(Carbon::now()->addHours(25)->addSeconds(90));

        // Remind at should be in last 3 minutes
        $this->assertLessThan(Carbon::now()->getTimestamp(), $metadata->getRemindAt());
        $this->assertGreaterThan(Carbon::now()->subMinute(3)->getTimestamp(), $metadata->getRemindAt());

        // Triggering the actual authorization call from RS
        $this->sendReminderRequest($updateReminder);

        $this->assertUpiDbLastEntity('upi', [
            'action'        => 'authorize',
            'status_code'   => '0',
            'gateway_data'  => [
                'act'   => 'execte',
                'ano'   => 1,
                'ext'   => null,
                'sno'   => 2,
            ]
        ], false);

        $this->assertUpiDbLastEntity('payment', [
            'status'        => 'created',
            'reference1'    => null,
            'reference16'   => null,
        ], false);

        $content = $this->mockServer()->getAsyncCallbackResponseAutoDebitForIcici($payment);

        $this->makeS2sCallbackAndGetContent($content, 'upi_icici');

        $this->assertUpiDbLastEntity('payment', [
            'status'        => 'captured',
            'reference1'    => 'HDFC00001124',
            'reference16'   => '019721040510',
        ], false);

        $this->assertUpiDbLastEntity('upi', [
            'action'                => 'authorize',
            'merchant_reference'    => $this->upiMandate->getId(),
            'gateway_payment_id'    => 'GatewayPaymentIdDebit',
            'status_code'           => '0',
            'npci_txn_id'           => 'HDFC00001124',
            'npci_reference_id'     => '019721040510',
        ]);

        $this->assertUpiDbLastEntity('upi_mandate', [
            'used_count'        => 2,
            'frequency'         => 'as_presented',
            'sequence_number'   => 2
        ]);
    }

    public function testAutoRecurringPaymentNotifyRetryForAsPresented()
    {
        $this->createDbUpiMandate([
            'frequency'         => 'as_presented',
            'start_time'        => Carbon::now()->getTimestamp(),
            'end_time'          => null,
            'recurring_value'   => null,
        ]);

        $this->createDbUpiToken();

        $input = $this->getDbUpiAutoRecurringPayment([
            'description' => 'notify_fails_twice',
        ]);

        // The request which we have sent to create the reminder
        $this->assertReminderRequest('createReminder', $createReminder, $pending);

        $this->doS2SRecurringPayment($input);

        $payment = $this->assertUpiDbLastEntity('payment', [
            'gateway'   => 'upi_icici',
            'status'    => 'created',
            'verify_at' => null,
        ]);

        // The first reminder call will trigger an update reminder
        $this->assertReminderRequest('updateReminder', $updateReminder, $pending);

        $calls = [];

        // Gateway request will be sent in next step
        $this->mockServerRequestFunction(function (& $content, $action) use (& $calls)
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
            'gateway'   => 'upi_icici',
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

        $this->assertUpiDbLastEntity('upi_mandate', [
            'used_count'        => 2,
            'frequency'         => 'as_presented',
            'sequence_number'   => 2
        ]);

        // Skipping to first retry time, with 90 seconds buffer
        Carbon::setTestNow(Carbon::now()->addMinutes(10)->addSeconds(90));

        // Remind at should be in last 3 minutes
        $this->assertLessThan(Carbon::now()->getTimestamp(), $metadata->getRemindAt());
        $this->assertGreaterThan(Carbon::now()->subMinute(3)->getTimestamp(), $metadata->getRemindAt());

        // Making first retry call from RS, This will again call preDebit action on Gateway
        $this->sendReminderRequest($updateReminder);

        $payment = $this->assertUpiDbLastEntity('payment', [
            'gateway'   => 'upi_icici',
            'status'    => 'created',
            'verify_at' => null,
        ], false);

        $metadata = $this->assertUpiDbLastEntity('upi_metadata', [
            'internal_status'   => 'reminder_in_progress_for_pre_debit',
            'remind_at'         => $updateReminder['reminder_data']['remind_at'],
        ]);

        $this->assertUpiDbLastEntity('upi_mandate', [
            'used_count'        => 2,
            'sequence_number'   => 2
        ]);

        $this->assertUpiDbLastEntity('upi', [
            'status_code'       => '08',
            'gateway_data'      => [
                'ano'   => 2,
                'sno'   => 2,
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
            'gateway'   => 'upi_icici',
            'status'    => 'created',
            'verify_at' => null,
        ], false);

        $metadata = $this->assertUpiDbLastEntity('upi_metadata', [
            'internal_status'   => 'reminder_in_progress_for_authorize',
            'remind_at'         => $updateReminder['reminder_data']['remind_at'],
        ]);

        $this->assertUpiDbLastEntity('upi', [
            'status_code'       => '0',
            'gateway_data'      => [
                'ano'   => 3,
                'sno'   => 2,
            ],
        ]);

        $this->assertUpiDbLastEntity('upi_mandate', [
            'used_count'        => 2,
            'sequence_number'   => 2,
        ]);

        $this->assertArraySubset([
            '1'=> [
                'act' => 'notify',
                'ano' => 1,
                'sno' => 2,
            ],
            '2'=> [
                'act' => 'notify',
                'ano' => 2,
                'sno' => 2,
            ],
            '3'=> [
                'act' => 'notify',
                'ano' => 3,
                'sno' => 2,
            ],
        ], $calls, 'Mismatch in gateway data for notify');

        // Skipping to final time of authorization, with 90 seconds buffer
        Carbon::setTestNow(Carbon::now()->addHours(25)->addSeconds(90));

        // Remind at should be in last 3 minutes
        $this->assertLessThan(Carbon::now()->getTimestamp(), $metadata->getRemindAt());
        $this->assertGreaterThan(Carbon::now()->subMinute(3)->getTimestamp(), $metadata->getRemindAt());

        // Triggering the actual authorization call from RS
        $this->sendReminderRequest($updateReminder);

        $this->assertUpiDbLastEntity('upi', [
            'action'        => 'authorize',
            'status_code'   => '0',
            'gateway_data'  => [
                'act'   => 'execte',
                'ano'   => 1,
                'ext'   => null,
                'sno'   => 2,
            ]
        ], false);

        $this->assertUpiDbLastEntity('payment', [
            'status'        => 'created',
            'reference1'    => null,
            'reference16'   => null,
        ], false);

        $content = $this->mockServer()->getAsyncCallbackResponseAutoDebitForIcici($payment);

        $this->makeS2sCallbackAndGetContent($content, 'upi_icici');

        $this->assertUpiDbLastEntity('payment', [
            'status'        => 'captured',
            'reference1'    => 'HDFC00001124',
            'reference16'   => '019721040510',
        ], false);

        $this->assertUpiDbLastEntity('upi', [
            'action'                => 'authorize',
            'merchant_reference'    => $this->upiMandate->getId(),
            'gateway_payment_id'    => 'GatewayPaymentIdDebit',
            'status_code'           => '0',
            'npci_txn_id'           => 'HDFC00001124',
            'npci_reference_id'     => '019721040510',
        ]);

        $this->assertUpiDbLastEntity('upi_mandate', [
            'used_count'        => 2,
            'frequency'         => 'as_presented',
            'sequence_number'   => 2
        ]);
    }
}
