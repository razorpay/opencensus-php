<?php

namespace RZP\Tests\Functional\CardMandate;

use Mockery;

use Queue;
use Carbon\Carbon;
use RZP\Constants\Entity;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Entity as E;
use RZP\Models\Currency\Currency;
use RZP\Tests\Functional\TestCase;
use RZP\Models\CardMandate\Status;
use RZP\Exception\BadRequestException;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\Payment\Entity as Payment;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class CardMandateTest extends TestCase
{
    use WebhookTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;
    use TestsWebhookEvents;

    /**
     * @var array
     */
    protected $paymentInput;

    protected $mandateHQ;

    protected $mandateConfirm;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/CardMandateTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->fixtures->merchant->addFeatures(['charge_at_will']);

        $this->fixtures->merchant->addFeatures(['recurring_card_mandate']);

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('iin', [
            'iin' => '400018',
            'type' => 'credit',
            'recurring' => 1,
            'issuer' => IFSC::RATN,
        ]);

        $this->paymentInput = $this->getDefaultRecurringPaymentArray();
        $this->paymentInput['bank'] = IFSC::RATN;

        $order = $this->fixtures->create('order', [
            'amount' => 50000,
            'payment_capture' => 1,
        ]);
        $this->paymentInput['card']['number'] = '4000184186218826';
        $this->paymentInput['order_id'] = $order->getPublicId();

        $this->mandateHQ = Mockery::mock('RZP\Services\MandateHQ', [$this->app]);
        $this->app->instance('mandateHQ', $this->mandateHQ);

        $this->mandateConfirm = 'true';
    }

    public function testCreateCardMandatePayment()
    {
        $this->mockCheckBin();

        $this->mockRegisterMandate();

        $this->mockReportPayment();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->paymentInput,
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['razorpay_payment_id'] ?? null);

        $payment = $this->getDbLastEntity(E::PAYMENT);
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('initial', $payment->getRecurringType());
        $this->assertNotNull($payment->getTokenId());

        $token = $payment->localToken;
        $this->assertNotEmpty($token);
        $this->assertEquals('confirmed', $token->getRecurringStatus());

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);
        $this->assertNotEmpty($cardMandate);
        $this->assertNotEmpty($cardMandate->getMandateSummaryUrl());
        $this->assertEquals('active', $cardMandate->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandate->getMandateId());
    }

    public function testMandateHQCallbackMandatePaused()
    {
        $this->testCreateCardMandatePayment();

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);

        $this->testData[__FUNCTION__]['request']['content']['payload']['mandate']['entity']['id'] = $cardMandate->getMandateId();

        $this->mockStorkService();

        $this->mockServiceStorkRequest(
            function ($path, $payload) use (&$webhookPayload)
            {
                $webhookPayload = $payload;

                return new \Requests_Response();
            })->times(1);

        $this->startTest();

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);

        $this->assertEquals('paused', $cardMandate->getStatus());

        $token = $this->getDbLastEntity(E::TOKEN);

        $this->assertEquals('paused', $token->getRecurringStatus());

        $this->assertEquals('token.paused', $webhookPayload['event']['name']);

        $payload = json_decode($webhookPayload['event']['payload'], true);

        $this->assertEquals($token->getPublicId(), $payload['payload']['token']['entity']['id']);
    }

    public function testMandateHQCallbackMandateResumed()
    {
        $this->testMandateHQCallbackMandatePaused();

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);

        $this->testData[__FUNCTION__]['request']['content']['payload']['mandate']['entity']['id'] = $cardMandate->getMandateId();

        $this->mockStorkService();

        $this->mockServiceStorkRequest(
            function ($path, $payload) use (&$webhookPayload)
            {
                $webhookPayload = $payload;

                return new \Requests_Response();
            })->times(1);

        $this->startTest();

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);

        $this->assertEquals('active', $cardMandate->getStatus());

        $token = $this->getDbLastEntity(E::TOKEN);

        $this->assertEquals('confirmed', $token->getRecurringStatus());

        $this->assertEquals('token.confirmed', $webhookPayload['event']['name']);

        $payload = json_decode($webhookPayload['event']['payload'], true);

        $this->assertEquals($token->getPublicId(), $payload['payload']['token']['entity']['id']);
    }

    public function testMandateHQCallbackMandateCancelled()
    {
        $this->testCreateCardMandatePayment();

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);

        $this->testData[__FUNCTION__]['request']['content']['payload']['mandate']['entity']['id'] = $cardMandate->getMandateId();

        $webhookPayload = [];

        $this->mockStorkService();

        $this->mockServiceStorkRequest(
            function ($path, $payload) use (&$webhookPayload)
            {
                $webhookPayload = $payload;

                return new \Requests_Response();
            })->times(1);

        $this->startTest();

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);

        $this->assertEquals('cancelled', $cardMandate->getStatus());

        $token = $this->getDbLastEntity(E::TOKEN);

        $this->assertEquals('cancelled', $token->getRecurringStatus());

        $this->assertEquals('token.cancelled', $webhookPayload['event']['name']);

        $payload = json_decode($webhookPayload['event']['payload'], true);

        $this->assertEquals($token->getPublicId(), $payload['payload']['token']['entity']['id']);
    }

    public function testMandateHQCallbackMandateCompleted()
    {
        $this->testCreateCardMandatePayment();

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);

        $this->testData[__FUNCTION__]['request']['content']['payload']['mandate']['entity']['id'] = $cardMandate->getMandateId();

        $this->mockStorkService();

        $webhookPayload = [];

        $this->mockServiceStorkRequest(
            function ($path, $payload) use (&$webhookPayload)
            {
                $webhookPayload = $payload;

                return new \Requests_Response();
            })->times(1);

        $this->startTest();

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);

        $this->assertEquals('completed', $cardMandate->getStatus());

        $token = $this->getDbLastEntity(E::TOKEN);

        $this->assertEquals('cancelled', $token->getRecurringStatus());

        $this->assertNotEmpty($token->getExpiredAt());

        $this->assertEquals('token.cancelled', $webhookPayload['event']['name']);

        $payload = json_decode($webhookPayload['event']['payload'], true);

        $this->assertEquals($token->getPublicId(), $payload['payload']['token']['entity']['id']);
    }

    public function testCreateCardMandatePaymentViaPaymentCheckoutApi()
    {
        $this->mockCheckBin();

        $this->mockRegisterMandate();

        $this->mockReportPayment();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/checkout',
            'content' => $this->paymentInput,
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['razorpay_payment_id'] ?? null);

        $payment = $this->getDbLastEntity(E::PAYMENT);
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('initial', $payment->getRecurringType());
        $this->assertNotNull($payment->getTokenId());

        $token = $payment->localToken;
        $this->assertNotEmpty($token);
        $this->assertEquals('confirmed', $token->getRecurringStatus());

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);
        $this->assertNotEmpty($cardMandate);
        $this->assertNotEmpty($cardMandate->getMandateSummaryUrl());
        $this->assertEquals('active', $cardMandate->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandate->getMandateId());
    }

    public function testSubscriptionRegistrationInitialCardMandatePaymentAmountGreaterThanMaxAmount()
    {
        $this->mockCheckBin();

        $this->mockRegisterMandate();

        $this->mockReportPayment();

        $paymentInp = $this->paymentInput;

        $paymentInp['amount'] = 800000;

        $subr = $this->fixtures->create('subscription_registration',
            ['method' => 'card', 'max_amount' => 400000, 'expire_at' => 4091958776, 'notes' => []]);

        $order = $this->fixtures->create('order',
            ['amount' => 800000, 'payment_capture' => 1]);

        $this->fixtures->create('invoice',
            ['entity_type' => 'subscription_registration', 'entity_id' => $subr->id, 'order_id' => $order->id]);

        $paymentInp['order_id'] = $order->getPublicId();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $paymentInp,
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['razorpay_payment_id'] ?? null);

        $payment = $this->getDbLastEntity(E::PAYMENT);
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('initial', $payment->getRecurringType());
        $this->assertNotNull($payment->getTokenId());

        $token = $payment->localToken;
        $this->assertNotEmpty($token);
        $this->assertEquals('confirmed', $token->getRecurringStatus());
        $this->assertEquals($subr['max_amount'], $token->getMaxAmount());
        $this->assertEquals($subr['expire_at'], $token->getExpiredAt());

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);
        $this->assertNotEmpty($cardMandate);
        $this->assertNotEmpty($cardMandate->getMandateSummaryUrl());
        $this->assertEquals('active', $cardMandate->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandate->getMandateId());
    }

    public function testCreateCardMandatePaymentWithFailedToken()
    {
        $this->mandateConfirm = 'false';

        $this->mockCheckBin();

        $this->mockRegisterMandate();

        $this->mockReportPayment();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->paymentInput,
        ];

        try {
            $this->makeRequestAndGetContent($request);
        } catch (\Exception $e)
        {}
        $payment = $this->getDbLastEntity(E::PAYMENT);

        $token = $payment->localToken;

        unset($request['content']['card']);
        $request['content']['token'] = $token->getPublicId();

        $this->mandateConfirm = 'true';

        $this->makeRequestAndGetContent($request);
        $payment = $this->getDbLastEntity(E::PAYMENT);
        $this->assertEquals('captured', $payment->getStatus());
        $this->assertEquals('initial', $payment->getRecurringType());
        $this->assertNotNull($payment->getTokenId());
        $this->assertNotEquals($payment->getTokenId(), $token->getId());
    }

    public function testCreateCardMandateForUSDCurrencyPayment()
    {
        $this->mockCheckBin();

        $this->mockRegisterMandate();

        $this->mockReportPayment();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->paymentInput,
        ];

        $request['content']['currency'] = Currency::USD;

        $order = $this->fixtures->create('order', [
            'amount' => 50000,
            'payment_capture' => 1,
            'currency' => Currency::USD,
        ]);
        $request['content'][Payment::ORDER_ID] = $order->getPublicId();

        try
        {
            $this->makeRequestAndGetContent($request);
        }
        catch (BadRequestException $e)
        {
            $exception = true;
            $this->assertEquals('BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED', $e->getCode());
            $this->assertEquals('Currency is not supported', $e->getMessage());
        }

        $this->assertTrue($exception);
    }

    public function testCreateCardMandatePaymentForMandateCancelledByCustomer()
    {
        $this->mockCheckBin();

        $this->mockRegisterMandate();

        $this->mandateConfirm = 'false';

        $this->mockReportPayment();

        $exception = false;
        try
        {
            $request = [
                'method'  => 'POST',
                'url'     => '/payments/create/ajax',
                'content' => $this->paymentInput,
            ];

            $this->makeRequestAndGetContent($request);
        }
        catch (BadRequestException $e)
        {
            $exception = true;
            $this->assertEquals('BAD_REQUEST_CARD_MANDATE_CANCELLED_BY_USER', $e->getCode());
            $this->assertEquals('Card mandate created for payment has been cancelled by user', $e->getMessage());
        }

        $this->assertTrue($exception);

        $payment = $this->getDbLastEntity(E::PAYMENT);
        $this->assertEquals('failed', $payment->getStatus());
        $this->assertEquals('BAD_REQUEST_CARD_MANDATE_CANCELLED_BY_USER', $payment->internal_error_code);
        $this->assertEquals('Card mandate created for payment has been cancelled by user', $payment->error_description);

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);
        $this->assertNotEmpty($cardMandate);
        $this->assertEquals('mandate_cancelled', $cardMandate->getStatus());
    }

    public function testCardMandateTokenDelete()
    {
        $this->testCreateCardMandatePayment();

        $token = $this->getDbLastEntity(Entity::TOKEN);

        $url = $this->testData[__FUNCTION__]['request']['url'];
        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, $token->getPublicId());

        $this->ba->proxyAuth();

        $this->mockCancelMandate();

        $this->startTest();

        $cardMandate = $this->getDbLastEntity(Entity::CARD_MANDATE);

        $this->assertEquals('cancelled', $cardMandate->getStatus());
    }

    public function testCreateCardMandateAutoPayment()
    {
        $this->testCreateCardMandatePayment();

        $this->mockCreatePreDebitNotification();

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $paymentInput = $this->getDefaultRecurringPaymentArray();
        unset($paymentInput[Payment::CARD]);
        unset($paymentInput[Payment::BANK]);

        $paymentInput[Payment::TOKEN] = $tokenId;

        $order = $this->fixtures->create('order', [
            'amount' => 50000,
            'payment_capture' => 1,
        ]);
        $paymentInput[Payment::ORDER_ID] = $order->getPublicId();

        $this->ba->privateAuth();

        $content = $this->doS2SRecurringPayment($paymentInput);
        $this->assertNotEmpty($content['razorpay_payment_id']);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('auto', $payment->getRecurringType());
        $this->assertEquals('created', $payment->getStatus());

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('notified', $cardMandateNotification->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandateNotification->notification_id);
        $this->assertNotNull($cardMandateNotification->reminder_id);
        $this->assertNotEmpty($cardMandateNotification->notified_at);

        $this->mockPostDebitNotification();

        $url = $this->testData[__FUNCTION__]['request']['url'];
        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, $payment->getId());
        $this->ba->reminderAppAuth();

        $this->startTest();

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('notified', $cardMandateNotification->getStatus());

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('captured', $payment->getStatus());
    }

    public function testCreateCardMandateAutoPaymentWithAfa()
    {
        $this->testCreateCardMandatePayment();

        $this->mockCreatePreDebitNotification(false, true);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $paymentInput = $this->getDefaultRecurringPaymentArray();
        unset($paymentInput[Payment::CARD]);
        unset($paymentInput[Payment::BANK]);

        $paymentInput[Payment::TOKEN] = $tokenId;

        $order = $this->fixtures->create('order', [
            'amount' => 50000,
            'payment_capture' => 1,
        ]);
        $paymentInput[Payment::ORDER_ID] = $order->getPublicId();

        $this->ba->privateAuth();

        $content = $this->doS2SRecurringPayment($paymentInput);
        $this->assertNotEmpty($content['razorpay_payment_id']);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('auto', $payment->getRecurringType());
        $this->assertEquals('created', $payment->getStatus());

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('failed', $cardMandateNotification->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandateNotification->notification_id);

        $this->mockPostDebitNotification();

        $this->testData[__FUNCTION__]['request']['content']['payload']['mandate.notification']['entity']['id'] = $cardMandateNotification->notification_id;

        $this->startTest();

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('failed', $cardMandateNotification->getStatus());
        $this->assertEquals('approved', $cardMandateNotification->getAfaStatus());

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('captured', $payment->getStatus());
    }

    public function testSubscriptionRegistrationAutoCardMandatePaymentAmountGreaterThanMaxAmountWithAFA()
    {
        $this->testSubscriptionRegistrationInitialCardMandatePaymentAmountGreaterThanMaxAmount();

        $this->mockCreatePreDebitNotification(true, true);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $paymentInput = $this->getDefaultRecurringPaymentArray();
        unset($paymentInput[Payment::CARD]);
        unset($paymentInput[Payment::BANK]);

        $paymentInput[Payment::TOKEN] = $tokenId;

        $order = $this->fixtures->create('order', [
            'amount' => 900000, // Amount greater than Token Max Amount
            'payment_capture' => 1,
        ]);
        $paymentInput[Payment::ORDER_ID] = $order->getPublicId();
        $paymentInput[Payment::AMOUNT] = $order['amount'];

        $this->ba->privateAuth();

        $content = $this->doS2SRecurringPayment($paymentInput);
        $this->assertNotEmpty($content['razorpay_payment_id']);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('auto', $payment->getRecurringType());
        $this->assertEquals('created', $payment->getStatus());

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('notified', $cardMandateNotification->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandateNotification->notification_id);
        $this->assertNotEmpty($cardMandateNotification->notified_at);

        $this->mockPostDebitNotification();

        $this->testData[__FUNCTION__]['request']['content']['payload']['mandate.notification']['entity']['id'] = $cardMandateNotification->notification_id;

        $this->startTest();

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('notified', $cardMandateNotification->getStatus());

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('captured', $payment->getStatus());
    }

    public function testCreateCardMandateAutoPaymentVerificationFailed()
    {
        $this->testCreateCardMandatePayment();

        $this->mockCreatePreDebitNotification();

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $paymentInput = $this->getDefaultRecurringPaymentArray();
        unset($paymentInput[Payment::CARD]);
        unset($paymentInput[Payment::BANK]);

        $paymentInput[Payment::TOKEN] = $tokenId;
        $order = $this->fixtures->create('order', [
            'amount' => 50000,
            'payment_capture' => 1,
        ]);
        $paymentInput[Payment::ORDER_ID] = $order->getPublicId();

        $this->ba->privateAuth();

        $content = $this->doS2SRecurringPayment($paymentInput);
        $this->assertNotEmpty($content['razorpay_payment_id']);

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('auto', $payment->getRecurringType());
        $this->assertEquals('created', $payment->getStatus());

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('notified', $cardMandateNotification->getStatus());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandateNotification->notification_id);
        $this->assertNotNull($cardMandateNotification->reminder_id);
        $this->assertNotEmpty($cardMandateNotification->notified_at);

        $url = $this->testData[__FUNCTION__]['request']['url'];
        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, $payment->getId());
        $this->ba->reminderAppAuth();

        $cardMandate = $this->getDbLastEntity('card_mandate_notification');
        $cardMandate->status = 'cancelled';
        $cardMandate->saveOrFail();

        $this->startTest();

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('failed', $payment->getStatus());
        $this->assertEquals('BAD_REQUEST_PAYMENT_CARD_MANDATE_NOTIFICATION_VERIFY_FAILED', $payment->internal_error_code);
        $this->assertEquals('Payment debit notification failed to verify', $payment->error_description);
    }

    public function testCreateCardMandateAutoPaymentNotificationFailed()
    {
        $this->testCreateCardMandatePayment();

        $this->mockCreatePreDebitNotification(false);

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $paymentInput = $this->getDefaultRecurringPaymentArray();
        unset($paymentInput[Payment::CARD]);
        unset($paymentInput[Payment::BANK]);

        $paymentInput[Payment::TOKEN] = $tokenId;

        $this->ba->privateAuth();

        $exception = false;
        try
        {
            $this->doS2SRecurringPayment($paymentInput);
        }
        catch (BadRequestException $e)
        {
            $exception = true;
            $this->assertEquals('Payment debit notification failed to deliver to customer', $e->getMessage());
            $this->assertEquals('BAD_REQUEST_PAYMENT_CARD_MANDATE_NOTIFICATION_NOT_SENT', $e->getCode());
        }
        finally
        {
            $this->assertTrue($exception);
        }

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('auto', $payment->getRecurringType());
        $this->assertEquals('failed', $payment->getStatus());
        $this->assertEquals('BAD_REQUEST_PAYMENT_CARD_MANDATE_NOTIFICATION_NOT_SENT', $payment->internal_error_code);
        $this->assertEquals('Payment debit notification failed to deliver to customer', $payment->error_description);

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandateNotification->notification_id);
        $this->assertEmpty($cardMandateNotification->reminder_id);
        $this->assertEquals('failed', $cardMandateNotification->getStatus());
    }

    public function testCreateCardMandateAutoPaymentMandateNotActive()
    {
        $this->testCreateCardMandatePayment();

        $this->mockCreatePreDebitNotification();

        $cardMandate = $this->getDbLastEntity('card_mandate');
        $cardMandate->setStatus(Status::PAUSED);
        $cardMandate->saveOrFail();

        $paymentEntity = $this->getLastEntity('payment', true);

        $tokenId = $paymentEntity[Payment::TOKEN_ID];

        $paymentInput = $this->getDefaultRecurringPaymentArray();
        unset($paymentInput[Payment::CARD]);
        unset($paymentInput[Payment::BANK]);

        $paymentInput[Payment::TOKEN] = $tokenId;

        $this->ba->privateAuth();

        $exception = false;
        try
        {
            $this->doS2SRecurringPayment($paymentInput);
        }
        catch (BadRequestException $e)
        {
            $exception = true;
            $this->assertEquals('Card mandate is not active, it is paused by user', $e->getMessage());
            $this->assertEquals('BAD_REQUEST_CARD_MANDATE_IS_NOT_ACTIVE_PAUSED', $e->getCode());
        }
        finally
        {
            $this->assertTrue($exception);
        }

        $this->fixtures->edit('card_mandate', $cardMandate->getId(), [
            'status' => Status::CANCELLED,
        ]);

        $exception = false;
        try
        {
            $this->doS2SRecurringPayment($paymentInput);
        }
        catch (BadRequestException $e)
        {
            $exception = true;
            $this->assertEquals('Card mandate is not active, it is cancelled by user', $e->getMessage());
            $this->assertEquals('BAD_REQUEST_CARD_MANDATE_IS_NOT_ACTIVE_CANCELLED', $e->getCode());
        }
        finally
        {
            $this->assertTrue($exception);
        }

        $this->fixtures->edit('card_mandate', $cardMandate->getId(), [
            'status' => Status::COMPLETED,
        ]);

        $exception = false;
        try
        {
            $this->doS2SRecurringPayment($paymentInput);
        }
        catch (BadRequestException $e)
        {
            $exception = true;
            $this->assertEquals('Card mandate is not active, it is expired', $e->getMessage());
            $this->assertEquals('BAD_REQUEST_CARD_MANDATE_IS_NOT_ACTIVE_EXPIRED', $e->getCode());
        }
        finally
        {
            $this->assertTrue($exception);
        }
    }

    protected function mockPostDebitNotification($success = true)
    {
        $callable = function () use ($success)
        {
            return [
                'success' => $success
            ];
        };

        return $this->mockMandateHQ($callable, 'postDebitNotify');
    }

    protected function mockRegisterMandate()
    {
        $callable = function ()
        {
            return [
                'redirect_url' => "https://mandate-manager.stage.razorpay.in/issuer/hdfc_GX3VC146gmBVNe/hostedpage",
                'id' => "ratn_PP3VC146gmBVGG",
                "status" => "created",
            ];
        };

        return $this->mockMandateHQ($callable);
    }

    protected function mockCancelMandate()
    {
        $callable = function ()
        {
            return [
                'id' => "ratn_PP3VC146gmBVGG",
                "status" => "cancelled",
            ];
        };

        return $this->mockMandateHQ($callable, 'cancelMandate');
    }

    protected function mockReportPayment()
    {
        $callable = function ()
        {
            return [];
        };

        return $this->mockMandateHQ($callable, 'reportPayment');
    }

    protected function mockCreatePreDebitNotification($success = true, $afaRequired = false)
    {
        $callable = function () use ($success, $afaRequired)
        {
            return [
                'id' => 'ratn_PP3VC146gmBVGG',
                'status' => $success ? 'delivered' : 'failed',
                'delivered_at' => Carbon::now()->timestamp,
                'afa_status' => 'created',
                'afa_required' => $afaRequired,
                'afa_completed_at' => 0,
            ];
        };

        return $this->mockMandateHQ($callable, 'createPreDebitNotification');
    }

}
