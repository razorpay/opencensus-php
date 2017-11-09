<?php

namespace RZP\Models\Payment\Processor;

use App;
use Route;
use Carbon\Carbon;
use RZP\Base\RepositoryManager;
use RZP\Constants\Mode;
use RZP\Dashboard\Dashboard;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Http;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\BankAccount;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Card;
use RZP\Models\Customer;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Merchant;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Plan\Subscription;
use RZP\Models\Plan\Subscription\Addon;
use RZP\Models\Payment\Processor\Notify;
use RZP\Models\Payment\Status;
use RZP\Models\Pricing;
use RZP\Models\Risk;
use RZP\Models\Terminal;
use RZP\Models\Transaction;
use RZP\Models\Transfer\Core as TransferCore;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Processor
{
    use Authorize;
    use Callback;
    use Capture;
    use Refund;
    use Verify;
    use OtpResend;
    use Topup;
    use FraudDetector;
    use Payout;
    use Reversal;
    use Transfer;

    /**
     * Callback urls can be hit multiple times by customers.
     * WIthin certain duration x minutes, we will return payment
     * success or failed when the url is hit again.
     * After that duration, we will simply throw
     * BAD_REQUEST_PAYMENT_ALREADY_PROCESSED payment_processed error.
     */
    const CALLBACK_PROCESS_AGAIN_DURATION = 20;

    /**
     * If payment fails on gateway then we may retry it with a different terminal/gateway.
     */
    const MAX_RETRY_ATTEMPTS = 5;

    /**
     * If a payment gets converted to authorized from failed after 15 minutes of creation of payment,
     * we do not send a notification to the customer.
     */
    const FAILED_TO_AUTHORIZED_NOTIFY_DURATION = 900;

    /**
     * Payment can be cancelled in multiple ways, one of which being
     * by closing the payment pop-up that.
     * However, we only allow payment to be cancelled within a certain duration.
     * A payment created today can only be cancelled within few minutes and
     * not on next day.
     */
    const PAYMENT_CANCEL_TIME_DURATION = 1800;  // 30 min * 60 sec

    /**
     * If a payment is async, it can receive a callback for 5 mins after which it is converted to a
     * failed payment
     */
    const ASYNC_PAYMENT_TIMEOUT = 300;

    /**
     * @var Merchant\Entity
     */
    protected $merchant;
    protected $trace;

    /**
     * @var Payment\Entity
     */
    protected $payment;

    /**
     * @var Terminal\Entity
     */
    protected $terminal;
    protected $selectedTerminals;
    protected $mode;
    /**
     * @var RepositoryManager
     */
    protected $repo;
    protected $orderRepo;
    protected $paymentRepo;
    protected $app;
    protected $mutex;
    protected $request;
    protected $methods;
    /**
     * @var Payment\Refund\Entity
     */
    protected $refund;
    /**
     * @var Order\Entity
     */
    protected $order;
    protected $segment;

    protected $verifyRefundStatus;

    /**
     * Api Route instance
     *
     * @var \RZP\Http\Route
     */
    protected $route;

    /**
     * @var \RZP\Http\BasicAuth\BasicAuth
     */
    protected $ba;

    public function __construct(Merchant\Entity $merchant)
    {
        $this->app  = App::getFacadeRoot();
        $this->trace = $this->app['trace'];
        $this->mode = $this->app['rzp.mode'];
        $this->repo = $this->app['repo'];

        $this->merchant = $merchant;
        $this->methods = $this->getMethodsForMerchant($merchant);

        $this->checkMerchantPermissions();

        $this->paymentRepo = $this->repo->payment;

        $this->orderRepo = $this->repo->order;

        $this->request = $this->app['request'];

        $this->mutex = $this->app['api.mutex'];

        $this->cache = $this->app['cache'];

        $this->route = $this->app['api.route'];

        $this->segment = $this->app['segment'];

        $this->ba = $this->app['basicauth'];

        // Only used in hdfc verify refund flow
        $this->verifyRefundStatus = null;
    }

    public function process(array $input): array
    {
        if (isset($input['method']) === false)
        {
            $input['method'] = Payment\Method::CARD;
        }
        else if (empty($input['method']) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Please provide appropriate payment method',
                Payment\Entity::METHOD);
        }

        $payment = $this->buildPaymentEntity($input);

        $ret = $this->preProcessPaymentInputs($input, $payment);

        if ($ret !== null)
        {
            return $ret;
        }

        $this->repo->transaction(function() use ($input, $payment)
        {
            $this->createPaymentEntity($input, $payment);
        });

        $payment = $this->payment;

        // This flow is being used for only hosted (Shopify).
        $this->checkSignature($input, $payment);

        return $this->authorize($payment, $input);
    }

    protected function preProcessPaymentInputs(array $input, $payment)
    {
        $coproto = null;

        if (($payment->isWallet() === true) and
            ((($payment->merchant->isPhoneOptional() === true) and
              ($payment->getContact() === Payment\Entity::DUMMY_PHONE)) or
             (($payment->merchant->isEmailOptional() === true) and
              ($payment->getEmail() === Payment\Entity::DUMMY_EMAIL))))
        {
            $coproto = [
                'type'    => 'wallet',
                'request' => [
                    'url'     => $this->route->getUrlWithPublicAuthInQueryParam('payment_create'),
                    'method'  => 'POST',
                    'content' => $input,
                ],
                'version' => '1',
            ];

            if ($payment->getContact() === Payment\Entity::DUMMY_PHONE)
            {
                $coproto['missing'][] = 'contact';
                unset($coproto['request']['content']['contact']);
            }

            if ($payment->getEmail() === Payment\Entity::DUMMY_EMAIL)
            {
                $coproto['missing'][] = 'email';
                unset($coproto['request']['content']['email']);
            }
        }

        return $coproto;
    }

    public function processAndReturnFees(array & $input)
    {
        if (isset($input['method']) === false)
        {
            $input['method'] = Payment\Method::CARD;
        }
        else if (empty($input['method']))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Please provide appropriate payment method',
                Payment\Entity::METHOD);
        }

        //
        // We only create a dummy payment entity for purpose
        // of pre-calculating fees and returning it.
        // It's not going to be saved in the database.
        //
        $payment = $this->buildPaymentEntity($input);

        // Performing dummy set of processing for the same
        $this->dummyPrePaymentAuthorizeProcessing($payment, $input);

        list($fee, $tax, $feesSplit) = (new Pricing\Fee)->calculateMerchantFees($payment);

        $data = array(
            'originalAmount'    => $input['amount'],
            'fees'              => $fee,
            'razorpay_fee'      => $fee - $tax,
            'serviceTax'        => $tax,
            'amount'            => $input['amount'] + $fee,
        );

        // Converts all the amounts to rupees
        foreach ($data as $key => $value)
        {
            $data[$key] = $value / 100;
        }

        // Set new input amount and fees
        $input['amount'] = $input['amount'] + $fee;

        $input['fee'] = $fee;

        return $data;
    }

    protected function checkSignature($input, $payment)
    {
        if (isset($input['signature']) === false)
        {
            return;
        }

        $payment->setSigned(true);

        if (isset($input['notes']['merchant_order_id']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'merchant_order_id field is required',
                'merchant_order_id');
        }

        $this->verifySignature($input, $payment);
    }

    protected function verifySignature($input, $payment)
    {
        $data = array(
            'amount'            => $payment->getAmount(),
            'currency'          => $payment->getCurrency(),
            'merchant_order_id' => $payment->getNotes()['merchant_order_id'],
        );

        $signature = $this->getSignature($data);

        // use hash_equals to prevent timing attacks
        if (hash_equals($signature, $input['signature']) !== true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Signature does not match', 'signature');
        }

        return true;
    }

    protected function getSignature(array $data)
    {
        ksort($data);

        $str = implode('|', $data);

        return $this->ba->sign($str);
    }

    protected function checkMerchantPermissions()
    {
        $merchant = $this->merchant;

        $mode = $this->mode;

        if ($mode === Mode::TEST)
        {
            return;
        }

        // On live request, ensure that merchant is activated
        if ($merchant->isActivated() === false)
        {
            throw new Exception\LogicException(
                'A non-activated merchant is making live request. Blasphemy!',
                null,
                [
                    'merchant_id' => $merchant->getId(),
                ]);
        }
    }

    protected function verifyMerchantIsLiveForLiveRequest()
    {
        // On live request, ensure that merchant isn't blocked temporarily
        if (($this->mode === Mode::LIVE) and
            ($this->merchant->isLive() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_LIVE_ACTION_DENIED);
        }
    }

    /**
     * Transfer a captured payment to customer/marketplace account
     *
     * @param  string $id    Payment ID
     * @param  array  $input Input Array
     * @throws Exception\BadRequestException
     */
    public function transfer(string $id, array $input)
    {
        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_REQUEST,
            ['payment_id' => $id, 'input' => $input]);

        $payment = $this->retrieve($id);

        $validator = $payment->getValidator();

        $validator->validateIsCaptured();

        $validator->validateInput('transfer', $input);

        return $this->mutex->acquireAndRelease(
            $payment->getId(),
            function() use ($payment, $input)
            {
                return $this->repo->transaction(function() use ($payment, $input)
                {
                    $transfers = (new TransferCore)->createForPayment(
                                    $payment,
                                    $input['transfers'],
                                    $this->merchant);

                    $this->trace->info(
                        TraceCode::PAYMENT_TRANSFER_SUCCESS,
                        ['transfer_ids' => $transfers->getIds()]);

                    return $transfers;
                });
            });
    }

    /**
     * Cancels a previously created payment
     *
     * @param  string $id Id of payment to be captured
     * @return  $status Payment\Status
     * @throws Exception\BadRequestException
     */
    public function cancel($id, $input)
    {
        $status = null;

        $payment = $this->retrieve($id);

        $diff = time() - $payment->getCreatedAt();

        if ($diff > self::PAYMENT_CANCEL_TIME_DURATION)
        {
            $this->segment->trackPayment($payment, ErrorCode::BAD_REQUEST_PAYMENT_CANNOT_BE_CANCELLED);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CANNOT_BE_CANCELLED);
        }

        $resource = $this->getCallbackMutexResource($payment);

        $response = $this->mutex->acquireAndRelease(
            $resource,
            function() use ($payment, $input)
            {
                // Reload in case it's processed by another thread.
                $this->repo->reload($payment);

                // If payment is not in created state, then that means
                // it's already been processed. It's possible that payment
                // may have succeeded. In such cases, we need to send back
                // exact same response as we would have if the payment succeeded
                if ($payment->isCreated() === false)
                {
                    return $this->processPaymentCallbackSecondTime($payment);
                }

                if (empty($input) === false)
                {
                    $this->trace->info(TraceCode::PAYMENT_CANCELLED_METADATA, (array) $input);
                }

                $errorCode = $this->repo->transaction(function() use ($payment, $input)
                {
                    $this->lockForUpdateAndReload($payment);

                    $errorCode = $this->cancelPayment($payment, $input);

                    return $errorCode;
                });

                throw new Exception\BadRequestException($errorCode);
            },
            60,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            20,
            1000,
            2000);

        return $response;
    }

    protected function cancelPayment($payment, $input)
    {
        $payment->getValidator()->cancelValidate($payment);

        $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER;

        if ((isset($input['_']['reason']) === true) and
            (is_string($input['_']['reason']) === true))
        {
            $this->payment->setCancellationReason($input['_']['reason']);
        }

        $e = new Exception\BadRequestException($errorCode);

        if ($payment->merchant->isFeatureEnabled(Feature::CREATED_FLOW))
        {
            $this->setPaymentError($e, TraceCode::PAYMENT_CANCELLED);
        }
        else
        {
            $this->updatePaymentFailed($e, TraceCode::PAYMENT_CANCELLED);
        }

        return $errorCode;
    }

    /**
     * Returns the proper async response for the status checks
     * made by Checkout
     *
     * @param  string $id payment id
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    public function getAsyncResponse($id)
    {
        $payment = $this->retrieve($id);

        $order = $this->getOrderForPayment($payment);

        $gateway = $payment->getGateway();

        // If the gateway is not async we just give a generic
        // error to not leak information
        if (Payment\Gateway::supportsAsync($gateway) === false)
        {
            // Throw exception of invalid id
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        // If it failed recently, then throw relevant exception
        // directly for the failure.
        $this->checkForRecentFailedPayment($payment);

        if ($payment->isCreated() === true)
        {
            // Throw payment failed exception if async payment timeout (5mins)
            // has been exceeded
            if ($payment->justCreated() === false)
            {
                $this->timeoutPayment();

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT);
            }

            return [
                Payment\Entity::STATUS => Payment\Status::CREATED
            ];
        }

        $resource = $this->getCallbackMutexResource($payment);

        $response = $this->mutex->acquireAndRelease(
            $resource,
            function() use ($payment)
            {
                // Reload in case it's processed by another thread.
                $this->repo->reload($payment);

                $diff = time() - $payment->getCreatedAt();

                if (($payment->hasBeenAuthorized() === true) and
                    ($diff < self::CALLBACK_PROCESS_AGAIN_DURATION * 60))
                {
                    return $this->postPaymentAuthorizeProcessing($payment);
                }

                $this->app['segment']->trackPayment($payment, ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED);
            },
            60,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            20,
            1000,
            2000);

        return $response;
    }

    public function callGatewayFunctionCaptureViaQueue($data, $payment)
    {
        $this->payment = $payment;

        $this->callGatewayFunction(Payment\Action::CAPTURE, $data);

        $payment->setGatewayCaptured(true);

        $this->repo->saveOrFail($payment);
    }

    protected function tracePaymentInfo($traceCode, $level = Trace::INFO)
    {
        $data = $this->payment->toArrayTraceRelevant();

        $this->trace->addRecord($level, $traceCode, $data);
    }

    public function timeoutPayment()
    {
        $payment = $this->payment;

        $traceCode = TraceCode::PAYMENT_TIMED_OUT;
        $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT;

        if ($payment->getInternalErrorCode() !== null)
        {
            $errorCode = $payment->getInternalErrorCode();

            $traceCode = TraceCode::PAYMENT_STATUS_FAILED;
        }

        $exception = new Exception\BadRequestException($errorCode);

        $this->updatePaymentFailed($exception, $traceCode);
    }

    protected function updatePaymentFailed($exception, $traceCode)
    {
        $error = $exception->getError();

        $code = $error->getPublicErrorCode();

        $desc = $error->getDescription();

        $internalCode = $error->getInternalErrorCode();

        $payment = $this->payment;

        $status = $payment->getStatus();

        $segmentCustomProperties = [
            'error'                 => $error,
            'code'                  => $code,
            'description'           => $desc,
            'internal_error_code'   => $internalCode,
            'status'                => $status
        ];

        $this->segment->trackPayment($payment, $traceCode, $segmentCustomProperties);

        if (($status !== Status::CREATED) and ($status !== Status::AUTHORIZED))
        {
            throw new Exception\LogicException(
                'Payment not in the appropriate status to be marked as failed.',
                null,
                [
                    'payment_id'    => $payment->getId(),
                    'status'        => $status
                ]);
        }

        $payment->setStatus(Payment\Status::FAILED);

        $this->trace->info(
            TraceCode::PAYMENT_STATUS_FAILED,
            [
                'payment_id'    => $payment->getId(),
                'old_status'    => $status,
                'error'         => $error,
                'segment_data'  => $segmentCustomProperties,
            ]
        );

        $payment->setError($code, $desc, $internalCode);

        $payment->setVerified(null);
        $payment->setVerifyBucket(0);

        $this->repo->saveOrFail($payment);

        $this->tracePaymentFailed($error, $traceCode);

        $this->eventPaymentFailed();

        if ($this->merchant->isFeatureEnabled(Feature::PAYMENT_FAILURE_EMAIL) === true)
        {
            $notifier = new Notify($this->payment);

            $notifier = $notifier->trigger(Payment\Event::FAILED);
        }
    }

    /**
     * Checks for risk failures and creates log in risk table
     *
     * @param        $payment Payment\Entity
     * @param string $internalErrorCode
     */
    public function logRiskFailureForGateway(
        Payment\Entity $payment,
        string $internalErrorCode)
    {
        $riskData = Risk\FailureCodeMap::getRiskDataForError($internalErrorCode);

        // If it is not error raised due to fraud failure, ignore everything
        if (empty($riskData) === true)
        {
            return;
        }

        $source = $riskData[Risk\Entity::SOURCE];

        (new Risk\Core)->logPaymentForSource($payment, $source, $riskData);
    }

    protected function setTwoFactorAuthAfterCallbackException(Exception\BaseException $exception)
    {
        $payment = $this->payment;

        // For Netbanking payments two_factor_auth was set to NOT_APPLICABLE on authorize itself
        if ($payment->isNetbanking() === true)
        {
            $twoFactorAuth = Payment\TwoFactorAuth::UNAVAILABLE;
        }
        else if (($exception instanceof Exception\GatewayErrorException) and
                 ($exception->hasTwoFaError()))
        {
            $twoFactorAuth = Payment\TwoFactorAuth::FAILED;
        }
        else
        {
            $twoFactorAuth = Payment\TwoFactorAuth::UNKNOWN;
        }

        $payment->setTwoFactorAuth($twoFactorAuth);
    }

    protected function eventPaymentFailed()
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $this->payment
        ];

        $this->app['events']->fire('api.payment.failed', $eventPayload);
    }

    protected function setPaymentError(Exception\BaseException $e, $traceCode)
    {
        $payment = $this->payment;

        $error = $e->getError();

        $internalCode = $error->getInternalErrorCode();

        $this->trace->info(
            $traceCode,
            [
                'payment_id'    => $payment->getId(),
                'status'        => $payment->getStatus(),
                'error'         => $error,
                'internalCode'  => $internalCode,
            ]
        );

        $payment->setInternalErrorCode($internalCode);

        $this->repo->saveOrFail($payment);
    }

    /**
     * Responsible for calling the gateway function
     *
     * @param  string $action      refund/capture etc.
     * @param  array  $gatewayData Relevant input for the corresponding
     *                             action
     *
     * @return array or null
     * @throws Exception\GatewayErrorException
     * @throws Exception\LogicException
     */
    protected function callGatewayFunction($action, array $gatewayData)
    {
        $terminal = $this->repo->terminal->fetchForPayment($this->payment);

        if ($terminal === null)
        {
            throw new Exception\LogicException(
                'Terminal should not be null here',
                null,
                ['payment_id' => $this->payment->getId()]);
        }

        $gateway = $this->payment->getGateway();

        $gatewayData['terminal'] = $terminal;

        $gatewayData['merchant'] = $this->payment->merchant;

        $eventCode = TraceCode::PAYMENT_CALL_GATEWAY_FUNC . '::' . strtoupper($action);

        // Do not track payment when Gateway verify is called
        if ($action !== Payment\Action::VERIFY)
        {
            $this->segment->trackPayment($this->payment, $eventCode, ['action' => $action]);
        }

        // Wrapping all gateway call, We can take actions on Exception here.
        try
        {
            return $this->app['gateway']->call($gateway, $action, $gatewayData, $this->mode, $terminal);
        }
        catch (Exception\GatewayErrorException $ex)
        {
            $error = $ex->getError();

            /*
             * If error is because of invalid terminal and terminal
             * used is direct, we can disable the terminal
             */
            if (($error->isInvalidTerminalError() === true) and
                ($terminal->isShared() === false))
            {
                $this->disableTerminal($terminal);
            }

            throw $ex;
        }

    }

    protected function createPaymentEntity(array $input, Payment\Entity $payment = null): Payment\Entity
    {
        $this->tracePaymentNewRequest($input);

        if ($payment == null)
        {
            $payment = $this->buildPaymentEntity($input);
        }

        // $this->segment->trackPayment($payment, TraceCode::PAYMENT_NEW_REQUEST);

        if ($this->merchant->isFeeBearerCustomer())
        {
            $this->verifyProvidedFee($payment, $input);
        }

        $this->addOrderIdToInputForSubscriptionIfApplicable($input, $payment);

        $this->validateAndSetOrderDetailsIfApplicable($payment, $input);

        $this->validateBankTransferDetailsIfApplicable($payment);

        $this->validateAndSetInvoiceDetailsIfApplicable($payment);

        $metadata = $payment->getMetadata();

        $this->trace->info(
            TraceCode::PAYMENT_METADATA,
            ['metadata' => $metadata, 'payment_id' => $payment->getId()]);

        if (isset($metadata['checkout_id']) === false)
        {
             $this->trace->warning(
                 TraceCode::PAYMENT_REQUEST_CHECKOUT_ID_NOT_FOUND,
                 ['metadata' => $metadata, 'payment_id' => $payment->getId()]);
        }

        $this->payment = $payment;

        return $payment;
    }

    /**
     * This is required when the first charge is done via auth transaction.
     * We need to use the invoice which was created during subscription
     * creation.
     * For the subsequent charges, this is handled since we send order_id as
     * part of the payment create request itself. Since, the payment is created internally.
     * The first charge (payment) is created by the merchant and hence not feasible to ask
     * them to send an order_id along with the subscription_id.
     *
     * @param array          $input
     * @param Payment\Entity $payment
     *
     * @throws Exception\BadRequestException
     */
    protected function addOrderIdToInputForSubscriptionIfApplicable(array & $input, Payment\Entity $payment)
    {
        if (isset ($input[Payment\Entity::SUBSCRIPTION_ID]) === false)
        {
            return;
        }

        $subscriptionId = $input[Payment\Entity::SUBSCRIPTION_ID];

        $subscription = $this->repo->subscription->findByPublicIdAndMerchant($subscriptionId, $this->merchant);

        //
        // In case the subscription is in active or halted state,
        // we don't want to add the order_id to the input.
        // 1. It would already be present if it's automated charge.
        // 2. Change card flow is being done. Hence, no invoice and stuff.
        //
        if ($subscription->isCreated() === true)
        {
            $this->addOrderIdToInputForCreatedSubscription($subscription, $input);
        }
        else
        {
            $cardChange = boolval($input[Subscription\Entity::SUBSCRIPTION_CARD_CHANGE] ?? false);

            if ($cardChange === true)
            {
                if ($subscription->isCardChangeStatus() === false)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_SUBSCRIPTION_CARD_CHANGE_NOT_ALLOWED,
                        null,
                        [
                            'subscription_id'       => $subscription->getId(),
                            'subscription_status'   => $subscription->getStatus(),
                        ]);
                }

                //
                // We do this because we are going to attempt to charge the invoice
                // directly along with card change.
                //
                if ($subscription->isPending() === true)
                {
                    $this->addOrderIdToInputForPendingSubscription($subscription, $input);
                }
            }
        }
    }

    protected function addOrderIdToInputForCreatedSubscription(Subscription\Entity $subscription, array & $input)
    {
        //
        // Invoice would have been created if:
        // - First charge needs to be done as part of authentication with or without addons
        // - Only addons need to be added, and no first charge needs to be done as part of authentication.
        //
        $subscriptionInvoices = $this->repo->invoice->fetchIssuedInvoicesOfSubscription($subscription);

        $subscriptionInvoicesCount = $subscriptionInvoices->count();

        if ($subscriptionInvoicesCount === 0)
        {
            return;
        }
        else if ($subscriptionInvoicesCount === 1)
        {
            $subscriptionInvoice = $subscriptionInvoices->first();

            $input[Payment\Entity::ORDER_ID] = Order\Entity::getSignedId($subscriptionInvoice->getOrderId());
        }
        else
        {
            throw new Exception\LogicException(
                'We should not have more than 1 issued invoice at this stage!',
                ErrorCode::SERVER_ERROR_TOO_MANY_SUBSCRIPTION_INVOICES_FOUND,
                [
                    'count'             => $subscriptionInvoicesCount,
                    'subscription_id'   => $subscription->getId(),
                    'invoices'          => $subscriptionInvoices->toArrayPublic()
                ]);
        }
    }

    protected function addOrderIdToInputForPendingSubscription(Subscription\Entity $subscription, array & $input)
    {
        $subscriptionInvoice = $this->repo->invoice->fetchLatestInvoiceOfPendingSubscription($subscription);

        $input[Payment\Entity::ORDER_ID] = Order\Entity::getSignedId($subscriptionInvoice->getOrderId());
    }

    protected function buildPaymentEntity(array $input): Payment\Entity
    {
        $payment = new Payment\Entity;

        $payment->generateId();

        $payment->merchant()->associate($this->merchant);

        $payment->build($input);

        $this->payment = $payment;

        return $payment;
    }

    /**
     * When customer is fee-bearer, the amount received from checkout is
     * inclusive of fees. (Fees is not received from checkout when
     * merchant is the fee bearer.
     * For a robust verification, we re-calculate the fees from the base
     * amount and verify that it's the same as received from checkout.
     *
     * @param Payment\Entity $payment
     * @param $input
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function verifyProvidedFee(Payment\Entity $payment, array $input)
    {
        // This is not needed because FeeCalculater:calculateFee()
        // calculates the actual amount (amount - fee) in case of feebearer merchant
        // $input['amount'] = $payment->getAmount() - $payment->getFee();

        // Re-calculates fees on the amount, using a dummy payment creation flow.
        // Also sets re-calculated fee and amount value (in paise) in $input.
        $feesArray = $this->processAndReturnFees($input);

        // The difference between the fees received from checkout and
        // and the fees re-calculated again. Ideally, this should be 0.
        $feeDifference = $input['fee'] - $payment->getFee();

        if (abs($feeDifference) > 5)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Payment failed because fees or tax was tampered');
        }
    }

    protected function fetchOrderFromInput(array $input): Order\Entity
    {
        $order = $this->orderRepo->findbyPublicId($input['order_id']);

        if ($order === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Order id provided not found.',
                'order_id');
        }

        if ($order->getMerchantId() !== $this->merchant->id)
        {
            // Merchant mismatch
            throw new Exception\BadRequestValidationFailureException(
                'Order id not found');
        }

        $order->merchant()->associate($this->merchant);

        return $order;
    }

    protected function validateAndSetOrderDetailsIfApplicable(
        Payment\Entity $payment,
        array $input)
    {
        if (empty($input[Payment\Entity::ORDER_ID]) === true)
        {
            if ($this->merchant->isTPVRequired() === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ID_REQUIRED,
                    Payment\Entity::ORDER_ID);
            }

            return;
        }

        $this->order = $this->fetchOrderFromInput($input);

        $this->order->getValidator()->validatePaymentCreation($payment);

        $this->order->setStatus(Order\Status::ATTEMPTED);

        $this->order->incrementAttempts();

        $this->trace->info(
            TraceCode::ORDER_STATUS_ATTEMPTED,
            [
                'order_id'      => $this->order->getId(),
                'attempts'      => $this->order->getAttempts(),
            ]);

        $this->repo->saveOrFail($this->order);

        $payment->order()->associate($this->order);
    }

    protected function validateAndSetInvoiceDetailsIfApplicable(Payment\Entity $payment)
    {
        if ($this->order === null)
        {
            return;
        }

        $invoice = $this->order->invoice()->withTrashed()->first();

        if ($invoice === null)
        {
            return;
        }

        $this->repo->invoice->lockForUpdateAndReload($invoice, true);

        $invoice->getValidator()->validateInvoicePayable();

        $payment->invoice()->associate($invoice);
    }

    protected function validateBankTransferDetailsIfApplicable(Payment\Entity $payment)
    {
        if (($payment->isBankTransfer() === true) and
            ($this->app['basicauth']->isAppAuth() === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid payment method given: ' . $payment->getMethod());
        }
    }

    protected function tracePaymentFailed($error, string $traceCode)
    {
        $traceData = array_merge(
                        $this->payment->toArrayTraceRelevant(),
                        ['error' => $error->getAttributes()]);

        $level = 'info';

        if ($error->isGatewayError())
        {
            $level = 'critical';
        }

        // Tracing
        $this->trace->$level(
            $traceCode,
            $traceData);

        $this->segment->trackPayment($this->payment, TraceCode::PAYMENT_FAILED, $traceData);
    }

    protected function retrieveToken(array $input)
    {
        $token = $this->repo->token->getByWalletTerminalAndCustomerId(
                            $input['payment']['wallet'],
                            $input['payment']['terminal_id'],
                            $input['customer']->getId());

        return $token;
    }

    protected function retrieve(string $id): Payment\Entity
    {
        $this->payment = $this->repo->payment->findByPublicIdAndMerchant(
                                                $id, $this->merchant);

        return $this->payment;
    }

    protected function getOrderForPayment(Payment\Entity $payment)
    {
        if ($payment->hasOrder())
        {
            $order = $this->repo->order->fetchForPayment($payment);

            return $order;
        }
    }

    /**
     * Sets both, the instance payment object and the passed
     * payment object, to the new payment object which is locked
     * for update.
     *
     * setRawAttributes is being used because of the way php
     * handles pass by reference for objects. If the passed object
     * is ASSIGNED to another object/value, the original object
     * from the calling function remains unaffected.
     * Any change ON the passed object will affect the original
     * object too.
     *
     * @param $payment
     */
    protected function lockForUpdateAndReload(Payment\Entity $payment)
    {
        $lockedPayment = $this->paymentRepo->lockForUpdate($payment->getKey());

        //
        // When $this->payment is being passed in the argument,
        // $this->payment will be the same object as $payment.
        // When $this->payment and $payment are two different objects,
        // we update both of them.
        //

        $this->payment->setRawAttributes($lockedPayment->getAttributes(), true);

        $payment->setRawAttributes($lockedPayment->getAttributes(), true);
    }

    public function setPayment(Payment\Entity $payment): Processor
    {
        $this->payment = $payment;

        return $this;
    }

    protected function tracePaymentNewRequest(array $input)
    {
        $this->unsetSensitiveCardDetails($input);

        $this->trace->debug(TraceCode::PAYMENT_NEW_REQUEST, $input);
    }

    protected function unsetSensitiveCardDetails(array & $input)
    {
        if ((isset($input['card'])) and
            (is_array($input['card'])))
        {
            unset($input['card'][Card\Entity::CVV]);
            unset($input['card'][Card\Entity::NUMBER]);
        }
    }

    protected function notifyDashboard($type, $entity)
    {
        Dashboard::send($type, $entity);
    }

    protected function getMerchantBankAccount(Merchant\Entity $merchant): BankAccount\Entity
    {
        $ba = $merchant->bankAccount;

        if ($ba !== null)
        {
            return $ba;
        }

        assert ($this->mode === Mode::TEST);

        $attributes = array(
            'merchant_id'           => $merchant->getId(),
            'ifsc_code'             => BankAccount\Entity::SPECIAL_IFSC_CODE,
            'beneficiary_name'      => $merchant->getAttribute('name'),
            'account_number'        => random_integer(11),
            'beneficiary_city'      => 'Mumbai',
            'beneficiary_state'     => 'MH',
            'beneficiary_country'   => 'IN',
            'beneficiary_pin'       => '400069',
            'beneficiary_mobile'    => '9393993939',
        );

        $ba = (new BankAccount\Entity)->newInstance($attributes, true);

        $ba->merchant()->associate($merchant);

        $merchant->setRelation('bankAccount', $ba);

        return $ba;
    }

    protected function shouldAutoCapture(Payment\Entity $payment): bool
    {
        // Bank transfers are auto-captured only if they are expected. This is checked later.
        if ($payment->isBankTransfer() === true)
        {
            return false;
        }

        //
        // We do an auto capture only if payment is associated with an order.
        //
        if ($payment->hasOrder() === false)
        {
            return false;
        }

        //
        // The payment should always be in authorized if it has reached this point.
        // Ideally, this should throw an exception. But, we do not want to fail
        // the payment because of an internal issue.
        //
        if ($payment->isAuthorized() === false)
        {
            $this->trace->error(
                TraceCode::PAYMENT_AUTO_CAPTURE_NOT_AUTHORIZED,
                [
                    'payment_id'    => $payment->getId(),
                    'status'        => $payment->getStatus()
                ]);

            return false;
        }

        //
        // The flow would reach till here because subscription creates
        // an invoice, which in turn creates an order.
        //
        // Auto capturing a subscription payment is handled in a different
        // flow, because of some pre-processing and post-processing
        // that requires to be done.
        //
        if ($payment->hasSubscription() === true)
        {
            return false;
        }

        return $this->shouldAutoCaptureOrder($payment);
    }

    protected function shouldAutoCaptureAlreadyAuthenticatedSubscription(Payment\Entity $payment)
    {
        $subscription = $payment->subscription;

        //
        // Late authorizations are not going to happen here because
        // everything is S2S. On the off chance that it happens,
        // we log it and see what to do about it.
        //
        if ($payment->isLateAuthorized() === true)
        {
            $this->trace->critical(
                TraceCode::SUBSCRIPTION_LATE_AUTH_NO_AUTO_CAPTURE,
                [
                    'payment_id' => $payment->getId(),
                    'subscription_id' => $subscription->getId(),
                    'late_authorized' => $payment->isLateAuthorized(),
                ]);

            return false;
        }

        return true;
    }

    protected function shouldAutoCaptureNewSubscription(Payment\Entity $payment)
    {
        $subscription = $payment->subscription;

        //
        // This is commented out because all the attributes set
        // for the subscription and not saved will get overridden
        // with the values present in the DB.
        // TODO: Handle this because race conditions.
        //
        // $this->repo->reload($subscription);

        //
        // This function can be used here since this flow is processed
        // only for a new subscription.
        //
        $subscriptionInvoices = $this->repo->invoice->fetchIssuedInvoicesOfSubscription($subscription);

        //
        // We auto capture a subscription only if start_at is absent,
        // which means that the first transaction is being used as the
        // first charge also.
        // OR we auto capture if upfront_amount (addon) is present.
        //
        // We create an invoice if any of the above two conditions are satisfied.
        //
        if ($subscriptionInvoices->count() === 0)
        {
            return false;
        }

        if ($subscriptionInvoices->count() > 1)
        {
            throw new Exception\LogicException(
                'There should have been only one invoice created for a newly created subscription',
                ErrorCode::SERVER_ERROR_INCORRECT_NUMBER_OF_INVOICES_FOUND,
                [
                    'invoices_count'    => $subscriptionInvoices->count(),
                    'subscription_id'   => $subscription->getId(),
                    'payment_id'        => $payment->getId(),
                ]);
        }

        //
        // Ideally, the flow shouldn't reach till here since this function
        // is not called at all in case of a late auth payment.
        //
        if ($payment->isLateAuthorized() === true)
        {
            $this->trace->critical(
                TraceCode::SUBSCRIPTION_LATE_AUTH_NO_AUTO_CAPTURE,
                [
                    'payment_id'      => $payment->getId(),
                    'subscription_id' => $subscription->getId(),
                    'late_authorized' => $payment->isLateAuthorized(),
                ]);

            return false;
        }

        return true;
    }

    protected function shouldAutoCaptureOrder(Payment\Entity $payment)
    {
        $order = $payment->order;

        //
        // Assume a case where the first payment failed.
        // The second payment is getting authorized.
        // The first payment is now getting late authorized.
        // If the second payment gets captured, we need to ensure that we don't capture
        // the first payment. We do a reload here to ensure that we get the latest
        // status of the order before marking the payment as captured.
        // An order must not have more than one captured payment.
        //
        $this->repo->reload($order);

        if (($order->isPaid() === true) or
            ($order->getPaymentCapture() === false))
        {
            return false;
        }

        if ($payment->isLateAuthorized() === true)
        {
            return $this->shouldAutoCaptureLateAuthorized($payment);
        }

        return true;
    }

    protected function shouldAutoCaptureLateAuthorized(Payment\Entity $payment): bool
    {
        $merchant = $payment->merchant;

        $autoRefundDelay = $merchant->getAutoRefundDelay();

        $createdAt = $payment->getCreatedAt();

        $shouldRefundAt = $createdAt + $autoRefundDelay;

        $currentTime = Carbon::now()->getTimestamp();

        $this->trace->info(
            TraceCode::LATE_AUTHORIZE_AUTO_CAPTURE,
            [
                'payment_id'        => $payment->getId(),
                'status'            => $payment->getStatus(),
                'refund_delay'      => $autoRefundDelay,
                'should_refund_at'  => $shouldRefundAt,
                'current_time'      => $currentTime,
            ]);

        //
        // If the payment is supposed to get refunded by now,
        // do not auto capture it.
        //
        if ($currentTime > $shouldRefundAt)
        {
            return false;
        }

        // Auto capturing a late authorized invoice has a little different logic.
        // Later, we would add logic for auto capturing a payment which is not
        // associated with an invoice also.
        if ($payment->hasInvoice())
        {
            return $this->shouldAutoCaptureLateAuthorizedInvoice($payment);
        }

        return $this->shouldAutoCaptureLateAuthorizedOrder($merchant);
    }

    /**
     * The merchant needs to have `auto_capture_late_auth` config set to true.
     *
     * @param Merchant\Entity $merchant
     *
     * @return bool
     */
    protected function shouldAutoCaptureLateAuthorizedOrder(Merchant\Entity $merchant)
    {
        return $merchant->getAutoCaptureLateAuth();
    }

    /**
     * Invoice related checks
     *   - Check if invoice status is ISSUED
     *
     * @param Payment\Entity $payment
     *
     * @return bool
     */
    protected function shouldAutoCaptureLateAuthorizedInvoice(Payment\Entity $payment)
    {
        $invoice = $payment->invoice;

        $this->repo->invoice->lockForUpdateAndReload($invoice);

        //
        // There could be a case where the current time is greater
        // than the expire_by of the invoice. But, if we haven't
        // yet marked the invoice as expired, we still go ahead
        // and capture the payment.
        //

        if ($invoice->isIssued() === false)
        {
            $this->trace->debug(
                TraceCode::INVOICE_PAYMENT_AUTO_CAPTURE_NOT_ALLOWED,
                [
                    'payment_id'        => $payment->getId(),
                    'status'            => $payment->getStatus(),
                    'invoice_id'        => $invoice->getId(),
                    'invoice_status'    => $invoice->getStatus(),
                ]);

            return false;
        }

        return true;
    }

    protected function acquireMutexOnPayment(Payment\Entity $payment)
    {
        $resource = $payment->getId();

        if ($this->mutex->acquire($resource) === false)
        {
            $data = [
                'payment_id'  => $payment->getId(),
                'merchant_id' => $payment->getMerchantId(),
                'gateway'     => $payment->getGateway(),
                'terminal_id' => $payment->getTerminalId(),
            ];

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS, null, $data);
        }
    }

    protected function releaseMutexOnPayment(Payment\Entity $payment)
    {
        $this->mutex->release($payment->getId());
    }

    protected function createOrUpdateToken(array $input, array $data): Customer\Token\Entity
    {
        $token = $this->retrieveToken($input);

        if ($token === null)
        {
            $token = (new Customer\Token\Core)
                        ->create($input['customer'], $data['token']);
        }
        else
        {
            $token->fill($data['token']);
            $token->saveOrFail();
        }

        return $token;
    }

    protected function getFormattedContact(string $contact): string
    {
        return substr($contact, -10);
    }

    public function saveFeeDetails(Transaction\Entity $txn, PublicCollection $feesSplit)
    {
        $this->trace->info(
            TraceCode::CREATING_FEES_BREAKUP,
            [
                'transaction_id'    => $txn->getId(),
                'payment_id'        => $txn->getEntityId(),
                'fee_split'         => $feesSplit->toArrayPublic(),
            ]);

        try
        {
            $this->repo->transaction(function() use ($txn, $feesSplit)
            {
                foreach ($feesSplit as $feeSplit)
                {
                    $feeSplit->transaction()->associate($txn);

                    $this->repo->saveOrFail($feeSplit);
                }

                $this->trace->info(
                    TraceCode::FEES_BREAKUP_CREATED,
                    [
                        'transaction_id'    => $txn->getId(),
                        'payment_id'        => $txn->getEntityId(),
                        'fee_split'         => $feesSplit->toArrayPublic(),
                    ]);
            });
        }
        catch (Exception\BaseException $ex)
        {
            $this->trace->info(
                TraceCode::FEES_BREAKUP_CREATION_FAILED,
                [
                    'transaction_id'    => $txn->getId(),
                    'payment_id'        => $txn->getEntityId(),
                    'fee_split'         => $feesSplit->toArrayPublic(),
                    'message'           => $ex->getMessage(),
                ]);

            throw new Exception\LogicException(
                'Error while recording fee breakup',
                ErrorCode::BAD_REQUEST_FEE_BREAKUP_CREATION_FAILED,
                [
                    'transaction_id'    => $txn->getId(),
                    'payment_id'        => $txn->getEntityId(),
                    'fee_split'         => $feesSplit->toArrayPublic(),
                ]);
        }

    }

    protected function getMethodsForMerchant(Merchant\Entity $merchant)
    {
        if ($merchant->hasRelation('methods') === false)
        {
            $methods = $this->repo->methods->getMethodsForMerchant($merchant);
        }

        return $merchant->methods;
    }

    protected function shouldHitGateway(Payment\Entity $payment)
    {
        if ($payment->isFileBasedEmandateDebitPayment() === true)
        {
            //
            // If the payment is a second recurring payment of a file-based emandate bank
            // we do not hit the gateway, we send a debit request asynchronously
            //
            return false;
        }

        if ($payment->isBankTransfer() === true)
        {
            return false;
        }

        if ($payment->getGateway() === Payment\Gateway::BHARAT_QR)
        {
            return false;
        }

        //
        // TODO: route check to be changed after refactor
        //
        // If this is hit while creating a payment, gateway would not have been set yet.
        // Hence, gateway check in the previous block would not work.
        // This function is hit in the refund flow also, in which the gateway
        // would have been set already.
        // The gateway would be set AFTER the payment is created and processed.
        //
        if (Route::currentRouteName() === 'bharat_qr_payment_process')
        {
            return false;
        }

        return true;
    }

    protected function disableTerminal(Terminal\Entity $terminal)
    {
        $this->trace->error(
            TraceCode::TERMINAL_AUTO_DISABLE,
            [
                'merchant_id'           => $terminal->getMerchantId(),
                'terminal_id'           => $terminal->getId(),
            ]
        );

        $terminal->setEnabled(false);

        $this->repo->saveOrFail($terminal);
    }
}
