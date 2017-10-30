<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use View;

use RZP\Constants\Entity as E;
use RZP\Trace\TraceCode;

class PaymentController extends Controller
{
    public function getPayment($id)
    {
        $input = Request::all();

        $payment = $this->service()->fetch($id, $input);

        return ApiResponse::json($payment);
    }

    /**
     * Retrieves payment details
     */
    public function getPayments()
    {
        $input = Request::all();

        $payments = $this->service()->fetchMultiple($input);

        return ApiResponse::json($payments);
    }

    public function getVerify($id)
    {
        $data = $this->service()->verify($id);

        return ApiResponse::json($data);
    }

    /**
     * Refund a payment.
     * @param $id
     */
    public function postRefund($id)
    {
        $input = Request::all();

        $payment = $this->service()->refund($id, $input);

        return ApiResponse::json($payment);
    }

    public function postRefundAuthorized($id)
    {
        $input = Request::all();

        $payment = $this->service()->refundAuthorized($id, $input);

        return ApiResponse::json($payment);
    }

    public function postRefundAuthorizedInBulk()
    {
        $input = Request::all();

        $summary = $this->service()->refundAuthorizedInBulk($input);

        return ApiResponse::json($summary);
    }

    public function postForceAuthorize($id)
    {
        $input = Request::all();

        $payment = $this->service()->forceAuthorizeFailed($id, $input);

        return ApiResponse::json($payment);
    }

    public function postRefundOldAuthorizedPayments()
    {
        $data = $this->service()->refundOldAuthorizedPayments();

        return ApiResponse::json($data);
    }

    public function postAuthorizeFailedPayment($id)
    {
        $data = $this->service()->authorizeFailed($id);

        return ApiResponse::json($data);
    }

    public function postFixAuthorizedAt()
    {
        $input = Request::all();

        $data = $this->service()->fixAuthorizeAt($input);

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

        $payment = $this->service()->capture($id, $input);

        return ApiResponse::json($payment);
    }

    /**
     * Captures authorized payment in bulk
     */
    public function postBulkCapture()
    {
        $input = Request::all();

        $data = $this->service()->captureInBulk($input);

        return ApiResponse::json($data);
    }

    public function getPaymentStatusForAsyncPayments($id)
    {
        $data = $this->service()->fetchStatus($id);

        return ApiResponse::json($data);
    }

    public function postCancel($id)
    {
        $input = Request::all();

        $data = $this->service()->cancel($id, $input);

        return ApiResponse::json($data);
    }

    public function postPayout(string $id)
    {
        $input = Request::all();

        $data = $this->service()->payout($id, $input);

        return ApiResponse::json($data);
    }

    /**
     * @deprecated
     * @return mixed
     */
    public function postAutoCapture()
    {
        $data = $this->service()->autoCaptureOldAuthorizedPayments();

        return ApiResponse::json($data);
    }

    public function getCardForPayment($id)
    {
        $card = $this->service()->getCardForPayment($id);

        return ApiResponse::json($card);
    }

    public function getRefundsForPayment($paymentId)
    {
        $refunds = $this->service()->retrieveRefundsForPayment($paymentId);

        return ApiResponse::json($refunds);
    }

    public function getRefundByRefundAndPaymentId($paymentId, $rfndId)
    {
        $refunds = $this->service()->retrieveRefundByIdAndPaymentId($paymentId, $rfndId);

        return ApiResponse::json($refunds);
    }

    public function getTransactionForPayment($paymentId)
    {
        $transaction = $this->service()->fetchTransactionByPaymentId($paymentId);

        return ApiResponse::json($transaction);
    }

    public function postTimeout()
    {
        $data = $this->service()->timeoutOldPayments();

        return ApiResponse::json($data);
    }

    public function getCard($id)
    {
        $data = $this->service(E::CARD)->fetchById($id);

        return ApiResponse::json($data);
    }

    public function getCardRecurring()
    {
        $input = Request::all();

        $data = $this->service(E::CARD)->getCardRecurring($input);

        return ApiResponse::json($data);
    }

    public function getCards()
    {
        $input = Request::all();

        $data = $this->service(E::CARD)->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function getAuthNotify()
    {
        $data = $this->service()->notifyAuthorizedPayments();

        return ApiResponse::json($data);
    }

    public function getAutoCaptureEmail()
    {
        $data = $this->service()->deliverAutoCaptureEmail();

        return ApiResponse::json($data);
    }

    public function postVerifyPayments($filter)
    {
        $input = Request::all();

        $data = $this->service()->verifyMultiplePayments($filter, $input);

        return ApiResponse::json($data);
    }

    public function postDummyReturnCallback()
    {
        $input = Request::all();

        return ApiResponse::json($input);
    }

    public function sendReminderMailForAuthorizedPayments()
    {
        return $this->service()->sendReminderMerchantMailForAuthorizedPayments();
    }

    public function postDummyRoute()
    {
        $input = Request::all();

        $this->app['trace']->info(
            TraceCode::PAYMENT_WEBHOOK,
            $input);

        return ApiResponse::json($input);
    }

    public function postPaymentMetadata($id)
    {
        $input = Request::all();

        $data = $this->service()->addPaymentMetadata($id, $input);

        return ApiResponse::json($data);
    }

    public function postCaptureVerify($id)
    {
        $data = $this->service()->verifyCapture($id);

        return ApiResponse::json($data);
    }

    public function postManualGatewayCapture($id)
    {
        $data = $this->service()->manualGatewayCapture($id);

        return ApiResponse::json($data);
    }

    public function postRefundAuthorizedPaymentsOfPaidOrders()
    {
        $data = $this->service()->refundAuthorizedPaymentsOfPaidOrders();

        return ApiResponse::json($data);
    }

    public function postAuthorizeLockTimeOut($paymentIds)
    {
        $data = $this->service()->authorizeLockTimeOutPayments($paymentIds);

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

        $transfers = $this->service()->transfer($paymentId, $input);

        return ApiResponse::json($transfers);
    }

    /**
     * Get all transfers made on a payment
     *
     * @param  string   $paymentId
     */
    public function getTransfers(string $paymentId)
    {
        $transfers = $this->service()->getTransfers($paymentId);

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

        $data = $this->service()->updateOnHold($input);

        return ApiResponse::json($data);
    }
}
