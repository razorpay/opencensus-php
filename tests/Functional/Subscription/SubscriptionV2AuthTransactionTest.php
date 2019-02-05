<?php

namespace RZP\Tests\Functional\Subscription;

use Carbon\Carbon;
use Requests_Session;
use Requests_Response;
use Requests_Exception;
use Illuminate\Support\Facades\Queue;

use RZP\Modules\Subscriptions;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Jobs\SubscriptionPaymentHandler;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Subscription\SubscriptionTrait;

class SubscriptionV2AuthTransactionTest extends TestCase
{
    use PaymentTrait;
    use SubscriptionTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        // This is set to 10 Jan 2018
        // Because in test cases subsription start date is set
        // to 20 Jan 2018 and it should always be in future
        Carbon::setTestNow("10-1-2018 3:00:00");

        $this->testDataFilePath = __DIR__ . '/Helpers/SubscriptionTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['subscriptions', 'subscription_auth_v2']);

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $this->mockCardVault();
    }

    public function testSubscriptionV2AuthTxnNormalWithStartAt()
    {
        $subscription = $this->createSubscription(true);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        $requestMock = $this->createMock(Requests_Session::class);

        $subscription = $this->getDbLastEntity('subscription');
        $subscription->setGlobalCustomer(false);

        $mockSuccessResponse = new Requests_Response;

        $mockSuccessResponse->status_code = 200;
        $mockSuccessResponse->success = true;
        $mockSuccessResponse->body = json_encode($subscription->attributesToArray());

        $requestMock = $this->createMock(Requests_Session::class);

        $requestMock->expects($this->exactly(2))->method('request')->will($this->returnValue($mockSuccessResponse));

        $this->registerMockedClient($requestMock);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription->toArrayPublic());

        Queue::fake();

        $response = $this->doAuthPayment($paymentRequest);

        $actualSignature = $response['razorpay_signature'];

        $signatureData = [
            'subscription_id'     => $subscription->getPublicId(),
            'razorpay_payment_id' => $response['razorpay_payment_id'],
        ];

        ksort($signatureData);
        $exceptedSignature = $this->getSignature($signatureData, 'TheKeySecretForTests');

        $this->assertEquals($exceptedSignature, $actualSignature);

        $payment = $this->getDbLastEntityPublic('payment');
        $invoice = $this->getDbLastEntityPublic('invoice');
        $order = $this->getDbLastEntityPublic('order');

        $this->assertEmpty($invoice);
        $this->assertEmpty($order);
        $this->assertEquals($subscription->getPublicId(), $payment['subscription_id']);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals(500, $payment['amount']);

        Queue::assertPushed(SubscriptionPaymentHandler::class, function ($job) use ($payment)
        {
            $this->assertEquals($payment['id'], $job->getPaymentData()['id']);

            return true;
        });
    }

    public function testSubscriptionV2AuthTxnNormalWithoutStartAt()
    {
        $subscription = $this->createSubscription(false);

        $oldScheduleTask = $this->getDbLastEntityPublic('schedule_task');

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 2000);

        $subscription = $this->getDbLastEntity('subscription');
        $subscription->setGlobalCustomer(false);

        $mockSuccessResponse = new Requests_Response;

        $mockSuccessResponse->status_code = 200;
        $mockSuccessResponse->success = true;
        $mockSuccessResponse->body = json_encode($subscription->attributesToArray());

        $requestMock = $this->createMock(Requests_Session::class);

        $requestMock->expects($this->exactly(2))->method('request')->will($this->returnValue($mockSuccessResponse));

        $this->registerMockedClient($requestMock);

        $this->doAuthPayment($paymentRequest);

        $invoice = $this->getDbLastEntityPublic('invoice');
        $order = $this->getDbLastEntityPublic('order');
        $subscription = $this->getDbLastEntityPublic('subscription');
        $token = $this->getDbLastEntityPublic('token');
        $payment = $this->getDbLastEntityPublic('payment');
        $refund = $this->getDbLastEntityPublic('refund');

        $this->assertEquals('issued', $invoice['status']);
        $this->assertEquals($subscription['id'], $invoice['subscription_id']);
        $this->assertEquals($order['id'], $invoice['order_id']);
        $this->assertEquals('created', $order['status']);
        $this->assertNotNull($token);
        $this->assertEmpty($refund);
    }

    public function testSubscriptionV2AuthTxnAddonWithStartAt()
    {
        $subscription = $this->createSubscription(true, [], [], true);

        $oldScheduleTask = $this->getLastEntity('schedule_task', true);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 300);

        $subscription = $this->getDbLastEntity('subscription');
        $subscription->setGlobalCustomer(false);

        $mockSuccessResponse = new Requests_Response;

        $mockSuccessResponse->status_code = 200;
        $mockSuccessResponse->success = true;
        $mockSuccessResponse->body = json_encode($subscription->attributesToArray());

        $requestMock = $this->createMock(Requests_Session::class);

        $requestMock->expects($this->exactly(2))->method('request')->will($this->returnValue($mockSuccessResponse));

        $this->registerMockedClient($requestMock);

        $this->doAuthPayment($paymentRequest);

        $invoice = $this->getLastEntity('invoice', true);
        $order = $this->getDbLastEntityPublic('order');
        $subscription = $this->getDbLastEntityPublic('subscription');
        $token = $this->getDbLastEntityPublic('token');
        $payment = $this->getDbLastEntityPublic('payment');
        $refund = $this->getDbLastEntityPublic('refund');

        $this->assertEquals('issued', $invoice['status']);
        $this->assertEquals($subscription['id'], $invoice['subscription_id']);
        $this->assertEquals($order['id'], $invoice['order_id']);
        $this->assertEquals('created', $order['status']);
        $this->assertEquals(300, $order['amount']);
        $this->assertNotNull($token);
        $this->assertEmpty($refund);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('cust_100000customer', $payment['customer_id']);
        $this->assertTrue($payment['recurring']);
        $this->assertNull($payment['global_customer_id']);
    }

    public function testSubscriptionV2AuthTxnAutoCaptureAddonWithoutStartAt()
    {
        $subscription = $this->createSubscription(false, [], [], true);

        $oldScheduleTask = $this->getDbLastEntityPublic('schedule_task');

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 2300);

        $subscription = $this->getDbLastEntity('subscription');
        $subscription->setGlobalCustomer(false);

        $mockSuccessResponse = new Requests_Response;

        $mockSuccessResponse->status_code = 200;
        $mockSuccessResponse->success = true;
        $mockSuccessResponse->body = json_encode($subscription->attributesToArray());

        $requestMock = $this->createMock(Requests_Session::class);

        $requestMock->expects($this->exactly(2))->method('request')->will($this->returnValue($mockSuccessResponse));

        $this->registerMockedClient($requestMock);

        $this->doAuthPayment($paymentRequest);

        $invoice = $this->getLastEntity('invoice', true);
        $order = $this->getDbLastEntityPublic('order');
        $subscription = $this->getDbLastEntityPublic('subscription');
        $token = $this->getDbLastEntityPublic('token');
        $payment = $this->getDbLastEntityPublic('payment');
        $refund = $this->getDbLastEntityPublic('refund');

        $this->assertEquals('issued', $invoice['status']);
        $this->assertEquals($subscription['id'], $invoice['subscription_id']);
        $this->assertEquals($order['id'], $invoice['order_id']);
        $this->assertEquals('created', $order['status']);
        $this->assertEquals(2300, $order['amount']);
        $this->assertNotNull($token);
        $this->assertEmpty($refund);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals('cust_100000customer', $payment['customer_id']);
        $this->assertTrue($payment['recurring']);
        $this->assertNull($payment['global_customer_id']);
    }

    public function testSubscriptionV2AuthTxnWithRecurringFalse()
    {
        $subscription = $this->createSubscription(true);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);
        unset($paymentRequest['recurring']);

        $subscription = $this->getDbLastEntity('subscription');
        $subscription->setGlobalCustomer(false);

        $mockSuccessResponse = new Requests_Response;

        $mockSuccessResponse->status_code = 200;
        $mockSuccessResponse->success = true;
        $mockSuccessResponse->body = json_encode($subscription->attributesToArray());

        $requestMock = $this->createMock(Requests_Session::class);

        $requestMock->expects($this->exactly(2))->method('request')->will($this->returnValue($mockSuccessResponse));

        $this->registerMockedClient($requestMock);

        // Basically, it should not throw any exception even if recurring flag is not set
        $this->doAuthPayment($paymentRequest);
    }

    public function testSubscriptionV2AuthTxnWithWrongAmount()
    {
        $subscription = $this->createSubscription(true);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 123);

        $subscription = $this->getDbLastEntity('subscription');

        $mockErrorResponse = new Requests_Response;

        $mockErrorResponse->status_code = 400;
        $mockErrorResponse->success = false;
        $errorResponse = [
            'error' => [
                'internal_error_code' => 'BAD_REQUEST_INVALID_TRANSACTION_AMOUNT',
                'code'                => 'BAD_REQUEST_ERROR',
                'description'         => 'The amount does not match with the expected amount for the transaction. It might have been tampered.',
            ]
        ];

        $mockErrorResponse->body = json_encode($errorResponse);

        $requestMock = $this->createMock(Requests_Session::class);

        $requestMock->expects($this->exactly(1))->method('request')->will($this->returnValue($mockErrorResponse));

        $this->registerMockedClient($requestMock);

        $this->makeRequestAndCatchException(function () use ($paymentRequest)
        {
            $this->doAuthPayment($paymentRequest);
        }, BadRequestException::class, 'The amount does not match with the expected amount for the transaction. It might have been tampered.');
    }

    public function testSubscriptionV2AuthTxnWithRequestTimeoutError()
    {
        $subscription = $this->createSubscription(true);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        $requestMock = $this->createMock(Requests_Session::class);

        $requestTimeoutException = new Requests_Exception('connection timed out', '502');

        $requestMock->method('request')->will($this->throwException($requestTimeoutException));

        $this->app['module']->extend('subscription', function () use ($requestMock)
        {
            $mockedSubsriptionExternal = new Subscriptions\Mock\External($requestMock);

            return $mockedSubsriptionExternal;
        });

        $subscription = $this->getDbLastEntity('subscription');

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription->toArrayPublic());

        $this->makeRequestAndCatchException(function () use ($paymentRequest)
        {
            $this->doAuthPayment($paymentRequest);
        }, ServerErrorException::class, 'connection timed out');

        $payment = $this->getDbLastEntityPublic('payment');

        $this->assertEmpty($payment);
    }

    public function testSubscriptionV2AuthTxnWithSubServServerError()
    {
        $subscription = $this->createSubscription(true);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        $requestMock = $this->createMock(Requests_Session::class);

        $mockResponse = new Requests_Response;

        $mockResponse->status_code = 500;
        $mockResponse->success = false;
        $errorDetails = [
            'description' => 'something went wrong'
        ];
        $mockResponse->body = json_encode([
            'error' => $errorDetails,
        ]);

        $requestMock->method('request')->will($this->returnValue($mockResponse));

        $this->app['module']->extend('subscription', function () use ($requestMock)
        {
            $mockedSubsriptionExternal = new Subscriptions\Mock\External($requestMock);

            return $mockedSubsriptionExternal;
        });

        $subscription = $this->getDbLastEntity('subscription');

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription->toArrayPublic());

        $this->makeRequestAndCatchException(function () use ($paymentRequest)
        {
            $this->doAuthPayment($paymentRequest);
        }, ServerErrorException::class, 'something went wrong');

        $payment = $this->getDbLastEntityPublic('payment');

        $this->assertEmpty($payment);
    }

    protected function registerMockedClient($requestMock)
    {
        $this->app['module']->extend('subscription', function () use ($requestMock)
        {
            $mockedSubsriptionExternal = new Subscriptions\Mock\External($requestMock);

            return $mockedSubsriptionExternal;
        });
    }
}
