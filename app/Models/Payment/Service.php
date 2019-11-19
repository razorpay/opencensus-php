<?php

namespace RZP\Models\Payment;

use Mail;
use Crypt;
use Config;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Constants\Mode;

use RZP\Jobs;
use RZP\Diag\EventCode;
use RZP\Exception;
use RZP\Error;
use RZP\Mail\Merchant\AuthorizedPaymentsReminder as AuthorizedPaymentsReminderMail;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Models\Offer;
use RZP\Models\Invoice;
use RZP\Models\Payment;
use RZP\Models\Card;
use RZP\Models\Transfer;
use RZP\Models\Transaction;
use RZP\Models\Admin\Org;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants;
use RZP\Constants\MailTags;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Gateway;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Verify\Verify;
use RZP\Models\Payment\Refund\Constants as RefundConstants;

class Service extends Base\Service
{
    protected $merchant;

    protected $core;

    protected $slack;

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Payment\Core;

        $this->slack = $this->app['slack'];

        $this->mutex = $this->app['api.mutex'];
    }

    /**
     * Processes a payment.
     * @param array $input
     * @return array|mixed
     * @throws Exception\BadRequestValidationFailureException
     */
    public function process(array $input)
    {
        return $this->getNewProcessor()->process($input);
    }

    /**
     * Processes a wallet payment
     *
     * @param array $input
     *
     * @return array|mixed
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     */
    public function processWallet(array $input)
    {
        // Just a hack to get around mobikwik normal flow
        $input['_']['source']   = 's2s';
        $input['method']        = 'wallet';

        if (Payment\Gateway::isPowerWallet($input['wallet']) === false)
        {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_SUPPORTED);
        }

        return $this->getNewProcessor()->process($input);
    }

    /**
     * Processes a upi payment
     *
     * @param array $input
     *
     * @return array|mixed
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     */
    public function processUpi(array $input)
    {
        $input['_']['source']   = 's2s';
        $input['method']        = 'upi';

        return $this->getNewProcessor()->process($input);
    }

    public function processAndReturnFees(array & $input)
    {
        return $this->getNewProcessor()->processAndReturnFees($input);
    }

    /**
     * Resend OTP
     *
     * @param string  $id
     * @param array   $input
     *
     * @return array
     */
    public function otpResend($id, $input)
    {
        return $this->getNewProcessor()->otpResend($id, $input);
    }

    /*
     * Topup a wallet
     *
     * @param string $id
     * @param array  $input
     *
     * @return array
     */
    public function topup($id, $input)
    {
        $data = $this->getNewProcessor()->topup($id, $input);

        return $data;
    }

    /**
     * Refunds a payment
     *
     * @param  string $id
     * @param  array  $input
     *
     * @return array
     */
    public function refund($id, array $input)
    {
        $this->trace->info(
            TraceCode::PAYMENT_REFUND_REQUEST,
            [
                'payment_id' => $id,
                'input'      => $input
            ]);

        $refund = $this->getNewProcessor()->refundPaymentViaMerchant($id, $input);

        return $refund->toArrayPublic();
    }

    /**
     * Refunds a payment
     *
     * @param string  $id
     * @param array   $input
     *
     * @return Payment\Entity
     */
    public function refundAuthorized($id, array $input)
    {
        //
        // Since this is in admin auth, we won't
        // have any merchant to check this with.
        //
        $payment = $this->repo->payment->findByPublicId($id);

        $refund = $this->getNewProcessor($payment->merchant)->refundAuthorizedPayment($payment, $input);

        return $refund->toArrayPublic();
    }

    public function refundAuthorizedInBulk(array $input)
    {
        $paymentIds = $input['payment_ids'];

        $count = count($paymentIds);

        $success = $failure = 0;

        $failurePayments = $successRefunds = [];

        foreach ($paymentIds as $paymentId)
        {
            Entity::verifyIdAndSilentlyStripSign($paymentId);

            $payment = $this->repo->payment->findOrFailPublic($paymentId);

            $merchant = $payment->merchant;

            try
            {
                $refund = $this->getNewProcessor($merchant)->refundAuthorizedPayment($payment);

                $success++;

                $successRefunds[] = $refund->getId();
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex);

                $failure++;

                $failurePayments[] = $paymentId;
            }
        }

        $data = [
            'count'            => $count,
            'success'          => $success,
            'failure'          => $failure,
            'failure_payments' => $failurePayments,
            'success_refunds'  => $successRefunds,
        ];

        $this->trace->info(
            TraceCode::REFUND_AUTHORIZE_BULK,
            $data
        );

        return $data;
    }

    public function verify($id)
    {
        $payment = $this->core->retrieveById($id);

        $merchant = $this->repo->merchant->fetchMerchantFromEntity($payment);

        $data = $this->getNewProcessor($merchant)->verify($payment);

        return $data;
    }

    public function cancel($id, $input)
    {
        $this->trace->info(
            TraceCode::PAYMENT_CANCELLED,
            [
                'payment_id' => $id,
                'input'      => $input
            ]);

        $data = $this->getNewProcessor()->cancel($id, $input);

        return $data;
    }

    public function redirectCallback($id)
    {
        return $this->getNewProcessor()->redirectCallback($id);
    }

    public function redirectTo3ds($id)
    {
        return $this->getNewProcessor()->redirectTo3ds($id);
    }

    public function redirectToAuthorize($id)
    {
        $traceData = ['track_id' => $id];

        $this->trace->info(TraceCode::PAYMENT_REDIRECT_TO_AUTHORIZE_REQUEST, $traceData);

        $payment = null;

        try
        {
            list($merchant, $payment) = $this->setRequiredDetailsGetMerchantAndPaymentId($id);

            $response = $this->getResponseDataFromCache($payment);

            if ($response !== null)
            {
                return $response;
            }

            // cant do this before as mode is set in above, and mode is required to ensure data goes to write place
            $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_CREATE_REDIRECT_INITIATED, $payment, null, $traceData);

            $response = $this->getNewProcessor($merchant)->processRedirectToAuthorize($payment, $id);

            $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_CREATE_REDIRECT_PROCESSED, $payment, null, $traceData);

            $this->cacheResponseData($payment, $response);

            (new Payment\Analytics\Service())->updatePaymentAnalyticsData($payment);

            return $response;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::PAYMENT_REDIRECT_TO_AUTHORIZE_FAILURE,
                $traceData
            );

            $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_CREATE_REDIRECT_PROCESSED, $payment, $e, $traceData);

            throw $e;
        }
    }

    protected function getResponseDataFromCache($payment)
    {
        if ($payment->getAuthenticationGateway() !== Gateway::PAYSECURE)
        {
            return;
        }

        $key = $payment->getPaymentResponseCacheKey();

        $payload = $this->app['cache']->get($key);

        if (empty($payload) === true)
        {
            return;
        }

        $data = Crypt::decrypt($payload);

        return  $data;
    }

    protected function cacheResponseData($payment, $data)
    {
        if ($payment->getAuthenticationGateway() !== Gateway::PAYSECURE)
        {
            return;
        }

        $response = $this->app->razorx->getTreatment($payment->getMerchantId(), 'redirect_cache_response', Mode::LIVE);

        if (strtolower($response) !== 'on')
        {
            return;
        }

        $key = $payment->getPaymentResponseCacheKey();

        $payload = Crypt::encrypt($data);

        $this->app['cache']->put($key, $payload, Processor\Processor::REDIRECT_CACHE_RESPONSE_TTL);
    }

    //
    // Since, redirectToAuthorize is a direct auth we don't have any
    // merchant/auth/mode. We set merchant in basic auth and return
    // merchant and paymentId
    //
    protected function setRequiredDetailsGetMerchantAndPaymentId($id)
    {
        $key = Payment\Entity::getRedirectToAuthorizeTrackIdKey($id);

        $encryptedText = $this->app['cache']->get($key);

        if ($encryptedText === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED
            );
        }

        $payload = Crypt::decrypt($encryptedText);

        if (empty($payload) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED
            );
        }

        $this->app['basicauth']->setModeAndDbConnection($payload['mode']);

        $merchant = $this->repo->merchant->findOrFail($payload['merchant_id']);

        $this->trace->info(
            TraceCode::PAYMENT_REDIRECT_TO_AUTHORIZE_REQUEST_PAYLOAD,
            $payload
        );

        $this->app['basicauth']->setMerchant($merchant);

        $this->app['basicauth']->setAuthDetailsUsingPublicKey($payload['public_key']);

        if (empty($payload['account_id']) === false)
        {
            $this->app['basicauth']->authCreds->creds['account_id'] = $payload['account_id'];
        }

        if (empty($payload['oauth_client_id']) === false)
        {
            $this->app['basicauth']->setOAuthClientId($payload['oauth_client_id']);
        }

        $payment = $this->core->retrieveById($payload['payment_id']);

        return [$merchant, $payment];
    }

    public function forceAuthorizeFailed($id, $input)
    {
        $payment = $this->core->retrieveById($id);

        $merchant = $this->repo->merchant->fetchMerchantFromEntity($payment);

        $data = $this->getNewProcessor($merchant)
                     ->forceAuthorizeFailedPayment($payment, $input);

        return $data;
    }

    public function authorizeLockTimeOutPayments($paymentIds)
    {
        $paymentIds = explode(',', $paymentIds);

        $failurePayments = [];

        $successes = $failures = 0;

        $total = count($paymentIds);

        foreach ($paymentIds as $paymentId)
        {
            $payment = $this->repo->payment->findOrFail($paymentId);

            $merchant = $payment->merchant;

            try
            {
                $this->getNewProcessor($merchant)->forceAuthorizeFailedPayment($payment);
                $successes++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex);
                $failures++;
                $failurePayments[] = $paymentId;
            }
        }

        $data = [
            'success_count'     => $successes,
            'failure_count'     => $failures,
            'failure_payments'  => $failurePayments,
            'total'             => $total,
        ];

        $this->trace->info(
            TraceCode::FORCE_AUTHORIZE_TIMEOUT_PAYMENTS_RESPONSE,
            $data);

        return $data;
    }

    public function authorizeFailed($id)
    {
        $payment = $this->core->retrieveById($id);

        $merchant = $this->repo->merchant->fetchMerchantFromEntity($payment);

        $data = $this->getNewProcessor($merchant)->authorizeFailedPayment($payment);

        return $data;
    }

    public function fixAttemptedOrders($input)
    {
        $paymentIds = $input['payment_ids'];
        $success = 0;
        $failed  = 0;
        $failedPaymentIds = [];

        foreach ($paymentIds as $paymentId)
        {
            try
            {
                $payment = $this->repo->payment->findByPublicId($paymentId);

                $order = $payment->order;

                $merchant = $payment->merchant;

                if(($payment->isCaptured() === true) and ($order->isPaid() === false))
                {
                    $success = $this->repo->transaction(
                    function() use ($payment, $order, $merchant, $success)
                    {
                        $this->getNewProcessor($merchant)->fixAttemptedOrder($payment, $order);

                        $success++;

                        return $success;
                    });
                }
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException($ex);
                $failedPaymentIds[] = $paymentId;
                $failed++;
                continue;
            }
        }

        return [
            'success'          => $success,
            'failed'           => $failed,
            'failedPaymentIds' => $failedPaymentIds,
        ];

    }

    public function fixAuthorizeAt($input)
    {
        $paymentIds = $input['payment_ids'];

        $failurePayments = [];

        $successes = $failures = 0;

        $total = count($paymentIds);

        foreach ($paymentIds as $paymentId)
        {
            $payment = $this->core->retrieveById($paymentId);

            if (($payment->isFailed() === false) or
                ($payment->hasBeenCaptured() === true))
            {
                $failures++;
                $failurePayments[] = $paymentId;
                continue;
            }

            $this->trace->info(TraceCode::PAYMENT_AUTHORIZED_NULL, [
                'payment_id' => $paymentId,
                'old_authorized_at' => $payment->getAuthorizeTimestamp()
            ]);

            $payment->setAuthorizedAtNull();

            $this->repo->saveOrFail($payment);

            $successes++;
        }

        $data = [
            'success_count'     => $successes,
            'failure_count'     => $failures,
            'failure_payments'  => $failurePayments,
            'total'             => $total,
        ];

        return $data;
    }

    public function retrieveRefundByIdAndPaymentId($paymentId, $rfndId)
    {
        Payment\Entity::verifyIdAndStripSign($paymentId);
        Refund\Entity::verifyIdAndStripSign($rfndId);

        $refund = $this->repo->refund->fetchByIdPaymentIdMerchantId(
                                    $rfndId,
                                    $paymentId,
                                    $this->merchant->getKey());

        return $refund->toArrayPublic();
    }

    public function getCardForPayment($id)
    {
        $payment = $this->repo->payment->findByPublicIdAndMerchant($id, $this->merchant);

        if ($payment->hasCard() === false)
        {
            throw new Exception\BadRequestException(Error\ErrorCode::BAD_REQUEST_NOT_CARD_PAYMENT);
        }

        $card = $this->repo->card->fetchForPayment($payment);

        return $card->toArrayPublic();
    }

    public function retrieveRefundsForPayment($id)
    {
        $payment = $this->repo->payment->findByPublicIdAndMerchant($id, $this->merchant);

        $refunds = $this->repo->refund->findForPaymentAndMerchant($payment, $this->merchant);

        $refundsArray = $refunds->toArrayPublic();

        if ($this->app['basicauth']->isProxyAuth() === true)
        {
            (new Payment\Refund\Service())->addModeAndPublicStatus($refundsArray, $refunds);
        }

        return $refundsArray;
    }

    public function fetchTransactionByPaymentId($id)
    {
        $payment = $this->repo->payment->findByPublicIdAndMerchant($id, $this->merchant);

        if ($payment->hasBeenCaptured() === false)
        {
            throw new Exception\BadRequestException(
                Error\ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED
            );
        }

        $transaction = $this->repo->transaction->findByEntityId($payment->getId(), $this->merchant, true);

        return $transaction->toArrayPublic();
    }

    /**
     * Captures a payment
     *
     * @param string $id
     * @param array  $input
     *
     * @return Payment\Entity
     */
    public function capture($id, $input)
    {
        $payment = $this->repo->payment->findByPublicIdAndMerchant($id, $this->merchant);

        $payment = $this->getNewProcessor()->capture($payment, $input);

        return $payment->toArrayPublic();
    }

    /**
     * Captures payments in bulk
     *
     * @param  array  $input
     *
     * @return array
     */
    public function captureInBulk(array $input)
    {
        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_BULK_REQUEST,
            $input
        );

        (new Payment\Validator)->validateInput('bulk_capture', $input);

        $payments = $input['payment_ids'];

        $success = $failure = 0;

        $failurePayments = [];

        foreach ($payments as $paymentId)
        {
            try
            {
                $payment = $this->repo->payment->findByPublicId($paymentId);

                $merchant = $payment->merchant;

                $captureInput = [
                    Payment\Entity::AMOUNT   => $payment->getAmount(),
                    Payment\Entity::CURRENCY => $payment->getCurrency()
                ];

                $payment = $this->getNewProcessor($merchant)
                                ->capture($payment, $captureInput);

                $success++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::INFO,
                    TraceCode::PAYMENT_CAPTURE_BULK_FAILURE,
                    [
                        'payment_id' => $paymentId
                    ]);

                $failure++;

                $failurePayments[] = $paymentId;
            }
        }

        $data = [
            'count'            => count($payments),
            'success'          => $success,
            'failure'          => $failure,
            'failure_payments' => $failurePayments,
        ];

        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_BULK_RESPONSE,
            $data
        );

        return $data;
    }

    /**
     * Transfers a payment
     * /payment/:id/transfer
     *
     * @param string $id
     * @param array  $input
     *
     * @return array
     */
    public function transfer(string $id, array $input) : array
    {
        try
        {
            $transfers = $this->getNewProcessor()->transfer($id, $input);

            return $transfers->toArrayPublic();
        }
        catch (\Exception $e)
        {
            (new Transfer\Metric)->pushCreateFailedMetrics($e);

            throw $e;
        }
    }

    /**
     * Get Transfers for a payment_id
     *
     * @param  string $id   Payment ID
     * @return array
     */
    public function getTransfers(string $id) : array
    {
        Payment\Entity::verifyIdAndStripSign($id);

        $transferStatus = Transfer\Constant::FETCH_STATUS;

        $transfers = (new Transfer\Core())->getForPayment($id, $transferStatus);

        $payment = $this->repo
                        ->payment
                        ->findByIdAndMerchant($id, $this->merchant);

        if ($payment->hasOrder() === true)
        {
            $orderId = $payment->getApiOrderId();

            $transfersFromOrder = (new Transfer\Core())->getForOrder($orderId, $transferStatus);

            foreach ($transfersFromOrder as $transferFromOrder)
            {
                $transfers->push($transferFromOrder);
            }
        }

        return $transfers->toArrayPublic();
    }

    /**
     * Create a payout from a payment
     *
     * @param string    $id
     * @param array     $input
     *
     * @return array
     */
    public function payout(string $id, array $input) : array
    {
        $payout = $this->getNewProcessor()->payout($id, $input);

        return $payout->toArrayPublic();
    }

    /**
     * If a payment has been captured on gateway but not on the api side,
     * we create a transaction for the payment.
     *
     * @param $paymentId
     * @return array
     */
    public function verifyCapture($paymentId)
    {
        $payment = $this->repo->payment->findOrFail($paymentId);

        $merchantId = $payment->getMerchantId();

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $data = $this->getNewProcessor($merchant)->verifyCapture($payment);

        $this->trace->info(
            TraceCode::VERIFY_CAPTURE_RESPONSE,
            [
                'payment_id'    => $paymentId,
                'data'          => $data
            ]
        );

        return $data;
    }

    public function postPendingGatewayCapture($input)
    {
        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_BULK_REQUEST,
            $input
        );

        $limit = $input['limit'] ?? 100;

        if (isset($input['payment_ids']) === true)
        {
            (new Payment\Validator)->validateInput('bulk_capture', $input);

            $paymentIds = $input['payment_ids'];

            Entity::verifyIdAndStripSignMultiple($paymentIds);

            $payments = $this->repo->payment->findMany($paymentIds);
        }
        else
        {
            $from = Carbon::today(Timezone::IST)->subDays(8)->getTimestamp();
            $to = Carbon::today(Timezone::IST)->subDays(3)->getTimestamp();

            $payments = $this->repo->payment->fetchPendingCapturePaymentsBetweenTimestamps($from, $to, $limit);
        }

        $total = $payments->count();
        $success = 0;

        foreach ($payments as $payment)
        {
            $result = $this->getNewProcessor($payment->merchant)->manualGatewayCapture($payment);

            $success += intval($result);
        }

        return [
            'total'   => $total,
            'success' => $success
        ];
    }

    public function manualGatewayCapture($paymentId)
    {
        Entity::verifyIdAndSilentlyStripSign($paymentId);

        $payment = $this->repo->payment->findOrFail($paymentId);

        $result = $this->getNewProcessor($payment->merchant)->manualGatewayCapture($payment);

        return [
            'payment_id' => $payment->getId(),
            'result'     => $result
        ];
    }

    /**
     * After card enroll, bank redirects to us
     * and we send it to gateway for further
     * processing (auth).
     * Returning from this function implies
     * 'auth' is successful.
     *
     * @param  array  $input Contains fields provided
     *                       by bank
     *
     * @return array
     */
    public function callback($id, $hash, array $input)
    {
        return $this->getNewProcessor()->callback($id, $hash, $input);
    }

    public function s2sCallback($id, $input)
    {
        $payment = $this->repo->payment->findByPublicId($id);

        $merchant = $this->repo->merchant->fetchMerchantFromEntity($payment);

        // TODO: Hack to prevent S2S callback processing for TPV Merchants.
        // All TPV Merchant transactions will be made through BILLDESK.
        // Issue is currently on BILLDESK end. Remove once the fix has been
        // made from the BILLDESK side.
        if (($merchant->isTPVRequired() === true) and
            ($payment->isGateway(Payment\Gateway::BILLDESK) === true))
        {
            return ['success' => true];
        }

        return $this->getNewProcessor($merchant)->s2sCallback($payment, $input);
    }

    public function unexpectedCallback(array $input, string $referenceId, string $gateway)
    {
        $isProduction = ($this->app->environment('production') === true);

        // set mode for unexpected payments
        $mode = $isProduction ? Mode::LIVE : Mode::TEST;

        $this->app['basicauth']->setModeAndDbConnection($mode);

        // use demo accounts for unexpected payments
        $merchantId = $isProduction ? Merchant\Account::DEMO_PAGE_ACCOUNT : Merchant\Account::DEMO_ACCOUNT;

        $gatewayClass = $this->app['gateway']->gateway($gateway);

        $data = $gatewayClass->getParsedDataFromUnexpectedCallback($input);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_S2S_CALLBACK, [
            'data'          => $data,
            'gateway'       => $gateway,
            'reference_id'  => $referenceId,
            'unexpected'    => 1,
        ]);

        $terminal = $this->repo->terminal->findByGatewayAndTerminalData($gateway, $data['terminal']);

        if ($terminal->isDirectSettlement() === true)
        {
            $merchantId = $terminal->getMerchantId();
        }

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        return $this->getNewProcessor($merchant)
                    ->authorizePush($input, $referenceId, $data, $terminal);
    }

    public function fetchMultiple(array $input)
    {
        $merchantId = $this->merchant->getId();

        $payments = $this->repo->payment->fetch($input, $merchantId, true);

        return $payments->toArrayPublic();
    }

    public function fetch(string $id, array $input = []): array
    {
        $payment = $this->repo
                        ->payment
                        ->findByPublicIdAndMerchant($id, $this->merchant, $input);

        $entity = $payment->toArrayPublic();

        // Adding support to add additional params to payment entity for frontend
        if ($this->app['basicauth']->isProxyAuth() === true)
        {
            $this->addDashboardFlags($entity, $payment, $input);
        }

        return $entity;
    }

    protected function addDashboardFlags(array &$entity, $payment, array $input = [])
    {
        if (isset($input['dashboard_flag']) === true)
        {
            foreach ($input['dashboard_flag'] as $key)
            {
                $func = 'addDashboardFlag' . studly_case($key);

                if (method_exists($this, $func))
                {
                    $this->$func($entity, $payment);
                }
            }
        }
    }

    protected function addDashboardFlagInstantRefundSupport(array &$entity, $payment)
    {
        $entity[RefundConstants::INSTANT_REFUND_SUPPORT] = $this->getNewProcessor($this->merchant)
                                                                ->isInstantRefundSupported($payment);
    }

    public function getPaymentFlows(array $input)
    {
        $merchant = $this->merchant;

        (new Payment\Validator)->validateInput('get_flows', $input);

        $iinEntity = $this->repo->iin->find($input['iin']);

        $data = $merchant->getPaymentFlows($iinEntity);

        if (isset($input['order_id']) === true)
        {
            $order = $this->repo->order->findByPublicIdAndMerchant($input['order_id'], $this->merchant);

            if ($order->hasOffers() === true)
            {
                $payment = $this->getDummyPayment($order, $iinEntity);

                $applicableOffers = (new Offer\Core)->getApplicableOffersForPayment($order, $payment);

                $data['offers'] = $applicableOffers;
            }
        }

        return $data;
    }

    public function getPaymentFlowsPrivate(array $input)
    {
        (new Payment\Validator)->validateInput('post_flows', $input);

        $iin = null;

        if (isset($input['card_number']) === true)
        {
            $iin = substr($input['card_number'], 0, 6);
        }
        else if (isset($input['iin']) === true)
        {
            $iin = $input['iin'];
        }
        else
        {
            throw new Exception\BadRequestValidationFailureException('invalid input');
        }

        $input = ['iin' => $iin];

        return $this->getPaymentFlows($input);
    }

    /**
     * We only return the payment status in case of an async
     * payment + status being either of created or authorized
     *
     * Note: This will only work within 15 minutes of the payment creation
     *
     * @param $id
     * @return array
     * @throws Exception\BadRequestException
     */
    public function fetchStatus($id)
    {
        $data = $this->getNewProcessor()->getAsyncResponse($id);

        return $data;
    }

    public function addPaymentMetadata($id, $input)
    {
        $payment = $this->repo->payment->findByPublicIdAndMerchant($id, $this->merchant);

        $this->trace->info(TraceCode::PAYMENT_METADATA, $input);

        if (isset($input['otp_read']) === true)
        {
            $otpRead = $input['otp_read'];

            if ($payment->isMethodCardOrEmi() === false)
            {
                return [];
            }

            $card = $payment->card;

            $cardIin = $card->iin;
            $iin = $this->repo->iin->find($cardIin);

            if ($iin === null)
            {
                return [];
            }

            if ($otpRead === '1')
            {
                $iin->setOtpRead(true);
                $this->repo->saveOrFail($iin);
            }
            else if (($otpRead === '0') and
                     ($iin->getOtpRead() === true))
            {
                $this->trace->error(
                    TraceCode::PAYMENT_OTP_READ_FAILURE,
                    ['iin' => $cardIin, 'otp_read' => $otpRead]);
            }
        }

        return [];
    }

    public function update($id, $input)
    {
        $paymentId = Entity::verifyIdAndStripSign($id);

        $payment = $this->mutex->acquireAndRelease($paymentId,
            function() use ($paymentId, $input)
            {
                $payment = $this->repo->payment->findByIdAndMerchant($paymentId, $this->merchant);

                $payment->edit($input);

                $this->repo->saveOrFail($payment);

                return $payment;
            },
            20,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);

        return $payment->toArrayPublic();
    }

    /**
     * This method is triggered by a CRON job.
     *
     * Refunds all authorized (read extra) payments for paid orders.
     *
     * There is case when there will be authorized payment against paid order which
     * is mostly LATE_AUTHORIZED payments. Though those will get auto refunded
     * within 5 days (or set auto refund delay) but this CRON helps in refunding
     * those payments immediately.
     *
     * For optimization purposes we only pick payments in last 10 days. This picked
     * '10 days' is sufficient filter logically.
     */
    public function refundAuthorizedPaymentsOfPaidOrders()
    {
        $payments = $this->repo->payment->getAuthorizedPaymentsOfPaidOrderForRefund();

        $time = time();

        $failedPaymentIds = []; // Refund failed for these payments.

        foreach ($payments as $payment)
        {
            $paymentId = $payment->getId();
            $orderId   = $payment->getApiOrderId();

            $merchant  = $payment->merchant;

            $tracePayload = [
                'payment_id' => $paymentId,
                'order_id'   => $orderId,
            ];

            try
            {
                $this->getNewProcessor($merchant)->refundAuthorizedPayment($payment);

                $this->trace->info(TraceCode::ORDER_REFUNDED, $tracePayload);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::PAYMENT_AUTO_REFUND_FAILURE,
                    $tracePayload);

                $failedPaymentIds[] = $paymentId;
            }
        }

        $time = time() - $time;

        $summary = [
            'count'      => $payments->count(),
            'total_time' => $time . ' secs',
            'failed_ids' => $failedPaymentIds,
        ];

        $this->trace->info(TraceCode::ORDERS_MULTIPLE_AUTHORIZED_REFUNDS, $summary);

        $message = 'Authorized payments for paid orders refunded';
        $channel = Config::get('slack.channels.tech_logs');

        $this->slack->queue($message, $summary, ['channel' => $channel]);

        return $summary;
    }

    public function refundOldAuthorizedPayments()
    {
        //
        // Since we are taking 12 am of today,
        // we only need to subtract 4 days from today
        // to arrive at 5 days before.
        //
        $seconds = Merchant\Entity::AUTO_REFUND_DELAY_DEFAULT;

        $date = Carbon::today(Timezone::IST);
        $ts = $date->subSeconds($seconds)->getTimestamp();

        $payments = $this->repo->payment->getAuthorizedPaymentsBeforeTimestamp($ts, false);

        //
        // We fetch all the authorized payments eligible for refund.
        // Payments are identified on the basis of merchant auto_refund_delay
        // Maximum delay can be 10 days
        //
        $payments2 = $this->repo->payment->getAuthorizedPaymentsWithAutoRefundDelay();

        $payments = $payments->merge($payments2);

        $authorized = $payments->count();
        $refunded = 0;

        $timedOut = 0;
        $failed = 0;
        $error = 0;

        $time = time();

        $payments = $payments->shuffle();

        $this->removeEmandatePaymentsAsApplicable($payments);

        $this->trace->info(
            TraceCode::PAYMENT_AUTO_REFUND_CRON,
            [
                'count' => $authorized,
                'start_time' => $time
            ]);

        foreach ($payments as $payment)
        {
            try
            {
                assertTrue ($payment->isAuthorized() === true);

                $merchant = $payment->merchant;

                $refund = $this->getNewProcessor($merchant)
                               ->refundAuthorizedPayment($payment);

                $this->trace->info(
                    TraceCode::PAYMENT_AUTO_REFUND,
                    [
                        'payment_id'        => $payment->getId(),
                        'refund_id'         => $refund->getId(),
                        'auto_refund_delay' => $merchant->getAutoRefundDelay()
                    ]);

                $refunded++;
            }
            catch (Exception\GatewayTimeoutException $e)
            {
                $this->trace->info(
                    TraceCode::GATEWAY_REQUEST_TIMEOUT,
                    ['payment_id' => $payment->getId()]);

                // Just continue
                $timedOut++;
            }
            catch (Exception\GatewayErrorException $e)
            {
                $failed++;

                $this->trace->traceException($e, Trace::INFO, TraceCode::REFUND_EXCEPTION);

                // Now Just continue
            }
            catch (\Exception $e)
            {
                // @note: If payment refund fails due to any reason
                // other than expected ones, we should log it as an error
                // exception.
                //
                // If for eg, exception is BadRequestException, then it won't
                // get logged by global handler because it's not a critical
                // exception but in this context it really shouldn't have
                // occurred.

                $this->trace->traceException($e, Trace::INFO, TraceCode::REFUND_EXCEPTION);

                // Just continue
                $error++;
            }
        }

        $time = time() - $time;

        $results = array(
            'authorized'    => $authorized,
            'refunded'      => $refunded,
            'error'         => $error,
            'failed'        => $failed,
            'timed out'     => $timedOut,
            'total time'    => $time . ' secs');

        $message = 'Authorized payments refunded: ' . $refunded;

        $this->slack->queue($message, $results, ['channel' => Config::get('slack.channels.tech_logs')]);

        return $results;
    }

    public function notifyAuthorizedPayments()
    {
        $date = Carbon::yesterday(Timezone::IST);
        $timestamp = $date->getTimestamp();

        $payments = $this->repo->payment->getAuthorizedPaymentsBeforeTimestamp(
                            $timestamp);

        $count = $payments->count();

        if ($count !== 0)
        {
            $date->subDay(1);

            $message = 'Payment authorizations till ' .
                        $date->format('d-m-y') . ': ' . $count;

            $this->slack->queue($message, [], ['channel' => Config::get('slack.channels.tech_logs')]);
        }

        return ['count' => $count];
    }

    public function timeoutOldPayments(array $input)
    {
        $count = 0;

        $limit = $input['limit'] ?? 1000;

        $allMethods = Payment\Method::getAllPaymentMethods();

        foreach ($allMethods as $method)
        {
            $count = $count + $this->timeoutOldPaymentsForMethod($limit, $method);
        }

        return ['count' => $count];
    }

    public function timeoutOldPaymentsForMethod($limit, $method)
    {
        $count = 0;

        $error = 0;

        $startTime = microtime(true);

        // All Payments in created state will be marked as failed after 9 minutes
        $now = time();

        $timestamp = $now - Payment\Entity::PAYMENT_TIMEOUT_DEFAULT_OLD;

        $payments = $this->repo->payment->fetchOldCreatedPaymentsForMethodForTimeout($timestamp, $limit, $method);

        $total = count($payments);

        foreach ($payments as $payment)
        {
            if ($payment->shouldTimeout($now) === true)
            {
                $this->repo->transaction(function () use ($payment, & $count, & $error)
                {
                    $this->repo->payment->lockForUpdateAndReload($payment);

                    try
                    {
                        $this->getNewProcessor($payment->merchant)
                             ->setPayment($payment)
                             ->timeoutPayment();

                        $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_AUTHORIZATION_DROPPED, $payment);

                        $count++;

                    }
                    catch (\Throwable $e)
                    {
                        $this->trace->traceException($e);

                        $error++;
                    }
                });
            }
        }

        $this->trace->info(
            TraceCode::PAYMENT_TIMED_OUT,
            [
                'total'      => $total,
                'count'      => $count,
                'error'      => $error,
                'timestamp'  => time(),
                'time_taken' => microtime(true) - $startTime
            ]);

        return $count;
    }

    public function autoCaptureOldAuthorizedPayments()
    {
        $timeLowerLimit = Carbon::now()->subHour()->getTimestamp();

        $timeUpperLimit = Carbon::now()->subMinutes(5)->getTimestamp();

        $payments = $this->repo
                         ->payment
                         ->getAuthorizedAutoCapturePaymentsBetweenTimestamps(
                            $timeLowerLimit, $timeUpperLimit
                         );

        $success          = 0;
        $totalCount       = count($payments);
        $failedPaymentIds = [];

        foreach ($payments as $payment)
        {
            $this->merchant = $payment->merchant;

            try
            {
                $this->getNewProcessor()->autoCapturePaymentIfApplicable($payment);

                $success++;
            }
            catch (Exception\RecoverableException $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::WARNING,
                    TraceCode::PAYMENT_AUTO_CAPTURE_FAILED,
                    [
                        'step'          => 'auto_capture_authorized',
                        'payment_id'    => $payment->getId()
                    ]
                );

                $failedPaymentIds[] = $payment->getId();

                continue;
            }
        }

        $dataToTrace = [
            'count'              => $totalCount,
            'success_count'      => $success,
            'failed_payment_ids' => $failedPaymentIds
        ];

        $this->trace->info(TraceCode::PAYMENT_AUTO_CAPTURE_CRON, $dataToTrace);

        return $dataToTrace;
    }

    public function deliverAutoCaptureEmail()
    {
        $timeLowerLimit = Carbon::yesterday(Timezone::IST)->getTimestamp();
        $timeUpperLimit = Carbon::today(Timezone::IST)->getTimestamp();

        $payments = $this->repo->payment->getAutoCapturedPaymentsBetweenTimestamps(
                                                        $timeLowerLimit, $timeUpperLimit);

        $count = $payments->count();
        $emailCount = 0;
        $i = 0;

        while ($i < $count)
        {
            $autoCaptured = new Base\Collection;
            $merchantId = $payments[$i]->getMerchantId();
            $str = 'Payments with below Ids have been auto-captured:\n';

            while (($i < $count) and
                   ($payments[$i]->getMerchantId() === $merchantId))
            {
                $autoCaptured->push($payments[$i]->getPublicId());
                $str .= $payments[$i]->getPublicId() . '\n';
                $i++;
            }

            $merchant = $this->repo->merchant->findOrFail($merchantId);
            $this->app['mailgun']->sendAutoCaptureEmail($merchant->email, $str);
            $emailCount++;
        }

        return ['payments_count' => $count, 'emails_count' => $emailCount];
    }

    public function verifyAllPayments(array $input)
    {
        (new Payment\Validator)->validateInput('verify_all', $input);

        $gateway = $input['gateway'] ?? null;

        $delay = $input['delay'] ?? 0;

        $count = $input['count'] ?? 200;

        $end = Carbon::now(Timezone::IST)->subSeconds($delay)->getTimestamp();

        $start = $this->getStartTimestamp($delay);

        return (new Verify)->verifyAllPayments([$start, $end], $gateway, $count);
    }

    public function verifyPaymentsInBulk(array $input)
    {
        (new Payment\Validator)->validateInput('bulk_verify', $input);

        $paymentIds = Payment\Entity::verifyIdAndStripSignMultiple($input['payment_ids']);

        return (new Verify)->verifyPaymentsWithIds($paymentIds);
    }

    public function verifyMultiplePayments(string $filter, array $input)
    {
        (new Payment\Validator)->validateInput('verify', $input);

        $bucket = $input['bucket'] ?? [];

        $gateway = $input['gateway'] ?? null;

        return (new Verify)->verifyPaymentsWithFilter($filter, $bucket, $gateway);
    }

    public function verifyPayment($payment)
    {
        return (new Verify)->verifyPayment($payment);
    }

    public function sendReminderMerchantMailForAuthorizedPayments()
    {
        $result = [
            'initial'   => $this->sendReminderMerchantMailForAuthorizedPaymentsForSpecificDay(2, false),
            'final'     => $this->sendReminderMerchantMailForAuthorizedPaymentsForSpecificDay(4, true)
        ];

        $this->trace->info(TraceCode::PAYMENT_AUTHORIZE_REMINDER, $result);

        return $result;
    }

    public function sendReminderMerchantMailForAuthorizedPaymentsForSpecificDay($day, $final = false)
    {
        $result = [
            // This holds the counts
            'counts' => []
        ];

        // This is the start of the day 00:00, $day ago
        $start = Carbon::today(Timezone::IST)->subDays($day);
        $end   = Carbon::today(Timezone::IST)->subDays($day)->addDays(1);

        $to = $end->getTimestamp();
        $from = $start->getTimestamp();

        $result['from'] = (string) $start;
        $result['to']   = (string) $end;

        $authorizedPayments = $this->repo->payment->getAuthorizedPaymentsBetweenTimestamps($from, $to);

        $grouped = $authorizedPayments->groupBy(Payment\Entity::MERCHANT_ID);

        // Put the counts in for debug purposes
        $result['counts'] = [
            'payments'  => count($authorizedPayments),
            'merchants' => count($grouped),
            'failures'  => 0,
        ];

        foreach ($grouped as $merchantId => $payments)
        {
            // Send mail only if we have some payments
            if (count($payments) > 0)
            {
                try
                {
                    $this->sendAuthorizedPaymentsReminderMail(
                        $merchantId, $payments, $final);

                    $result['counts'][$merchantId] = count($payments);
                }
                catch (\Exception $ex)
                {
                    $this->trace->warning(TraceCode::PAYMENT_AUTHORIZE_REMINDER_FAILURE,
                        [
                            'merchant_id' => $merchantId,
                            'payments'    => count($payments),
                            'message'     => $ex->getMessage(),

                        ]);

                    $result['counts']['failures'] += 1;
                }
            }
        }

        return $result;
    }

    /**
     * Fetch and update on_hold flag for all payment
     * and source transfer with on_hold_until less than today's
     *
     * @param array $input
     * @return array
     */
    public function updateOnHold(array $input): array
    {
        $timestamp = Carbon::today(Timezone::IST)->getTimestamp();

        $paymentsToUpdate = $this->repo->payment->getPaymentsOnHoldBeforeTimestamp($timestamp);

        $this->trace->debug(
            TraceCode::PAYMENT_UPDATE_HOLD_CRON,
            [
                'step'          => 'fetch_payments',
                'ids_fetched'   => $paymentsToUpdate->getIds(),
                'timestamp'     => Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('d-m-Y H:i:s')
            ]
        );

        $cronSummary = [
            'total_count' => $paymentsToUpdate->count(),
            'failed_ids'  => []
        ];

        foreach ($paymentsToUpdate as $payment)
        {
            try
            {
                $this->repo->transaction(
                    function() use ($payment)
                    {
                        $this->setHoldFalse($payment);
                    });
            }
            catch (\Exception $e)
            {
                $cronSummary['failed_ids'][] = $payment->getId();

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::PAYMENT_UPDATE_HOLD_CRON,
                    [
                        'step'  => 'update_failed',
                        'id'    => $payment->getId()
                    ]
                );
            }
        }

        $this->trace->debug(TraceCode::PAYMENT_UPDATE_HOLD_CRON, ['step' => 'summary', 'summary' => $cronSummary]);

        $slackMessage = 'CRON: Payment set on_hold=false for elapsed on_hold_until';

        $slackChannel = Config::get('slack.channels.tech_logs');

        $this->slack->queue($slackMessage, $cronSummary, ['channel' => $slackChannel]);

        return [
            'success'   => true,
            'summary'   => $cronSummary
        ];
    }

    public function updateOnHoldBulkUpdate(array $input)
    {
       (new Payment\Validator)->validateInput('payment_onhold_bulk_update', $input);

       $onHold = $input['on_hold'];

       $paymentsToUpdate = $this->repo->payment->findManyByPublicIds($input['payment_ids']);

        $this->trace->info(
            TraceCode::PAYMENT_ON_HOLD_TOGGLE,
            [
                'payment_ids'   => $paymentsToUpdate->getIds(),
                'on_hold'       => $onHold,
            ]
        );

        $result = [
            'count' => $paymentsToUpdate->count(),
            'failed_ids' => [],
            'successful' => 0,
        ];

        $paymentCore = new Payment\Core;

        foreach ($paymentsToUpdate as $payment)
        {
            try
            {
                $merchant = $payment->merchant;

                $paymentCore->updatePaymentOnHold($payment, $onHold);

                $result['successful'] += 1;
            }
            catch (\Exception $e)
            {
                $result['failed_ids'][] = $payment->getId();

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::PAYMENT_ON_HOLD_TOGGLE_FAILED,
                    [
                        'step'  => 'update_failed',
                        'id'    => $payment->getId()
                    ]
                );
            }
        }

        return $result;
    }

    /**
     * Marks the payment as acknowledged, if not already acknowledged.
     * Also, updates payments.notes field with acknowledged data if any.
     *
     * @param string $paymentId
     * @param array  $input
     *
     * @throws Exception\BadRequestException
     */
    public function acknowledge(string $paymentId, array $input)
    {
        (new Payment\Validator)->validateInput('acknowledge', $input);

        $payment = $this->repo->payment->findByPublicIdAndMerchant($paymentId, $this->merchant);

        $this->getNewProcessor()->acknowledge($payment, $input);
    }

    public function updateReceiverData()
    {
        return $this->core->updateReceiverData();
    }

    /**
     * @param $input
     *
     * @return array
     * @throws Exception\GatewayErrorException
     * @throws Exception\RuntimeException
     */
    public function validateVpa($input)
    {
        $merchant = $this->merchant;

        /**
         * - Doing this for calls from FAVpaValidation Worker since merchant is not set in async processing
         * - Tried with basicauth but has related issues of repo null
         */
        if (($merchant === null) and (empty($input['merchant_id']) === false))
        {
            $merchant = $this->repo->merchant->findOrFail($input['merchant_id']);
            unset($input['merchant_id']);
        }

        $data = $this->getNewProcessor($merchant)->validateVpa($input);

        return $data;
    }

    public function validateEntity(array $input)
    {
        (new Payment\Validator())->validateInput('validate_entity', $input);

        $validator = Payment\Validation\Factory::build($input['entity']);

        $data = $validator->processValidation($input);

        return $data;
    }

    protected function setHoldFalse(Payment\Entity $payment)
    {
        $this->repo->payment->lockForUpdateAndReload($payment);

        $payment->setOnHold(false);

        $payment->setOnHoldUntil(null);

        $this->repo->saveOrFail($payment);

        $txn = $this->repo->transaction->lockForUpdate($payment->getTransactionId());

        $txn->setOnHold(false);

        $this->repo->saveOrFail($txn);

        //
        // If the payment has a transfer, update the
        // on_hold flag for the transfer as well
        //
        if ($payment->hasTransfer() === true)
        {
            $transfer = $this->repo->transfer->lockForUpdate($payment->getTransferId());

            $transfer->setOnHold(false);

            $transfer->setOnHoldUntil(null);

            $this->repo->saveOrFail($transfer);
        }
        // Temp: Payments can't have hold enabled right now without a linked transfer
        // Fail if no associated transfer. @todo - Remove this when payment hold is added\
        else
        {
            throw new Exception\LogicException(
                'Hold update attempted for payment with no transfer',
                null,
                [
                    'transaction_id'    => $txn->getId(),
                    'payment_id'        => $payment->getId(),
                ]);
        }
    }

    /**
     * Sends the authorized payments reminder email
     *
     * @param  string  $merchantId
     * @param  array   $payments
     * @param  boolean $final Whether this is the final payment reminder
     */
    protected function sendAuthorizedPaymentsReminderMail($merchantId, $payments, $final)
    {
        $merchant = (new Merchant\Entity)->findOrFail($merchantId);

        if ($merchant->isLinkedAccount() === true)
        {
            return;
        }

        $merchant = $merchant->toArray();

        $data = compact('merchant', 'payments', 'final');

        $authorizedPaymentsReminderMail = new AuthorizedPaymentsReminderMail($data);

        Mail::send($authorizedPaymentsReminderMail);
    }

    protected function getNewProcessor(Merchant\Entity $merchant = null)
    {
        if ($merchant === null)
        {
            $merchant = $this->merchant;
        }

        $processor = new Processor\Processor($merchant);

        return $processor;
    }

    protected function removeEmandatePaymentsAsApplicable(Base\PublicCollection & $payments)
    {
        $seconds = Merchant\Entity::AUTO_REFUND_DELAY_FOR_EMANDATE;

        $currentTime = Carbon::now(Timezone::IST);

        $ts = $currentTime->subSeconds($seconds)->getTimestamp();

        $payments = $payments->reject(function($payment) use ($ts)
        {
            if ($payment->isEmandate() === false)
            {
                return false;
            }

            if ($payment->getCreatedAt() <= $ts)
            {
                return false;
            }

            return true;
        });
    }

    private function getDummyPayment(Order\Entity $orderEntity, Card\IIN\Entity $iinEntity)
    {
        $payment = new Payment\Entity;

        $card = new Card\Entity;

        $payment->merchant()->associate($this->merchant);

        $paymentInput = $payment->getDummyPaymentArray(Payment\Method::CARD, null, $iinEntity->getNetworkCode());

        $payment->fill($paymentInput);

        $cardInput = $card->getDummyCardArray(null, $iinEntity);

        $card->fill($cardInput);

        $payment->card()->associate($card);

        return $payment;
    }

    public function generateAndSaveOneTimeTokenWithContact($input)
    {
        $cacheTtl = 15;

        $length = 14;

        $bytes = random_bytes($length / 2);

        $token = bin2hex($bytes);

        $key = Payment\Entity::getCardlessEmiOnetimeTokenCacheKey($token);

        $data = [
            Entity::CONTACT   => $input[Entity::CONTACT],
            Entity::PROVIDER  => $input[Entity::PROVIDER],
        ];

        if (isset($input['payment_id']) === true)
        {
            $data['payment_id'] = $input['payment_id'];
        }

        $this->app['cache']->put($key, $data, $cacheTtl);

        return $token;
    }

    public function migrateCardVaultToken(string $cardId, string $paymentId = null)
    {
        $updated = null;

        (new Card\Service)->migtateCardVaultToken($cardId);

        if ($paymentId !== null)
        {
            $payment = $this->repo->payment->find($paymentId);

            $card = $this->repo->card->find($cardId);

            $updated = (new Token\Core)->updatePaymentToken($payment, $card);

            if ($updated === true)
            {
                $this->repo->saveOrFail($payment);
            }
        }

        return $updated;
    }

    public function updateMerchantBalance(string $paymentId)
    {
        $payment = $this->repo->payment->find($paymentId);
        $transaction = $payment->transaction;

        if (($transaction === null) or
            ($transaction->isBalanceUpdated() === true))
        {
            return;
        }

        $this->getNewProcessor($payment->merchant)->updateMerchantBalance($payment, $transaction);
    }

    public function fetchForSubscription(string $paymentId, string $subscriptionId): array
    {
        $payment = $this->repo->payment->fetchByIdandSubscriptionId($paymentId, $subscriptionId);

        $payload = $payment->toArrayAdmin();

        $payload['merchant'] = [
            Merchant\Entity::BILLING_LABEL => $payment->merchant->getBillingLabel(),
            Merchant\Entity::WEBSITE       => $payment->merchant->getWebsite(),
            Merchant\Entity::EMAIL         => $payment->merchant->getTransactionReportEmail(),
        ];

        $payload['customer'] = [
            'email' => $payment->customer->getEmail(),
            'phone' => $payment->customer->getContact(),
        ];

        if ($payment->hasCard() === true)
        {
            $card = $payment->card;
            $expiryMonth = str_pad($card->getExpiryMonth(), 2, '0', STR_PAD_LEFT);

            $cardDetails = $card->toArrayPublic();

            $cardFormatted = [
                'number'  => '**** **** **** ' . $card->getLast4(),
                'expiry'  => $expiryMonth . '/' . $card->getExpiryYear(),
                'network' => $card->getNetworkCode(),
                'color'   => $card->getNetworkColorCode()
            ];

            $payload['card'] = array_merge($cardDetails, $cardFormatted);
        }

        if ($payment->hasInvoice() === true)
        {
            $payload['invoice'] = [
                Invoice\Entity::BILLING_START => $payment->invoice->getBillingStart(),
                Invoice\Entity::BILLING_END   => $payment->invoice->getBillingEnd()
            ];
        }

        return $payload;
    }

    // verify to fetch the payments between certain duration
    protected function getStartTimestamp(int $delay)
    {
        $delay = 3 * $delay;

        // keeping the min fetch window to 5 mins
        if ($delay < 300)
        {
            $delay = 300;
        }

        return Carbon::now(Timezone::IST)->subSeconds($delay)->getTimestamp();
    }

    public function paymentCardVaultMigrate($input)
    {
        (new Payment\Validator)->validateInput('payment_card_migrate', $input);

        $limit = $input['limit'] ?? 1000;

        $payments = $this->repo->payment->findPaymentsWithCardVault(Card\Vault::RZP_ENCRYPTION, $limit);

        $cardIds = $payments->pluck(Entity::CARD_ID)->toArray();

        $cards = $this->repo->card->findCardsWithVaultAndNoPayments(Card\Vault::RZP_ENCRYPTION, $limit, $cardIds);

        $this->trace->info(
            TraceCode::VAULT_TOKEN_MIGRATION_CRON_REQUEST,
            [
                'payments_count' => count($payments),
                'cards_count'    => count($cards),
            ]);

        $result = [
            'payments_count' => count($payments),
            'cards_count'    => count($cards),
            'payment_failed' => [],
            'card_failed'    => [],
        ];

        foreach ($payments as $payment)
        {
            try
            {
                $this->migrateCardDataIfApplicable($payment, $payment->card);
            }
            catch (\Throwable $e)
            {
                $result['payment_failed'][] = $payment->getId();
            }
        }

        foreach ($cards as $card)
        {
            try
            {
                $this->migrateCardDataIfApplicable(null, $card);
            }
            catch (\Throwable $e)
            {
                $result['card_failed'][] = $card->getId();
            }
        }

        return $result;
    }

    public function migrateCardDataIfApplicable($payment, $card)
    {
        $payload = [];

        try
        {
            $payload = [
                'card_id'    => $card->getId(),
                'token'      => $card->getVaultToken(),
                'mode'       => $this->mode,
            ];

            if ($payment !== null)
            {
                $payload['payment_id'] = $payment->getId();
            }

            $this->trace->info(
                TraceCode::VAULT_TOKEN_MIGRATION_CRON_REQUEST_INIT,
                [
                    'payload' => $payload,
                ]);

            Jobs\CardVaultMigrationJob::dispatch($payload, $this->mode);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::VAULT_TOKEN_MIGRATION_CRON_DISPATCH_FAILED,
                ['payment_id' => $payment->getId()]
            );

            throw $e;
        }
    }
}
