<?php

namespace RZP\Tests\Functional\PaymentsUpi;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Services\RazorXClient;
use RZP\Models\UpiMandate\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Customer\Token\RecurringStatus;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\PaymentsUpiRecurringTrait;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;

class UpiRecurringPaymentSharpTest extends TestCase
{
    use PaymentTrait;
    use InteractsWithSession;
    use PaymentsUpiRecurringTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        // use old autopay pricing for old test cases
        $this->setRazorxMock(function ($mid, $feature, $mode)
        {
            return $this->getRazoxVariant($feature, 'upi_autopay_pricing_blacklist', 'on');
        });
    }

    public function testCreateFirstUpiRecurringPaymentSuccess()
    {
        $orderId = $this->createUpiRecurringOrder();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $order = $this->assertUpiDbLastEntity('order', [
            'status'            => 'created',
            'method'            => 'upi',
            'payment_capture'   => true,
        ]);

        $upiMandate = $this->assertUpiDbLastEntity('upi_mandate', [
            'status'            => 'created',
            'max_amount'        => 150000,
            'frequency'         => 'monthly',
            'merchant_id'       => '10000000000000',
            'recurring_type'    => 'before',
            'recurring_value'   => 31,
            'umn'               => null,
            'rrn'               => null,
            'npci_txn_id'       => null,
            'gateway_data'      => null,
            'late_confirmed'    => false,
            'used_count'        => 0,
            'confirmed_at'      => null,
        ]);

        $payment = $this->getDefaultUpiRecurringPaymentArray($orderId);
        $payment['order_id'] = $orderId;
        $payment['customer_id'] = 'cust_100000customer';

        $response = $this->doAuthPayment($payment);

        $this->assertArraySubset([
            'type'          => 'async',
            'method'        => 'upi',
            'data'          => [
                'vpa'       => 'random@razorpay',
            ],
        ], $response);

        $payment = $this->getDbLastPayment();
        $token = $this->getDbLastEntity('token');

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $mandate = $this->assertUpiDbLastEntity('upi_mandate', [
            'status'            => 'confirmed',
            'token_id'          => $token->getId(),
            'umn'               => $payment->getId() . '@razorpay',
            'rrn'               => '001000100001',
            'npci_txn_id'       => 'RZP12345678910111213141516',
            'gateway_data'      => [
                'id'            => 'ID001000100001',
            ],
            'confirmed_at'      => $upiMandate->getConfirmedAt(),
            'late_confirmed'    => false,
            'used_count'        => 1,
            'sequence_number'   => 1,
        ]);
        $this->assertGreaterThanOrEqual(Carbon::now()->subMinute()->getTimestamp(), $mandate->getConfirmedAt());

        $order->refresh();
        $token->refresh();
        $upiMandate->refresh();

        $this->assertEquals($upiMandate['token_id'], $token['id']);
        $this->assertEquals($upiMandate['customer_id'], $token['customer_id']);
        $this->assertEquals('confirmed', $upiMandate['status']);

        $this->assertArraySubset([
            'method'            => 'upi',
            'recurring_status'  => 'confirmed',
            'recurring'         => true,
        ], $token->toArray(), true);

        $this->assertArraySubset([
            'method'            => 'upi',
            'status'            => 'paid',
            'authorized'        => true,
            'payment_capture'   => true,
        ], $order->toArray(), true);

        $payment = $this->getDbLastPayment();

        $this->assertTrue($payment->isCaptured());
        $this->assertNotNull($payment->getReference16());
        $this->assertEquals('vishnu@icici', $payment->getVpa());
    }

    public function testCreateFirstUpiRecurringPaymentFailure()
    {
        $orderId = $this->createUpiRecurringOrder();

        $payment = $this->getDefaultUpiRecurringPaymentArray($orderId);
        $payment['order_id'] = $orderId;
        $payment['customer_id'] = 'cust_100000customer';

        $payment['vpa'] = 'failure@razorpay';

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            });

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $token = $this->getDbLastEntity('token');

        $this->assertEquals($upiMandate['token_id'], $token['id']);

        $this->assertEquals($upiMandate['customer_id'], $token['customer_id']);

        $this->assertEquals('created', $upiMandate['status']);

        $payment = $this->getDbLastPayment();

        $this->assertTrue($payment->isFailed());
        $this->assertNull($payment->getReference16());
        $this->assertEquals('failure@razorpay', $payment->getVpa());
    }

    public function testCreateFirstUpiRecurringPaymentRejected()
    {
        $orderId = $this->createUpiRecurringOrder();

        $payment = $this->getDefaultUpiRecurringPaymentArray($orderId);
        $payment['order_id'] = $orderId;
        $payment['customer_id'] = 'cust_100000customer';

        $payment['vpa'] = 'rejected@razorpay';

        $this->doAuthPayment($payment);

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $token = $this->getDbLastEntity('token');

        $this->assertEquals($upiMandate['token_id'], $token['id']);

        $this->assertEquals($upiMandate['customer_id'], $token['customer_id']);

        $this->assertEquals('created', $upiMandate['status']);

        $payment = $this->getDbLastPayment();

        $this->assertTrue($payment->isFailed());
        $this->assertNull($payment->getReference16());
        $this->assertEquals('rejected@razorpay', $payment->getVpa());
    }

    public function testCreateMonthlyAutoRecurringPaymentSuccess()
    {
        $this->createDbUpiMandate();

        $this->createDbUpiToken();

        // The request which we have sent to create the reminder
        $createReminder = null;

        $this->mockReminderService('createReminder',
            function($request, $merchantId) use (& $createReminder)
            {
                $payment = $this->getDbLastPayment();
                $metadata = $payment->getUpiMetadata();

                $createReminder = $request;

                $this->assertArraySubset([
                    'internal_status'   => 'reminder_pending_for_pre_debit',
                    'reminder_id'       => null,
                    'remind_at'         => $createReminder['reminder_data']['remind_at']
                ], $metadata->toArray(), true);

            });

        $response = $this->doS2SRecurringPayment($this->getDbUpiAutoRecurringPayment());

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getDbLastPayment();

        // Now we can assert the request to RS
        $this->assertArraySubset([
            'namespace'     => 'upi_auto_recurring',
            'entity_id'     => $payment->getId(),
            'entity_type'   => 'payment',
            'callback_url'  => 'reminders/send/test/payment/upi_auto_recurring/' . $payment->getId(),
        ], $createReminder, true);

        $this->assertArraySubset([
            'terminal_id'   => '1000SharpTrmnl',
            'status'        => 'created',
            'verify_at'     => null,
        ], $payment->toArray(), true);

        $metadata = $payment->getUpiMetadata();

        $this->assertArraySubset([
            'type'              => 'recurring',
            'flow'              => 'collect',
            'mode'              => 'auto',
            'reference'         => null,
            'rrn'               => null,
            'umn'               => null,
            'npci_txn_id'       => null,
            'internal_status'   => 'reminder_in_progress_for_pre_debit',
            'reminder_id'       => 'TestReminderId',
            'remind_at'         => $createReminder['reminder_data']['remind_at']
        ], $metadata->toArray(), true);

        $this->assertUpiDbLastEntity('upi_mandate', [
            'used_count'    => 2,
        ]);

        // Update reminder call to RS
        $updateReminder = null;

        // In the call from reminder service, an updateReminder function will be called
        $this->mockReminderService('updateReminder',
            function($request, $merchantId) use (& $updateReminder, $createReminder, $metadata)
            {
                $updateReminder = $request;
                $metadata->refresh();

                $this->assertArraySubset([
                    'internal_status'   => 'pre_debit_initiated',
                    'remind_at'         => $createReminder['reminder_data']['remind_at']
                ], $metadata->toArray(), true);
                $createRemindAt = $createReminder['reminder_data']['remind_at'];
                $updateRemindAt = $updateReminder['reminder_data']['remind_at'];


                $this->assertTrue(($createRemindAt + 90) <= $updateRemindAt);
            });

        Carbon::setTestNow(Carbon::now()->addSeconds(90));

        // Now we will initiate the reminder
        $this->sendReminderRequest($createReminder);

        $metadata->refresh();

        $this->assertArraySubset([
            'type'              => 'recurring',
            'flow'              => 'collect',
            'mode'              => 'auto',
            'reference'         => 'preDebitReference:1',
            'rrn'               => null,
            'umn'               => $this->upiMandate->umn,
            'npci_txn_id'       => null,
            'internal_status'   => 'reminder_in_progress_for_authorize',
            'reminder_id'       => 'TestReminderId',
            'remind_at'         => $updateReminder['reminder_data']['remind_at']
        ], $metadata->toArray(), true);

        $this->sendReminderRequest($updateReminder);

        $payment->refresh();

        $this->assertArraySubset([
            'terminal_id'   => '1000SharpTrmnl',
            'status'        => 'captured',
            'verify_at'     => null,
            'refund_at'     => null,
            'reference16'   => '001000100001',
            'vpa'           => 'localuser@icici',
        ], $payment->toArray(), true);

        $metadata->refresh();

        $this->assertArraySubset([
            'type'              => 'recurring',
            'flow'              => 'collect',
            'mode'              => 'auto',
            'reference'         => 'DebitReference:1',
            'rrn'               => '001000100001',
            'umn'               => $this->upiMandate->umn,
            'npci_txn_id'       => 'npci_txn_id_for_' . $payment->getId(),
            'internal_status'   => 'authorized',
            'reminder_id'       => 'TestReminderId',
            'remind_at'         => null,
        ], $metadata->toArray(), true);
    }

    public function testCreateMonthlyAutoRecurringPaymentTimeout()
    {
        $now = Carbon::now();

        Carbon::setTestNow(Carbon::now()->subDays(2));

        $mandate = $this->createFirstUpiRecurringPayment();

        $payment = $this->getDefaultUpiRecurringPaymentArray();

        $payment['token'] = $mandate->token->getPublicId();

        $response = $this->doS2SRecurringPayment($payment);

        $payment = $this->getDbLastPayment();

        Carbon::setTestNow($now);

        // We can see that payment is in fact one day older
        $this->assertTrue($payment->getCreatedAt() < (time() - 86400));

        $this->ba->cronAuth();

        $response = $this->timeoutOldPayment();

        // Not a single payment timed out
        $this->assertSame(0, $response['count']);

        $payment->refresh();
        // it is still created
        $this->assertTrue($payment->isCreated());

        // Now mark payment order than three days by just one secound
        $payment->setCreatedAt(Carbon::now()->subSeconds(259201)->getTimestamp());
        $payment->saveOrFail();

        $response = $this->timeoutOldPayment();

        // Not a single payment timed out
        $this->assertSame(1, $response['count']);

        $payment->refresh();
        $this->assertTrue($payment->isFailed());
        $this->assertSame('BAD_REQUEST_PAYMENT_TIMED_OUT', $payment->getInternalErrorCode());
    }

    public function testCreateDailyAutoRecurringPaymentAndNotifySkips()
    {
        $this->createDbUpiMandate([
            'frequency' => 'daily',
        ]);

        $this->createDbUpiToken();

        // The request which we have sent to create the reminder
        $createReminder = null;
        $this->mockReminderService('createReminder',
            function($request, $merchantId) use (& $createReminder)
            {
                $createReminder = $request;

                $this->assertUpiDbLastEntity('payment', [
                    'status' => 'created',
                ]);
                $this->assertUpiDbLastEntity('upi_metadata', [
                    'internal_status'   => 'reminder_pending_for_pre_debit',
                    'reminder_id'       => null,
                    'remind_at'         => $createReminder['reminder_data']['remind_at']
                ]);
            });

        $this->doS2SRecurringPayment($this->getDbUpiAutoRecurringPayment([
            'description'   => 'notify_skips',
        ]));

        $this->sendReminderRequest($createReminder);

        $payment = $this->assertUpiDbLastEntity('payment', [
            'status' => 'captured',
        ], false);

        $this->assertUpiDbLastEntity('upi_metadata', [
            'vpa'               => 'localuser@icici',
            'reference'         => 'DebitReference:1',
            'rrn'               => '001000100001',
            'umn'               => $this->upiMandate->umn,
            'npci_txn_id'       => 'npci_txn_id_for_' . $payment->getId(),
            'internal_status'   => 'authorized',
            'reminder_id'       => 'TestReminderId',
            'remind_at'         => null,
        ]);
    }

    public function testCreateMonthlyAutoRecurringPaymentNotifyFailsTwice()
    {
        $this->createDbUpiMandate();
        $this->createDbUpiToken();

        $input = $this->getDbUpiAutoRecurringPayment([
            'description' => 'notify_fails_twice',
        ]);

        // The request which we have sent to create the reminder
        $this->assertReminderRequest('createReminder', $createReminder, $pending);

        $this->doS2SRecurringPayment($input);

        $this->assertUpiMetadataStatus('reminder_pending_for_pre_debit', $pending);
        // Now waiting for first reminder from RS
        $this->assertUpiMetadataStatus('reminder_in_progress_for_pre_debit');
        // The first reminder call will trigger an update reminder
        $this->assertReminderRequest('updateReminder', $updateReminder, $pending);
        // Making first call from RS
        $this->sendReminderRequest($createReminder);

        $this->assertUpiMetadataStatus('pre_debit_initiated', $pending);
        // Because of first failure status moved back to reminder in progress
        $this->assertUpiMetadataStatus('reminder_in_progress_for_pre_debit');
        // Now making second call from RS
        $this->sendReminderRequest($updateReminder);

        $this->assertUpiMetadataStatus('pre_debit_initiated', $pending);
        // Because of Second failure status moved back to reminder pending
        $this->assertUpiMetadataStatus('reminder_in_progress_for_pre_debit');

        $this->assertUpiDbLastEntity('upi_metadata', [
            'reference'  => 'preDebitReference:2',
        ]);

        // Third attempt for recurring
        $this->sendReminderRequest($updateReminder);
        // Because of Second failure status moved back to reminder pending
        $this->assertUpiMetadataStatus('reminder_in_progress_for_authorize');

        // Mandate is still showing two used count
        $this->assertUpiDbLastEntity('upi_mandate', [
            'used_count'    => 2,
        ]);
    }

    public function testCreateMonthlyAutoRecurringPaymentNotifyFailsCompletely()
    {
        $this->createDbUpiMandate();
        $this->createDbUpiToken();

        $input = $this->getDbUpiAutoRecurringPayment([
            'description' => 'notify_fails',
        ]);

        // The request which we have sent to create the reminder
        $this->assertReminderRequest('createReminder', $createReminder, $pending);

        $this->doS2SRecurringPayment($input);

        $this->assertUpiMetadataStatus('reminder_pending_for_pre_debit', $pending);
        // Now waiting for first reminder from RS
        $this->assertUpiMetadataStatus('reminder_in_progress_for_pre_debit');
        // The first reminder call will trigger an update reminder
        $this->assertReminderRequest('updateReminder', $updateReminder, $pending);
        // Making first call from RS
        $this->sendReminderRequest($createReminder);

        $this->assertUpiMetadataStatus('pre_debit_initiated', $pending);
        // Because of first failure status moved back to reminder in progress
        $this->assertUpiMetadataStatus('reminder_in_progress_for_pre_debit');
        // Now making second call from RS
        $this->sendReminderRequest($updateReminder);

        $this->assertUpiMetadataStatus('pre_debit_initiated', $pending);
        // Because of Second failure status moved back to reminder pending
        $this->assertUpiMetadataStatus('reminder_in_progress_for_pre_debit');

        $this->assertUpiDbLastEntity('upi_metadata', [
            'reference'  => 'preDebitReference:2',
        ]);

        // Third attempt for recurring
        $this->sendReminderRequest($updateReminder);
        // Because of third failure status is finally failed
        $this->assertUpiMetadataStatus('pre_debit_failed');

        // We do not need to verify payments where the pre debit has failed 3 times
        $this->assertUpiDbLastEntity('payment', [
            'status'                => 'failed',
            'internal_error_code'   => 'GATEWAY_ERROR_BANK_OFFLINE',
            'verify_at'             => null,
        ]);
    }

    public function testCreateMonthlyAutoRecurringPaymentSuccessOnCallback()
    {
        $this->createDbUpiMandate();

        $this->createDbUpiToken();

        $this->assertReminderRequest('createReminder', $createReminder, $pending);

        $this->doS2SRecurringPayment($this->getDbUpiAutoRecurringPayment([
            'description' => 'authorize_on_callback',
        ]));

        $payment = $this->assertUpiDbLastEntity('payment', [
            'status'    => 'created',
            'verify_at' => null,
        ]);

        $this->assertUpiMetadataStatus('reminder_pending_for_pre_debit', $pending);

        $this->assertReminderRequest('updateReminder', $updateReminder, $pending);

        $this->sendReminderRequest($createReminder);

        $this->assertUpiMetadataStatus('pre_debit_initiated', $pending);
        $this->assertUpiMetadataStatus('reminder_in_progress_for_authorize');

        // Fields updated by notify
        $this->assertUpiDbLastEntity('upi_metadata', [
            'rrn'           => null,
            'npci_txn_id'   => null,
            'umn'           => 'FirstUpiRecPayment@razorpay'
        ]);

        // Not this reminder will try to get payment authorized
        $this->sendReminderRequest($updateReminder);

        $this->assertUpiMetadataStatus('pre_debit_initiated', $pending);

        $this->assertUpiMetadataStatus('authorize_initiated');

        $this->assertUpiDbLastEntity('upi_metadata', [
            'rrn'           => '001000100000',
            'npci_txn_id'   => 'expecting_from_callback',
        ]);

        $payment = $this->assertUpiDbLastEntity('payment', [
            'status'        => 'created',
        ], false);
        // Verify is enabled for the payment
        $this->assertGreaterThanOrEqual(Carbon::now()->getTimestamp(), $payment->getVerifyAt());

        (new Payment\Service)->s2scallback($payment->getPublicId(), [
            'status'        => 'authorized',
            'rrn'           => '001000100002',
            'npci_txn_id'   => 'npci_txn_id_for_' . $payment->getId(),
        ]);

        $this->assertUpiDbLastEntity('payment', [
            'status'    => 'captured',
        ], false);
    }

    public function testCreateMonthlyAutoRecurringPaymentAuthorizeFailsTwice()
    {
        $this->createDbUpiMandate();
        $this->createDbUpiToken();

        $input = $this->getDbUpiAutoRecurringPayment([
            'description' => 'authorize_fails_twice',
        ]);

        // The request which we have sent to create the reminder
        $this->assertReminderRequest('createReminder', $createReminder, $pending);

        $this->doS2SRecurringPayment($input);

        $this->assertUpiMetadataStatus('reminder_pending_for_pre_debit', $pending);
        // Now waiting for first reminder from RS
        $this->assertUpiMetadataStatus('reminder_in_progress_for_pre_debit');
        // The first reminder call will trigger an update reminder
        $this->assertReminderRequest('updateReminder', $updateReminder, $pending);
        // Making first call from RS
        $this->sendReminderRequest($createReminder);

        $this->assertUpiMetadataStatus('pre_debit_initiated', $pending);
        // Because of notify succeed, status moved to reminder in progress
        $this->assertUpiMetadataStatus('reminder_in_progress_for_authorize');
        // Now making first call to authorize from RS
        $this->sendReminderRequest($updateReminder);

        $this->assertUpiMetadataStatus('reminder_in_progress_for_authorize', $pending);
        // Because of first failure status still is reminder progress
        $this->assertUpiMetadataStatus('reminder_in_progress_for_authorize');

        $this->assertUpiDbLastEntity('upi_metadata', [
            'reference'  => 'DebitReference:1',
        ]);

        // Second attempt for recurring authorize
        $this->sendReminderRequest($updateReminder);
        // Because of Second failure status still in reminder progress
        $this->assertUpiMetadataStatus('reminder_in_progress_for_authorize');

        // Third attempt for recurring authorize
        $this->sendReminderRequest($updateReminder);

        $this->assertUpiMetadataStatus('authorized');

        $this->assertUpiDbLastEntity('payment', [
            'status'    => 'captured',
        ]);

        // Mandate is still showing two used count
        $this->assertUpiDbLastEntity('upi_mandate', [
            'used_count'    => 2,
        ]);
    }

    public function testCreateMonthlyAutoRecurringPaymentAuthorizeFailsCompletely()
    {
        $this->createDbUpiMandate();
        $this->createDbUpiToken();

        $input = $this->getDbUpiAutoRecurringPayment([
            'description' => 'authorize_fails',
        ]);

        // The request which we have sent to create the reminder
        $this->assertReminderRequest('createReminder', $createReminder, $pending);

        $this->doS2SRecurringPayment($input);

        $this->assertUpiMetadataStatus('reminder_pending_for_pre_debit', $pending);
        // Now waiting for first reminder from RS
        $this->assertUpiMetadataStatus('reminder_in_progress_for_pre_debit');
        // The first reminder call will trigger an update reminder
        $this->assertReminderRequest('updateReminder', $updateReminder, $pending);
        // Making first call from RS
        $this->sendReminderRequest($createReminder);

        $this->assertUpiMetadataStatus('pre_debit_initiated', $pending);
        // Because of notify succeed, status moved to reminder in progress
        $this->assertUpiMetadataStatus('reminder_in_progress_for_authorize');
        // Now making first call to authorize from RS
        $this->sendReminderRequest($updateReminder);

        $this->assertUpiMetadataStatus('reminder_in_progress_for_authorize', $pending);
        // Because of first failure status still is reminder progress
        $this->assertUpiMetadataStatus('reminder_in_progress_for_authorize');

        $this->assertUpiDbLastEntity('upi_metadata', [
            'reference'  => 'DebitReference:1',
        ]);

        // Second attempt for recurring authorize
        $this->sendReminderRequest($updateReminder);
        // Because of Second failure status still in reminder progress
        $this->assertUpiMetadataStatus('reminder_in_progress_for_authorize');

        $this->assertUpiDbLastEntity('payment', [
            'status'        => 'created',
            'verify_at'     => null,
        ]);

        // Third attempt for recurring authorize
        $this->sendReminderRequest($updateReminder);

        $this->assertUpiMetadataStatus('failed');

        $payment = $this->assertUpiDbLastEntity('payment', [
            'status'            => 'failed',
            'verify_bucket'     => 0,
        ], false);

        // Verify is set to be in 2 minutes in future
        $this->assertGreaterThanOrEqual(Carbon::now()->getTimestamp(), $payment->getVerifyAt());

        // Mandate is still showing two used count
        $this->assertUpiDbLastEntity('upi_mandate', [
            'used_count'    => 2,
        ]);
    }

    public function testRevokeMandate()
    {
        $mandate = $this->createFirstUpiRecurringPayment();

        $token = $this->getDbLastEntity('token');

        $this->revokeUpiRecurringMandate($token->getPublicId());

        $mandate->reload();

        $this->assertEquals(Status::REVOKED, $mandate['status']);

        $this->assertSame(RecurringStatus::CANCELLED, $mandate->token->getRecurringStatus());
    }

    public function testRevokeCreatedMandate()
    {
        $orderId = $this->createUpiRecurringOrder();

        $payment = $this->getDefaultUpiRecurringPaymentArray();
        $payment['order_id'] = $orderId;
        $payment['customer_id'] = 'cust_100000customer';
        $payment['vpa'] = 'failure@razorpay';

        $this->makeRequestAndCatchException(
            function() use ($payment)
            {
                $this->doAuthPayment($payment);
            });

        $mandate = $this->getDbLastEntity('upi_mandate');

        $token = $this->getDbLastEntity('token');

        $this->assertEquals(Status::CREATED, $mandate['status']);

        $data = $this->getRevokeCreatedMandateResponse();

        $this->runRequestResponseFlow($data, function() use ($token) {
            $this->revokeUpiRecurringMandate($token->getPublicId());
        });

        $mandate->reload();

        $this->assertEquals(Status::CREATED, $mandate['status']);
    }

    protected function getRevokeCreatedMandateResponse()
    {
        return [
            'response' => [
                'content'     => [
                    'error' => [
                        'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description'   => PublicErrorDescription::BAD_REQUEST_INVALID_TOKEN_FOR_CANCEL,
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'               => 'RZP\Exception\BadRequestException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_TOKEN_FOR_CANCEL,
            ],
        ];
    }

    protected function revokeUpiRecurringMandate(string $tokenId)
    {
        $this->ba->privateAuth();

        $request = [
            'method'  => 'PUT',
            'content' => [],
            'url' => '/customers/cust_100000customer/tokens/' . $tokenId . '/cancel',
        ];

        $this->makeRequestAndGetContent($request);
    }

    /********** Tests for as_presented **********/

    public function testCreateAsPresentedAutoRecurringPaymentSuccess()
    {
        $this->createDbUpiMandate([
            'frequency'         => 'as_presented',
            'start_time'        => Carbon::now()->getTimestamp(),
            'end_time'          => Carbon::now()->addYears(1)->getTimestamp(),
            'recurring_value'   => null,
        ]);


        $this->createDbUpiToken();

        // The request which we have sent to create the reminder
        $createReminder = null;

        $this->mockReminderService('createReminder',
            function($request, $merchantId) use (& $createReminder)
            {
                $payment = $this->getDbLastPayment();
                $metadata = $payment->getUpiMetadata();

                $createReminder = $request;

                $this->assertArraySubset([
                    'internal_status'   => 'reminder_pending_for_pre_debit',
                    'reminder_id'       => null,
                    'remind_at'         => $createReminder['reminder_data']['remind_at']
                ], $metadata->toArray(), true);

            });

        $response = $this->doS2SRecurringPayment($this->getDbUpiAutoRecurringPayment());

        $this->assertArrayHasKey('razorpay_payment_id', $response);

        $payment = $this->getDbLastPayment();

        // Now we can assert the request to RS
        $this->assertArraySubset([
            'namespace'     => 'upi_auto_recurring',
            'entity_id'     => $payment->getId(),
            'entity_type'   => 'payment',
            'callback_url'  => 'reminders/send/test/payment/upi_auto_recurring/' . $payment->getId(),
        ], $createReminder, true);

        $this->assertArraySubset([
            'terminal_id'   => '1000SharpTrmnl',
            'status'        => 'created',
            'verify_at'     => null,
        ], $payment->toArray(), true);

        $metadata = $payment->getUpiMetadata();

        $this->assertArraySubset([
            'type'              => 'recurring',
            'flow'              => 'collect',
            'mode'              => 'auto',
            'reference'         => null,
            'rrn'               => null,
            'umn'               => null,
            'npci_txn_id'       => null,
            'internal_status'   => 'reminder_in_progress_for_pre_debit',
            'reminder_id'       => 'TestReminderId',
            'remind_at'         => $createReminder['reminder_data']['remind_at']
        ], $metadata->toArray(), true);

        $this->assertUpiDbLastEntity('upi_mandate', [
            'used_count'        => 2,
            'frequency'         => 'as_presented',
            'sequence_number'   => 2
        ]);

        // Update reminder call to RS
        $updateReminder = null;

        // In the call from reminder service, an updateReminder function will be called
        $this->mockReminderService('updateReminder',
            function($request, $merchantId) use (& $updateReminder, $createReminder, $metadata)
            {
                $updateReminder = $request;
                $metadata->refresh();

                $this->assertArraySubset([
                    'internal_status'   => 'pre_debit_initiated',
                    'remind_at'         => $createReminder['reminder_data']['remind_at']
                ], $metadata->toArray(), true);
                $createRemindAt = $createReminder['reminder_data']['remind_at'];
                $updateRemindAt = $updateReminder['reminder_data']['remind_at'];


                $this->assertTrue(($createRemindAt + 90) <= $updateRemindAt);
            });

        Carbon::setTestNow(Carbon::now()->addSeconds(90));

        // Now we will initiate the reminder
        $this->sendReminderRequest($createReminder);

        $metadata->refresh();

        $this->assertArraySubset([
            'type'              => 'recurring',
            'flow'              => 'collect',
            'mode'              => 'auto',
            'reference'         => 'preDebitReference:1',
            'rrn'               => null,
            'umn'               => $this->upiMandate->umn,
            'npci_txn_id'       => null,
            'internal_status'   => 'reminder_in_progress_for_authorize',
            'reminder_id'       => 'TestReminderId',
            'remind_at'         => $updateReminder['reminder_data']['remind_at']
        ], $metadata->toArray(), true);

        $this->sendReminderRequest($updateReminder);

        $payment->refresh();

        $this->assertArraySubset([
            'terminal_id'   => '1000SharpTrmnl',
            'status'        => 'captured',
            'verify_at'     => null,
            'refund_at'     => null,
            'reference16'   => '001000100001',
            'vpa'           => 'localuser@icici',
        ], $payment->toArray(), true);

        $metadata->refresh();

        $this->assertArraySubset([
            'type'              => 'recurring',
            'flow'              => 'collect',
            'mode'              => 'auto',
            'reference'         => 'DebitReference:1',
            'rrn'               => '001000100001',
            'umn'               => $this->upiMandate->umn,
            'npci_txn_id'       => 'npci_txn_id_for_' . $payment->getId(),
            'internal_status'   => 'authorized',
            'reminder_id'       => 'TestReminderId',
            'remind_at'         => null,
        ], $metadata->toArray(), true);
    }

    public function testCreateAsPresentedAutoRecurringPaymentNotifyFailsTwice()
    {
        $this->createDbUpiMandate([
            'frequency'         => 'as_presented',
            'start_time'        => Carbon::now()->getTimestamp(),
            'end_time'          => Carbon::now()->addYears(1)->getTimestamp(),
            'recurring_value'   => null,
        ]);

        $this->createDbUpiToken();

        $input = $this->getDbUpiAutoRecurringPayment([
            'description' => 'notify_fails_twice',
        ]);

        // The request which we have sent to create the reminder
        $this->assertReminderRequest('createReminder', $createReminder, $pending);

        $this->doS2SRecurringPayment($input);

        $this->assertUpiMetadataStatus('reminder_pending_for_pre_debit', $pending);
        // Now waiting for first reminder from RS
        $this->assertUpiMetadataStatus('reminder_in_progress_for_pre_debit');
        // The first reminder call will trigger an update reminder
        $this->assertReminderRequest('updateReminder', $updateReminder, $pending);
        // Making first call from RS
        $this->sendReminderRequest($createReminder);

        $this->assertUpiMetadataStatus('pre_debit_initiated', $pending);
        // Because of first failure status moved back to reminder in progress
        $this->assertUpiMetadataStatus('reminder_in_progress_for_pre_debit');
        // Now making second call from RS
        $this->sendReminderRequest($updateReminder);

        $this->assertUpiMetadataStatus('pre_debit_initiated', $pending);
        // Because of Second failure status moved back to reminder pending
        $this->assertUpiMetadataStatus('reminder_in_progress_for_pre_debit');

        $this->assertUpiDbLastEntity('upi_metadata', [
            'reference'  => 'preDebitReference:2',
        ]);

        // Third attempt for recurring
        $this->sendReminderRequest($updateReminder);
        // Because of Second failure status moved back to reminder pending
        $this->assertUpiMetadataStatus('reminder_in_progress_for_authorize');

        // Mandate is still showing two used count
        $this->assertUpiDbLastEntity('upi_mandate', [
            'used_count'        => 2,
            'frequency'         => 'as_presented',
            'sequence_number'   => 2
        ]);
    }

    /**
     * returns a mock response of the razorx request
     *
     * @param string $inputFeature
     * @param string $expectedFeature
     * @param string $variant
     * @return string
     */
    protected function getRazoxVariant(string $inputFeature, string $expectedFeature, string $variant): string
    {
        if ($expectedFeature === $inputFeature)
        {
            return $variant;
        }

        return 'control';
    }

    /**
     * sets the razox mock
     *
     * @param [type] $closure
     * @return void
     */
    protected function setRazorxMock($closure)
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
            ->method('getTreatment')
            ->will($this->returnCallback($closure));
    }
}
