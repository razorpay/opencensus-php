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
    public function getPayment($id)
    {
        $payment = $this->service('payment')->fetch($id);

        return ApiResponse::json($payment);
    }

    /**
     * Retrieves payment details
     */
    public function getPayments()
    {
        $input = Request::all();

        $payments = $this->service('payment')->fetchMultiple($input);

        return ApiResponse::json($payments);
    }

    public function getVerify($id)
    {
        $data = $this->service('payment')->verify($id);

        return ApiResponse::json($data);
    }

    /**
     * Refund a payment.
     * @param $id
     */
    public function postRefund($id)
    {
        $input = Request::all();

        $payment = $this->service('payment')->refund($id, $input);

        return ApiResponse::json($payment);
    }

    public function postRefundAuthorized($id)
    {
        $input = Request::all();

        $payment = $this->service('payment')->refundAuthorized($id, $input);

        return ApiResponse::json($payment);
    }

    public function postRefundAuthorizedInBulk()
    {
        $input = Request::all();

        $summary = $this->service('payment')->refundAuthorizedInBulk($input);

        return ApiResponse::json($summary);
    }

    public function postForceAuthorize($id)
    {
        $input = Request::all();

        $payment = $this->service('payment')->forceAuthorizeFailed($id, $input);

        return ApiResponse::json($payment);
    }

    public function postRefundOldAuthorizedPayments()
    {
        $data = $this->service('payment')->refundOldAuthorizedPayments();

        return ApiResponse::json($data);
    }

    public function postAuthorizeFailedPayment($id)
    {
        $data = $this->service('payment')->authorizeFailed($id);

        return ApiResponse::json($data);
    }

    public function postFixAuthorizedAt()
    {
        $input = Request::all();

        $data = $this->service('payment')->fixAuthorizeAt($input);

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

        $payment = $this->service('payment')->capture($id, $input);

        return ApiResponse::json($payment);
    }

    public function getPaymentStatusForAsyncPayments($id)
    {
        $data = $this->service('payment')->fetchStatus($id);

        return ApiResponse::json($data);
    }

    public function postCancel($id)
    {
        $input = Request::all();

        $data = $this->service('payment')->cancel($id, $input);

        return ApiResponse::json($data);
    }

    public function postPayout(string $id)
    {
        $input = Request::all();

        $data = $this->service('payment')->payout($id, $input);

        return ApiResponse::json($data);
    }

    /**
     * @deprecated
     * @return mixed
     */
    public function postAutoCapture()
    {
        $data = $this->service('payment')->autoCaptureOldAuthorizedPayments();

        return ApiResponse::json($data);
    }

    public function getCardForPayment($id)
    {
        $card = $this->service('payment')->getCardForPayment($id);

        return ApiResponse::json($card);
    }

    public function getRefundsForPayment($paymentId)
    {
        $refunds = $this->service('payment')->retrieveRefundsForPayment($paymentId);

        return ApiResponse::json($refunds);
    }

    public function getRefundByRefundAndPaymentId($paymentId, $rfndId)
    {
        $refunds = $this->service('payment')->retrieveRefundByIdAndPaymentId($paymentId, $rfndId);

        return ApiResponse::json($refunds);
    }

    public function getTransactionForPayment($paymentId)
    {
        $transaction = $this->service('payment')->fetchTransactionByPaymentId($paymentId);

        return ApiResponse::json($transaction);
    }

    public function postTimeout()
    {
        $data = $this->service('payment')->timeoutOldPayments();

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
        $data = $this->service('payment')->notifyAuthorizedPayments();

        return ApiResponse::json($data);
    }

    public function getAutoCaptureEmail()
    {
        $data = $this->service('payment')->deliverAutoCaptureEmail();

        return ApiResponse::json($data);
    }

    public function postVerifyPayments($filter)
    {
        $input = Request::all();

        $data = $this->service('payment')->verifyMultiplePayments($filter, $input);

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

        $data = $this->service('payment')->addPaymentMetadata($id, $input);

        return ApiResponse::json($data);
    }

    public function postCaptureVerify($id)
    {
        $data = $this->service('payment')->verifyCapture($id);

        return ApiResponse::json($data);
    }

    public function postManualGatewayCapture($id)
    {
        $data = $this->service('payment')->manualGatewayCapture($id);

        return ApiResponse::json($data);
    }

    public function postRefundMultipleAuthorizedPaymentsForOrders()
    {
        $data = $this->service('payment')->refundMultipleAuthorizedPaymentsForOrders();

        return ApiResponse::json($data);
    }

    public function postAuthorizeLockTimeOut($paymentIds)
    {
        $data = $this->service('payment')->authorizeLockTimeOutPayments($paymentIds);

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

        $transfers = $this->service('payment')->transfer($paymentId, $input);

        return ApiResponse::json($transfers);
    }

    /**
     * Get all transfers made on a payment
     *
     * @param  string   $paymentId
     */
    public function getTransfers(string $paymentId)
    {
        $transfers = $this->service('payment')->getTransfers($paymentId);

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

        $data = $this->service('payment')->updateOnHold($input);

        return ApiResponse::json($data);
    }
}
