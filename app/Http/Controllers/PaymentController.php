<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Constants\HyperTrace;
use RZP\Trace\Tracer;
use View;

use RZP\Constants\Entity as E;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Analytics\Service as PaymentAnalyticsService;

class PaymentController extends Controller
{
    use Traits\HasCrudMethods;

    public function getPayment($id)
    {
        $input = Request::all();

        $payment = $this->service()->fetch($id, $input);

        return ApiResponse::json($payment);
    }

    public function getPaymentById($id)
    {
        $input = Request::all();

        $payment = $this->service()->fetchById($id, $input);

        return ApiResponse::json($payment);
    }

    public function getPaymentForSubscription($paymentId, $subscriptionId)
    {
        $payment = $this->service()->fetchForSubscription($paymentId, $subscriptionId);

        return ApiResponse::json($payment);
    }


    public function getPaymentwithSubscription($subscriptionId)
    {
        $payment = $this->service()->fetchpaymentwithSubscription($subscriptionId);

        return ApiResponse::json($payment);
    }

    /*
     * Temporary code
     */
    public function getPaymentwithSubscriptionEmailAndContactNotNull($subscriptionId)
    {
        $payment = $this->service()->fetchpaymentwithSubscriptionEmailAndContactNotNull($subscriptionId);

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

    public function getPaymentsStatusCountInternal()
    {
        $input = Request::all();

        $payments = $this->service()->fetchStatusCountInternal($input);

        return ApiResponse::json($payments);
    }

    public function getPaymentsStatusCount()
    {
        $input = Request::all();

        $payments = $this->service()->fetchStatusCount($input);

        return ApiResponse::json($payments);
    }

    public function getPaymentFlows()
    {
        $input = Request::all();

        $data = $this->service()->getPaymentFlows($input);

        return ApiResponse::json($data);
    }

    public function getPaymentAuthenticationEntity($id)
    {
        $data = $this->service()->getAuthenticationEntity($id);

        return ApiResponse::json($data);
    }

    public function getPaymentAuthorizationEntity($id)
    {
        $data = $this->service()->getAuthorizationEntity($id);

        return ApiResponse::json($data);
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

        $refund = Tracer::inSpan(['name' => HyperTrace::PAYMENT_REFUND], function() use ($id, $input) {
            return $this->service()->refund($id, $input);
        });

        return ApiResponse::json($refund);
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
        $input = Request::all();

        $data = $this->service()->refundOldAuthorizedPayments($input);

        return ApiResponse::json($data);
    }

    public function postAuthorizeFailedPayment($id)
    {
        $data = $this->service()->authorizeFailed($id);

        return ApiResponse::json($data);
    }

    public function postFixAttemptedOrders()
    {
        $input = Request::all();

        $data = $this->service()->fixAttemptedOrders($input);

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
        $input = Request::all();

        $refunds = $this->service()->retrieveRefundsForPayment($paymentId, $input);

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
        $input = Request::all();

        $data = $this->service()->timeoutOldPayments($input);

        return ApiResponse::json($data);
    }

    public function postTimeoutNew($paymentId)
    {
        $data = $this->service()->timeoutPaymentsNew($paymentId);

        return ApiResponse::json($data);
    }

    public function postAuthTimeout()
    {
        $input = Request::all();

        $data = $this->service()->timeoutAuthenticatedPayments($input);

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

    public function getAutoCaptureEmail()
    {
        $data = $this->service()->deliverAutoCaptureEmail();

        return ApiResponse::json($data);
    }

    public function postVerifyAllPayments()
    {
        $input = Request::all();

        $data = $this->service()->verifyAllPayments($input);

        return ApiResponse::json($data);
    }

    public function postVerifyAllPaymentsNewRoute()
    {
        $input = Request::all();

        $data = $this->service()->verifyAllPaymentsNewRoute($input);

        return ApiResponse::json($data);
    }

    public function postVerifyNew($id)
    {
        $data = $this->service()->verifyPaymentNewRoute($id);

        return ApiResponse::json($data);
    }

    public function postVerifyCapturedPayments()
    {
        $input = Request::all();

        $data = $this->service()->verifyCapturedPayments($input);

        return ApiResponse::json($data);
    }

    public function postVerifyPaymentsBulk()
    {
        $input = Request::all();

        $data = $this->service()->verifyPaymentsInBulk($input);

        return ApiResponse::json($data);
    }

    public function postVerifyPaymentsBulkNewRoute()
    {
        $input = Request::all();

        $data = $this->service()->verifyPaymentsInBulkNewRoute($input);

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

    public function postPendingGatewayCapture()
    {
        $input = Request::all();

        $data = $this->service()->postPendingGatewayCapture($input);

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

    public function createTransferFromBatch(string $paymentId)
    {
        $input = Request::all();

        $transfer = $this->service()->createTransferFromBatch($paymentId, $input);

        return ApiResponse::json($transfer);
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

    public function updateOnHoldBulkUpdate()
    {
        $input = Request::all();

        $data = $this->service()->updateOnHoldBulkUpdate($input);

        return ApiResponse::json($data);
    }

    /**
     * @param string $paymentId
     *
     * @return \Illuminate\Http\JsonResponse;
     */
    public function postAcknowledge(string $paymentId)
    {
        $input = Request::all();

        $this->service()->acknowledge($paymentId, $input);

        return ApiResponse::json([], 204);
    }

    public function updateReceiverData()
    {
        $data = $this->service()->updateReceiverData();

        return ApiResponse::json($data);
    }

    public function postPaymentValidateVpa()
    {
        $input = Request::all();

        $data = $this->service()->validateVpa($input);

        return ApiResponse::json($data);
    }

    public function postPaymentValidateEntity()
    {
        $input = Request::all();

        $data = $this->service()->validateEntity($input);

        return ApiResponse::json($data);
    }

    public function getPaymentFlowsPrivate()
    {
        $input = Request::all();

        $data = $this->service()->getPaymentFlowsPrivate($input);

        return ApiResponse::json($data);
    }

    public function paymentCardVaultMigrate()
    {
        $input = Request::all();

        $data = $this->service()->paymentCardVaultMigrate($input);

        return ApiResponse::json($data);
    }


    public function getPaymentMerchantActions($id)
    {
        $data = $this->service()->getPaymentMerchantActions($id);

        return ApiResponse::json($data);
    }

    public function postUpdateRefundAtForPayments()
    {
        $input = Request::all();

        $data = $this->service()->updateRefundAtForPayments($input);

        return ApiResponse::json($data);
    }

    public function postPaymentMetaReference()
    {
        $input = Request::all();

        $data = $this->service()->postPaymentMetaReference($input);

        return ApiResponse::json($data);
    }

    public function getPaymentMetaByPaymentIdAction(string $paymentId, string $actionType)
    {
        $pm = $this->service()->getPaymentMetaByPaymentIdAction($paymentId, $actionType);

        return ApiResponse::json($pm);
    }

    public function sendNotification()
    {
        $input = Request::all();

        $this->service()->sendNotification($input);

        return ApiResponse::json([]);
    }
    public function postRefundAuthorizedInternal($id)
    {
        $input = Request::all();

        $refund = $this->service()->refundAuthorized($id, $input);

        $data = ["data" => $refund];

        return ApiResponse::json($data);
    }

    public function postVerifyDisabledGateway()
    {
        $input = Request::all();

        $success =  $this->service()->addVerifyDisabledGateway($input);

        return ApiResponse::json($success);
    }

    public function createPaymentAnalyticsPartition()
    {
        $response =  (new PaymentAnalyticsService())->createPaymentAnalyticsPartition();

        return ApiResponse::json($response);
    }

    public function updateReference6($id)
    {
        $data = $this->service()->updateReference6($id);

        return ApiResponse::json($data);
    }

    public function reviveOrderViaPL($id)
    {
        $data = $this->service()->reviveOrderViaPL($id);

        return ApiResponse::json($data);
    }

    public function paymentsCardEsSyncCron()
    {
        $input = Request::all();

        $success =  $this->service()->paymentsCardEsSyncCron($input);

        return ApiResponse::json($success);
    }

    public function postAuthorizeFailedUpiPayment()
    {
        $input = Request::all();

        $response = $this->service()->authorizeFailedUpiPayment($input);

        return ApiResponse::json($response);
    }

    public function internalPricingFetchForPayment($id)
    {
        $input = Request::all();

        $pricingResponse = $this->service()->internalPricingFetchForPayment($id, $input);

        return ApiResponse::json($pricingResponse);
    }

    public function internalRiskNotificationForRearch($id)
    {
        $input = Request::all();

        $this->service()->internalRiskNotificationForRearch($id, $input);

        return ApiResponse::json();
    }
}
