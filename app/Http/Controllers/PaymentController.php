<?php

namespace RZP\Http\Controllers;

use RZP\Http\ApiResponse;
use RZP\Models\Payment;
use RZP\Models\Card;
use RZP\Trace\TraceCode;
use Request;
use View;

class PaymentController extends Controller
{
    protected $payment;

    public function __construct()
    {
        parent::__construct();

        $this->payment = new Payment\Service();
    }

    public function getPayment($id)
    {
        $payment = $this->payment->fetch($id);

        return ApiResponse::json($payment);
    }

    /**
     * Retrieves payment details
     */
    public function getPayments()
    {
        $input = Request::all();

        $payments = $this->payment->fetchMultiple($input);

        return ApiResponse::json($payments);
    }

    public function getVerify($id)
    {
        $data = $this->payment->verify($id);

        return ApiResponse::json($data);
    }

    /**
     * Refund a payment.
     * @param $id
     */
    public function postRefund($id)
    {
        $input = Request::all();

        $payment = $this->payment->refund($id, $input);

        return ApiResponse::json($payment);
    }

    public function postRefundAuthorized($id)
    {
        $input = Request::all();

        $payment = $this->payment->refundAuthorized($id, $input);

        return ApiResponse::json($payment);
    }

    public function postForceAuthorize($id)
    {
        $input = Request::all();

        $payment = $this->payment->forceAuthorizeFailed($id, $input);

        return ApiResponse::json($payment);
    }

    public function postRefundOldAuthorizedPayments()
    {
        $data = $this->payment->refundOldAuthorizedPayments();

        return ApiResponse::json($data);
    }

    public function postAuthorizeFailedPayment($id)
    {
        $data = $this->payment->authorizeFailed($id);

        return ApiResponse::json($data);
    }

    /**
     * Captures an authorized payment
     *
     * @param string $id Payment ID to capture
     */
    public function postCapture($id)
    {
        $input = Request::all();

        $payment = $this->payment->capture($id, $input);

        return ApiResponse::json($payment);
    }

    public function getPaymentStatusForAsyncPayments($id)
    {
        $data = $this->payment->fetchStatus($id);

        return ApiResponse::json($data);
    }

    public function postCancel($id)
    {
        $input = Request::all();

        $data = $this->payment->cancel($id, $input);

        return ApiResponse::json($data);
    }

    public function postAutoCapture()
    {
        $data = $this->payment->autoCaptureOldAuthorizedPayments();

        return ApiResponse::json($data);
    }

    public function getCardForPayment($id)
    {
        $card = $this->payment->getCardForPayment($id);

        return ApiResponse::json($card);
    }

    public function getRefundsForPayment($paymentId)
    {
        $refunds = $this->payment->retrieveRefundsForPayment($paymentId);

        return ApiResponse::json($refunds);
    }

    public function getRefundByRefundAndPaymentId($paymentId, $rfndId)
    {
        $refunds = $this->payment->retrieveRefundByIdAndPaymentId($paymentId, $rfndId);

        return ApiResponse::json($refunds);
    }

    public function postTimeout()
    {
        $data = $this->payment->timeoutOldPayments();

        return ApiResponse::json($data);
    }

    public function getCard($id)
    {
        $data = (new Card\Service)->fetchById($id);

        return ApiResponse::json($data);
    }

    public function getCards()
    {
        $input = Request::all();

        $data = (new Card\Service)->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function getAuthNotify()
    {
        $data = $this->payment->notifyAuthorizedPayments();

        return ApiResponse::json($data);
    }

    public function getAutoCaptureEmail()
    {
        $data = $this->payment->deliverAutoCaptureEmail();

        return ApiResponse::json($data);
    }

    public function getVerifyPayments($filter)
    {
        $data = $this->payment->verifyMultiplePayments($filter);

        return ApiResponse::json($data);
    }

    public function getVerifyPaymentsWithPreviousVerifyResultFailed()
    {
        $data = $this->payment->verifyPaymentsWithFailedVerifyResult();

        return ApiResponse::json($data);
    }

    public function postDummyReturnCallback()
    {
        $input = Request::all();

        return ApiResponse::json($input);
    }

    public function sendReminderMailForAuthorizedPayments()
    {
        return (new Payment\Service)->sendReminderMerchantMailForAuthorizedPayments();
    }

    public function postDummyRoute()
    {
        $input = Request::all();

        $this->app['trace']->info(
            TraceCode::PAYMENT_WEBHOOK,
            $input);
    }

    public function postPaymentMetadata($id)
    {
        $input = Request::all();

        $data = $this->payment->addPaymentMetadata($id, $input);

        return ApiResponse::json($data);
    }

    public function postCaptureVerify($id)
    {
        $data = $this->payment->verifyCapture($id);

        return ApiResponse::json($data);
    }

    public function postRefundMultipleAuthorizedPaymentsForOrders()
    {
        $data = $this->payment->refundMultipleAuthorizedPaymentsForOrders();

        return ApiResponse::json($data);
    }
}
