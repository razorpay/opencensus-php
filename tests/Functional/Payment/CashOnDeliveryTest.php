<?php


namespace Functional\Payment;

use Mockery;
use RZP\Models\Payment;
use RZP\Models\Transaction;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Exception\BadRequestException;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class CashOnDeliveryTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;


    const  SECONDS_IN_DAY = 24 * 60 * 60;
    protected $order;

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/CashOnDeliveryTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->fixtures->merchant->enableCoD();


        $this->fixtures->pricing->create([
            'plan_id'        => 'DefaltCodRleId',
            'payment_method' => 'cod',
        ]);
    }


    public function testInitiatePayment()
    {
        $paymentCreateResponse = $this->initiatePayment();

        $paymentId = $paymentCreateResponse['razorpay_payment_id'];

        $payment = $this->getLastPayment(true);

        $orderId = $paymentCreateResponse['razorpay_order_id'];

        $order = $this->getLastEntity('order', true);

        $expectedPaymentData = [
            'id'                     => $paymentId,
            'status'                 => 'pending',
            'verify_at'              => null,
            'amount_authorized'      => 0,
            'settled_by'             => 'delivery_partner',
            'merchant_id'            => '10000000000000',
            'amount'                 => 50000,
            'currency'               => 'INR',
            'base_amount'            => 50000,
            'method'                 => 'cod',
            'order_id'               => $orderId,
            'description'            => 'random description',
            'email'                  => 'a@b.com',
            'contact'                => '+919918899029',
            'notes'                  => [
                'merchant_order_id' => 'random order id',
            ],
            'fee'                    => null,
            'mdr'                    => null,
            'error_code'             => null,
            'terminal_id'            => null,
            'gateway'                => null,
            'authentication_gateway' => null,
            'fee_bearer'             => 'platform',
            //not setting to t+45 here because refund will anyway fail for cod payment. instead payment fail scheduler
            // flow will be modified to fail the payment after t+45 if its still in pending status
            'refund_at'              => null,
            'captured'               => false,
        ];

        $expectedOrderData = [
            'id'          => $orderId,
            'merchant_id' => '10000000000000',
            'amount'      => 50000,
            'amount_paid' => 0,
            'attempts'    => 1,
            'status'      => 'placed',
        ];

        $this->assertArraySelectiveEquals($expectedPaymentData, $payment);

        $this->assertArraySelectiveEquals($expectedOrderData, $order);
    }

    public function testPaymentPendingWebhook()
    {
        $this->expectWebhookEventWithContents('payment.pending', 'testPaymentPendingWebhookEventData');

        $this->initiatePayment();
    }


    protected function initiatePayment()
    {
        $request = $this->getPaymentCreateRequest();

        $response = $this->makeRequestParent($request)->json();

        $this->assertEquals($this->order->getPublicId(), $response['razorpay_order_id']);

        return $response;
    }

    protected function getPaymentCreateRequest(): array
    {
        $this->setupOrderIfApplicable();

        return [
            'method'  => 'POST',
            'content' => $this->getDefaultCoDPaymentArray([
                'order_id' => $this->order->getPublicId(),
                'capture'  => 1,
            ]),
            'url'     => '/payments',
        ];
    }

    protected function setupOrderIfApplicable(): void
    {
        if ($this->order === null)
        {
            $this->order = $this->fixtures->create('order', [
                'amount' => '50000',
            ]);
        }
    }

    protected function getDefaultCoDPaymentArray($attributes): array
    {
        $defaults = $this->getDefaultPaymentArrayNeutral();

        $defaults['method'] = 'cod';

        return array_merge($attributes, $defaults);
    }

    public function testInitiatePaymentRemindersCallbackSetup()
    {

        $remindersMock = $this->setUpRemindersMock();

        $remindersRequest = [];

        $remindersMock->shouldReceive('createReminder')
            ->andReturnUsing(function ($request, $merchantId) use (&$remindersRequest)
            {

                $remindersRequest = $request;

                return [
                    'id' => UniqueIdEntity::generateUniqueId(),
                ];
            });

        $paymentId = $this->initiatePayment()['razorpay_payment_id'];

        Payment\Entity::verifyIdAndStripSign($paymentId);

        $payment = $this->getLastPayment(true);

        $this->assertArraySelectiveEquals([
            'namespace'     => 'cod_payment_pending',
            'callback_url'  => 'reminders/send/test/payment/cod_payment_pending/' . $paymentId,
            'reminder_data' => [
                'created_at' => $payment['created_at'],
            ],
        ], $remindersRequest);
    }

    protected function setUpRemindersMock()
    {
        $mock = Mockery::mock('RZP\Services\Reminders', [$this->app])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->app['reminders'] = $mock;

        return $mock;
    }

    public function testInitiatePaymentWithMethodDisabledShouldFail()
    {
        $this->fixtures->merchant->disableCoD();

        $this->startTest([
            'request'   => $this->getPaymentCreateRequest(),
            'response'  => $this->getMethodNotEnabledResponse(),
            'exception' => $this->getMethodNotEnabledException(),
        ]);
    }

    protected function getMethodNotEnabledResponse(): array
    {
        return [
            'content'     => [
                'error' => [
                    'description' => 'Cash on delivery transactions are not supported for the merchant.',
                ],
            ],
            'status_code' => 400,
        ];
    }

    protected function getMethodNotEnabledException(): array
    {
        return [
            'class'               => BadRequestException::class,
            'internal_error_code' => 'BAD_REQUEST_PAYMENT_COD_NOT_ENABLED_FOR_MERCHANT',
        ];
    }

    public function testInitiatePaymentNonRZPOrgShouldFail()
    {
        $this->fixtures->merchant->edit('10000000000000', [
            'org_id' => 'SBINbankOrgnId',
        ]);


        $this->startTest([
            'request'   => $this->getPaymentCreateRequest(),
            'response'  => $this->getMethodNotEnabledResponse(),
            'exception' => $this->getMethodNotEnabledException(),
        ]);

        $this->assertArraySelectiveEquals([
            'status' => 'created',
        ], $this->getLastEntity('order', true));
    }

    public function testInitiatePaymentNonPlatformFeeBearerShouldFail()
    {
        $this->fixtures->merchant->edit('10000000000000', [
            'fee_bearer' => 'customer',
        ]);

        $this->startTest([
            'request'   => $this->getPaymentCreateRequest(),
            'response'  => $this->getMethodNotEnabledResponse(),
            'exception' => $this->getMethodNotEnabledException(),
        ]);
    }

    public function testInitiatePaymentWithoutOrderShouldFail()
    {
        $request = $this->getPaymentCreateRequest();

        unset($request['content']['order_id']);

        $this->startTest(['request' => $request]);
    }

    public function testInitiatePaymentNonInr()
    {
        $order = $this->order = $this->fixtures->create('order', [
            'amount'   => '50000',
            'currency' => 'USD',
        ]);

        $this->fixtures->merchant->edit('10000000000000', [
            'convert_currency' => 1,
        ]);

        $request = $this->getPaymentCreateRequest();

        $request['content']['currency'] = 'USD';

        $request['content']['order_id'] = $order->getPublicId();

        $this->makeRequestParent($request)->json();

        $order = $this->getEntityById('order', $order->getPublicId(), true);

        $payment = $this->getLastPayment(true);


        $this->assertArraySelectiveEquals([
            'amount'      => 50000,
            'currency'    => 'USD',
            'amount_paid' => 0,
        ], $order);

        $this->assertArraySelectiveEquals([
            'amount'      => 50000,
            'base_amount' => 500000,
        ], $payment);
    }

    public function testInitiatePaymentWithNonTerminalOrderStatus()
    {
        foreach (['attempted', 'pending', 'created'] as $existingOrderStatus)
        {
            $this->order = $this->fixtures->create('order', [
                'status' => $existingOrderStatus,
                'amount' => 50000,
            ]);

            $this->ba->publicAuth();

            $this->initiatePayment();

            $order = $this->getEntityById('order', $this->order->getPublicId(), true);

            $payment = $this->getLastPayment(true);

            $this->assertEquals('placed', $order['status']);

            $this->assertEquals('pending', $payment['status']);
        }
    }

    public function testInitiateNonCodPaymentWithOrderInPlacedStatus()
    {
        $this->order = $this->fixtures->create('order', [
            'status' => 'placed',
            'amount' => 50000,
        ]);

        $this->initiateNonCoDPayment();

        $order = $this->getLastEntity('order', true);

        $this->assertEquals('attempted', $order['status']);
    }

    protected function initiateNonCoDPayment()
    {
        $this->setupOrderIfApplicable();

        $payment = $this->getDefaultPaymentArray();

        $payment['order_id'] = $this->order->getPublicId();

        return $this->doAuthPayment($payment);
    }

    public function testInitiateCoDPaymentWithOrderInTerminalStatus()
    {
        $this->order = $this->fixtures->create('order', [
            'status' => 'paid',
            'amount' => 50000,
        ]);

        $this->startTest(['request' => $this->getPaymentCreateRequest()]);
    }


    public function testCapturePayment()
    {
        $paymentId = $this->initiatePayment()['razorpay_payment_id'];

        $this->capturePayment($paymentId, 50000);

        $transaction = $this->getLastEntity('transaction', true);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $order = $this->getEntityById('order', $this->order->getPublicId(), true);

        $payment = $this->getEntityById('payment', $paymentId, true);


        $transactionId = $transaction['id'];

        Transaction\Entity::verifyIdAndStripSign($transactionId);

        $paymentFeeBreakup = $this->getDbEntities('fee_breakup', [
            'transaction_id' => $transactionId,
            'name'           => 'payment',
        ])->first()->toArray();

        $taxFeeBreakup = $this->getDbEntities('fee_breakup', [
            'transaction_id' => $transactionId,
            'name'           => 'tax',
        ])->first()->toArray();

        $this->assertArraySelectiveEquals([
            'entity_id'       => $paymentId,
            'type'            => 'payment',
            'merchant_id'     => '10000000000000',
            'amount'          => 50000,
            'fee'             => 0,
            'mdr'             => 0,
            'tax'             => 0,
            'pricing_rule_id' => null,
            'debit'           => 0,
            'credit'          => 0,
            'currency'        => 'INR',
            'balance'         => 1000000,
            'gateway_amount'  => null,
            'gateway_fee'     => 0,
            'fee_bearer'      => 'platform',
        ], $transaction);


        $this->assertArraySelectiveEquals([
            'balance' => 1000000,
        ], $balance);

        $this->assertArraySelectiveEquals([
            'status'      => 'paid',
            'amount_paid' => 50000,
        ], $order);

        $this->assertArraySelectiveEquals([
            'status'                 => 'captured',
            'verify_at'              => null,
            'amount_authorized'      => 0,
            'settled_by'             => 'delivery_partner',
            'fee'                    => 0,
            'mdr'                    => 0,
            'terminal_id'            => null,
            'gateway'                => null,
            'authentication_gateway' => null,
            'fee_bearer'             => 'platform',
            //not setting to t+45 here because refund will anyway fail for cod payment. instead payment fail scheduler
            // flow will be modified to fail the payment after t+45 if its still in pending status
            'refund_at'              => null,
            'captured'               => true,
        ], $payment);

        $this->assertArraySelectiveEquals([
            'amount'     => 0,
            'percentage' => NULL,
        ], $paymentFeeBreakup);

        $this->assertArraySelectiveEquals([
            'amount'     => 0,
            'percentage' => 1800,
        ], $taxFeeBreakup);

    }

    public function testCaptureNonCodPaymentWhenOrderInPlacedStatus()
    {
        $paymentId = $this->initiateNonCoDPayment()['razorpay_payment_id'];

        $this->fixtures->edit('order', $this->order->getId(), [
            'status' => 'placed',
        ]);

        $order = $this->getLastEntity('order', true);

        $this->assertEquals('placed', $order['status']);

        $this->capturePayment($paymentId, 50000);

        $order = $this->getLastEntity('order', true);

        $this->assertEquals('paid', $order['status']);
    }

    public function testCaptureCoDPaymentWhenOrderInAttemptedStatus()
    {
        $codPaymentId = $this->initiatePayment()['razorpay_payment_id'];

        $this->initiateNonCoDPayment();

        $order = $this->getLastEntity('order', true);

        $this->assertEquals('attempted', $order['status']);

        $this->capturePayment($codPaymentId, 50000);

        $order = $this->getLastEntity('order', true);

        $this->assertArraySelectiveEquals([
            'status' => 'paid',
        ], $order);
    }

    public function testRefundPaymentShouldFail()
    {
        $paymentId = $this->initiatePayment()['razorpay_payment_id'];

        $this->capturePayment($paymentId, 50000);

        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage('Refund is currently not supported for this payment method');

        $this->refundPayment($paymentId, 50000);
    }


    public function testCaptureCoDPaymentInNonPendingStatusShouldFail()
    {
        $paymentId = $this->initiatePayment()['razorpay_payment_id'];

        Payment\Entity::verifyIdAndStripSign($paymentId);

        $this->fixtures->edit('payment', $paymentId, [
            'status' => 'created' // not a valid transition, to be used for testing only
        ]);

        $this->ba->privateAuth();

        $this->startTest([
            'request' => [
                'url' => '/payments/pay_' . $paymentId . '/capture',
            ],
        ]);
    }

    public function testPaymentTimeout()
    {
        $minute = 24 * 60 * 60;

        $eligiblePayment = $this->fixtures->create('payment', ['created_at' => time() - 15 * 60, 'method' => 'cod']);

        $inEligiblePayment = $this->fixtures->create('payment', ['created_at' => time() - 10 * 60, 'method' => 'cod']);

        $this->ba->cronAuth();

        $this->startTest();

        $eligiblePayment->reload();

        $inEligiblePayment->reload();

        $this->assertEquals('failed', $eligiblePayment['status']);

        $this->assertEquals('created', $inEligiblePayment['status']);
    }


    /**
     * Usecase for the next 4 tests: fail any cod payment in pending status for more than 45days
     */
    public function testReminderCallbackForPendingPaymentBefore45Days()
    {
        $payment = $this->fixtures->create('payment', [
            'id'         => 'randmPaymentId',
            'method'     => 'cod',
            'status'     => 'pending',
            'created_at' => time() - 30 * self::SECONDS_IN_DAY,
        ]);

        $this->ba->reminderAppAuth();

        $this->startTest();

        $payment->reload();

        $this->assertEquals('pending', $payment['status']);
    }

    public function testReminderCallbackForPendingPaymentAfter45Days()
    {
        $payment = $this->fixtures->create('payment', [
            'id'         => 'randmPaymentId',
            'method'     => 'cod',
            'status'     => 'pending',
            'created_at' => time() - 50 * self::SECONDS_IN_DAY,
        ]);

        $this->ba->reminderAppAuth();

        $this->startTest($this->testData['testReminderCallbackStopScheduleTestData']);

        $payment->reload();

        $this->assertEquals('failed', $payment['status']);
    }

    public function testReminderCallbackForNonPendingPaymentBefore45Days()
    {
        $payment = $this->fixtures->create('payment', [
            'id'         => 'randmPaymentId',
            'method'     => 'cod',
            'status'     => 'captured',
            'created_at' => time() - 30 * self::SECONDS_IN_DAY,
        ]);

        $this->ba->reminderAppAuth();

        $this->startTest($this->testData['testReminderCallbackStopScheduleTestData']);

        $payment->reload();

        $this->assertEquals('captured', $payment['status']);
    }

    public function testReminderCallbackForNonPendingPaymentAfter45Days()
    {
        $payment = $this->fixtures->create('payment', [
            'id'         => 'randmPaymentId',
            'method'     => 'cod',
            'status'     => 'captured',
            'created_at' => time() - 50 * self::SECONDS_IN_DAY,
        ]);

        $this->ba->reminderAppAuth();

        $this->startTest($this->testData['testReminderCallbackStopScheduleTestData']);

        $payment->reload();

        $this->assertEquals('captured', $payment['status']);
    }
}