<?php

namespace RZP\Http\Controllers;

use ApiResponse;
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

        $this->payment = new Payment\Service;
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

    public function postRefundAuthorizedInBulk()
    {
        $input = Request::all();

        $summary = $this->payment->refundAuthorizedInBulk($input);

        return ApiResponse::json($summary);
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

    public function postFixAuthorizedAt()
    {
        $input = Request::all();

        $data = $this->payment->fixAuthorizeAt($input);

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

    public function postPayout(string $id)
    {
        $input = Request::all();

        $data = $this->payment->payout($id, $input);

        return ApiResponse::json($data);
    }

    /**
     * @deprecated
     * @return mixed
     */
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

    public function getTransactionForPayment($paymentId)
    {
        $transaction = $this->payment->fetchTransactionByPaymentId($paymentId);

        return ApiResponse::json($transaction);
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

    public function getCardRecurring()
    {
        $input = Request::all();

        $data = (new Card\Service)->getCardRecurring($input);

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

    public function postVerifyPayments($filter)
    {
        $input = Request::all();

        $data = $this->payment->verifyMultiplePayments($filter, $input);

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

    public function postManualGatewayCapture($id)
    {
        $data = $this->payment->manualGatewayCapture($id);

        return ApiResponse::json($data);
    }

    public function postRefundAuthorizedPaymentsOfPaidOrders()
    {
        $data = $this->payment->refundAuthorizedPaymentsOfPaidOrders();

        return ApiResponse::json($data);
    }

    public function postAuthorizeLockTimeOut($paymentIds)
    {
        $data = $this->payment->authorizeLockTimeOutPayments($paymentIds);

        return ApiResponse::json($data);
    }

    /**
     * Create new transfers on a payment
     *
     * @param  string   $paymentId
     */
    public function postTransfer(string $paymentId)
    {
        $input = Request::all();

        $transfers = $this->payment->transfer($paymentId, $input);

        return ApiResponse::json($transfers);
    }

    /**
     * Get all transfers made on a payment
     *
     * @param  string   $paymentId
     */
    public function getTransfers(string $paymentId)
    {
        $transfers = $this->payment->getTransfers($paymentId);

        return ApiResponse::json($transfers);
    }

    /**
     * CRON route: Fetches all payments with on_hold_until timestamps elapsed
     * and updates the on_hold flag to false to allow settlements
     * for the payment txn.
     *
     * If payment has a linked transfer, this updates it's on_hold value too.
     *
     * @return ApiResponse
     */
    public function updateOnHold()
    {
        $input = Request::all();

        $data = $this->payment->updateOnHold($input);

        return ApiResponse::json($data);
    }
}
