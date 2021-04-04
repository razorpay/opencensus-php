<?php

namespace RZP\Tests\Functional\CardMandate;

use Mockery;

use RZP\Models\Bank\IFSC;
use RZP\Constants\Entity as E;
use RZP\Models\CardMandate\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Models\Payment\Entity as Payment;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class CardMandateTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

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
        ]);
        $this->paymentInput['card']['number'] = '4000184186218826';
        $this->paymentInput['order_id'] = $order->getPublicId();

        $this->mandateHQ = Mockery::mock('RZP\Services\MandateHQ', [$this->app]);
        $this->app->instance('mandateHQ', $this->mandateHQ);

        $this->mandateConfirm = 'true';
    }

    public function testCreateCardMandatePayment()
    {
        $this->mockRegisterMandate();

        $this->mockConfirmMandate();

        $request = [
            'method'  => 'POST',
            'url'     => '/payments/create/ajax',
            'content' => $this->paymentInput,
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['razorpay_payment_id'] ?? null);

        $payment = $this->getDbLastEntity(E::PAYMENT);
        $this->assertEquals('authorized', $payment->getStatus());
        $this->assertEquals('initial', $payment->getRecurringType());
        $this->assertNotNull($payment->getTokenId());

        $token = $payment->localToken;
        $this->assertNotEmpty($token);
        $this->assertEquals('confirmed', $token->getRecurringStatus());

        $cardMandate = $this->getDbLastEntity(E::CARD_MANDATE);
        $this->assertNotEmpty($cardMandate);
        $this->assertNotEmpty($cardMandate->getMandateSummaryUrl());
        $this->assertEquals('active', $cardMandate->getStatus());
        $this->assertEquals('ratn_GX3VC146gmBVNe', $cardMandate->getMandateRegisterId());
        $this->assertEquals('ratn_PP3VC146gmBVGG', $cardMandate->getMandateId());
    }

    public function testCreateCardMandatePaymentForMandateCancelledByCustomer()
    {
        $this->mockRegisterMandate();

        $this->mandateConfirm = 'false';

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
        $this->assertEmpty($cardMandate->getMandateId());
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

        $this->mockVerifyNotification();

        $this->mockPostDebitNotification();

        $url = $this->testData[__FUNCTION__]['request']['url'];
        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, $payment->getId());
        $this->ba->reminderAppAuth();

        $this->startTest();

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('post_debit_notified', $cardMandateNotification->getStatus());

        $payment = $this->getDbLastEntity('payment');
        $this->assertEquals('authorized', $payment->getStatus());
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

        $this->mockVerifyNotification(false);

        $url = $this->testData[__FUNCTION__]['request']['url'];
        $this->testData[__FUNCTION__]['request']['url'] = sprintf($url, $payment->getId());
        $this->ba->reminderAppAuth();

        $this->startTest();

        $cardMandateNotification = $this->getDbLastEntity('card_mandate_notification');
        $this->assertEquals('verification_failed', $cardMandateNotification->getStatus());

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

        $request = array(
            'method'  => 'POST',
            'url'     => '/mandate_hq/callback',
            'content' => [
                'entity' => 'mandate',
                'id' => $cardMandate->mandate_id,
                'status' => 'paused',
            ],
        );

        $this->ba->mandateHQAuth();
        $this->makeRequestAndGetContent($request);

        $cardMandate = $this->getDbLastEntity('card_mandate');
        $this->assertEquals('paused', $cardMandate->status);

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
            'status' => Status::EXPIRED,
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

    protected function mockVerifyNotification($success = true)
    {
        $callable = function () use ($success)
        {
            return [
                'success' => $success,
                'error_code' => "",
                'error_message' => ""
            ];
        };

        return $this->mockMandateHQ($callable, 'verifyNotification');
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
                'error' => [
                    'success' => true,
                    'error_code' => "",
                    'error_message' => ""
                ],
                'redirect_url' => "https://mandate-manager.stage.razorpay.in/issuer/hdfc_GX3VC146gmBVNe/hostedpage",
                'mandate_register_id' => "ratn_GX3VC146gmBVNe"
            ];
        };

        return $this->mockMandateHQ($callable);
    }

    protected function mockConfirmMandate()
    {
        $callable = function ()
        {
            return [
                'error' => [
                    'success' => true,
                    'error_code' => "",
                    'error_message' => ""
                ],
                'mandateId' => 'ratn_PP3VC146gmBVGG'
            ];
        };

        return $this->mockMandateHQ($callable, 'confirmMandate');
    }

    protected function mockCreatePreDebitNotification($success = true)
    {
        $callable = function () use ($success)
        {
            return [
                'error' => [
                    'success' => $success,
                    'error_code' => "",
                    'error_message' => ""
                ],
                'notification_id' => 'ratn_PP3VC146gmBVGG',
                'status' => $success ? 'debit_pending' : 'failed',
            ];
        };

        return $this->mockMandateHQ($callable, 'createPreDebitNotification');
    }

    protected function mockMandateHQ($callable = null, $method = 'registerMandate')
    {
        $this->mandateHQ->shouldReceive($method)
            ->andReturnUsing($callable);
    }
}
