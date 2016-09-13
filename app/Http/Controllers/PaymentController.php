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
    protected $refund;

    public function __construct()
    {
        parent::__construct();

        $this->payment = new Payment\Service();
        $this->refund = new Payment\Refund\Service();
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

    public function postRefundOldAUthorizedPayments()
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

    /**
     * Creates transactions for all refunds if not present.
     */
    public function postRefundsTransactions()
    {
        $summary = $this->refund->createMissingTransactions();

        return ApiResponse::json($summary);
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

    public function getRefund($id)
    {
        $refunds = $this->refund->fetch($id);

        return ApiResponse::json($refunds);
    }

    public function getRefunds()
    {
        $input = Request::all();

        $refunds = $this->refund->fetchMultiple($input);

        return ApiResponse::json($refunds);
    }

    public function getRefundByRefundAndPaymentId($paymentId, $rfndId)
    {
        $refunds = $this->payment->retrieveRefundByIdAndPaymentId($paymentId, $rfndId);

        return ApiResponse::json($refunds);
    }

    public function generateNetbankingRefunds()
    {
        $input = Request::all();
        // Just a hack, will be shifted to the /refunds/excel route
        // once properly deployed
        $input['method'] = 'netbanking';

        $refundExcel = $this->refund->getRefundsFile($input);

        return ApiResponse::json($refundExcel);
    }

    public function generateRefunds()
    {
        $input = Request::all();

        $refundExcel = $this->refund->getRefundsFile($input);

        return ApiResponse::json($refundExcel);
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

    /**
     * The current implementation:
     * This is run when refunds in api don't have corresponding transactions (refund on gateway was not called).
     * On gateway, we check whether we should have called refund for this entity or not. If the check returns true,
     * we call refund on the gateway and then create a refund transaction on api side.
     *
     * The name is a misnomer. This route should have ideally meant whether a refund was successful or not on gateway.
     *
     * @param string $ids Refund IDs of refunds without refund transactions, but should have had.
     * @return array
     */
    public function postRefundVerify($ids)
    {
        $data = $this->refund->verify($ids);

        return ApiResponse::json($data);
    }

    public function postCaptureVerify($id)
    {
        $data = $this->payment->verifyCapture($id);

        return ApiResponse::json($data);
    }

    /**
     * The current implementation:
     * These refunds already have a refund transaction.
     * But, we did not actually call refund on gateway or refund on gateway actually failed.
     * This route calls refund on gateway forcefully (albeit some checks).
     *
     * @param string $refundIds refund IDs for which we want to call refund on gateway
     * @return mixed
     */
    public function postManualGatewayRefund($refundIds)
    {
        $data = $this->payment->manualGatewayRefund($refundIds);

        return ApiResponse::json($data);
    }

    /**
     * This is a little similar to manual gateway refund and verify refund (a combination).
     *
     * In this route, we get all the refunds which have been timed out. We call verify on the gateway
     * to find out whether the refund was done successfully. If it has, we record the refund on gateway. If it has
     * not, we just notify on slack and move on.
     * We DO NOT call refund on the gateway. (That's why we don't use verifyRefund/manualRefund)
     *
     * Two basic checks which we would have here:
     * - The refund on api side has a corresponding transaction.
     * - No refund entity created on the gateway side.
     *
     * @param $gateway
     */
    public function postGatewayRefundRecord($gateway)
    {
        $data = $this->refund->createGatewayRefundRecords($gateway);

        return ApiResponse::json($data);
    }
}
