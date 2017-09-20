<?php

namespace RZP\Models\Payment\Processor;

use App;
use Carbon\Carbon;
use Config;
use Crypt;
use Lib\PhoneBook;
use Mail;
use RZP\Constants\TLD;
use RZP\Constants\Mode;
use RZP\Http\BasicAuth;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Plan\Subscription;
use RZP\Error;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Admin;
use RZP\Models\Card;
use RZP\Models\Card\IIN;
use RZP\Models\Customer;
use RZP\Models\Customer\Token;
use RZP\Models\Emi;
use RZP\Models\Currency;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Methods;
use RZP\Models\Offer;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Payment\Action;
use RZP\Models\Payment\Analytics;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\TwoFactorAuth;
use RZP\Models\Payment\TerminalAnalytics;
use RZP\Models\Pricing;
use RZP\Models\Risk;
use RZP\Models\Terminal;
use RZP\Models\Transaction;
use RZP\Models\Customer\GatewayToken;
use RZP\Models\Upi;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

trait Authorize
{
    /**
     * There are different ways of doing payment authorization.
     */
    protected $type;

    /**
     * @param Payment\Entity $payment
     * @param array $input
     * @return array
     */
    public function authorize(Payment\Entity $payment, array $input): array
    {
        $this->verifyMerchantIsLiveForLiveRequest();

        $gatewayInput = [];

        // $gatewayInput is being passed by reference.
        // Adds callback url, payment and card info to $gatewayInput
        $this->runPaymentMethodRelatedPreProcessing($payment, $input, $gatewayInput);

        // this needs to be done after we have card entity as we need to know if
        // cards used in payment is international
        $this->processCurrencyConversions($payment);

        $this->runPaymentInputValidations($payment, $input);

        $this->validateOfferIfApplicable($payment);

        $ret = $this->hitGatewayIfRequired($payment, $input, $gatewayInput);

        if ($ret !== null)
        {
            return $ret;
        }

        return $this->processAuth($payment);
    }

    protected function hitGatewayIfRequired(Payment\Entity $payment, array $input, array $gatewayInput)
    {
        if ($this->shouldHitGateway($payment) === false)
        {
            $this->repo->saveOrFail($payment);

            return null;
        }

        $this->selectedTerminals = (new TerminalProcessor)->getTerminalsForPayment($payment);

        $request = $this->authorizeAcrossTerminals($payment, $input, $gatewayInput);

        $this->createAnalyticsLog($payment);

        //
        // If $request is not null, then payment is two-step process
        // where client needs to provide additional info via his browser.
        //
        if ($request !== null)
        {
            return $this->getPaymentGatewayRequestData($request, $payment);
        }

        return null;
    }

    protected function authorizeAcrossTerminals(Payment\Entity $payment, array $input, array $gatewayInput)
    {
        $totalTerminals = count($this->selectedTerminals);

        $maxRetryAttempts = min($totalTerminals, self::MAX_RETRY_ATTEMPTS);

        $retryAttempts = 0;

        $request = [];

        $retry = false;

        //
        // We are attempting to rotate across multiple terminals to get a successful payment here.
        // For each of the terminals tried, we want to record the terminal metrics using recordTerminalAudit()
        // At the end of a successful/failed payment, we want to record the payment details
        // using createAnalyticsLog. There could be cases where terminal #1 failed and terminal #2 succeeded.
        // In the above scenario, we will have 2 records in terminal analytics, but only one record
        // for the entire payment in payment analytics. The terminal chosen here in payment analytics
        // will be the last terminal tried.

        while ($retryAttempts < $maxRetryAttempts)
        {
            $currentTerminal = $this->selectedTerminals[$retryAttempts];

            $payment->associateTerminal($currentTerminal);

            $terminalGatewayInput = $gatewayInput;

            $this->runPostGatewaySelectionPreProcessing($payment, $terminalGatewayInput);

            $segmentCustomProps = [
                'selected_terminal' => $currentTerminal->getId(),
                'retry_attempt' => $retryAttempts
            ];

            $this->segment->trackPayment($payment, TraceCode::GATEWAY_POSTPROCESSING, $segmentCustomProps);

            // data for terminal analytics
            $terminalData = [
                'payment_id'    => $payment['id'],
                'input'         => $input,
                'terminal_id'   => $payment['terminal_id'],
                'start'         => microtime(true),
            ];

            try
            {
                if ($this->canRunOtpPaymentFlow($payment) === true)
                {
                    $request = $this->callGatewayFunction(Action::OTP_GENERATE, $terminalGatewayInput);
                }
                else
                {
                    $request = $this->callGatewayAuthorize($terminalGatewayInput);
                }

                $retry = false;

                break;
            }
            catch (Exception\GatewayRequestException $e)
            {
                // record a failed payment for given terminal and continue
                $terminalData['exception'] = $e;

                $retryAttempts++;

                $retry = $this->logAndCheckForAuthRetry($e, $payment);

                if (($retry === true) and
                    ($retryAttempts < $maxRetryAttempts))
                {
                    continue;
                }

                $this->updatePaymentAuthFailedAndThrowException($e);
            }
            catch (Exception\BaseException $e)
            {
                //
                // An error occurred on gateway due to user or gateway.
                // We need to record this and mark payment as failed.
                //
                $terminalData['exception'] = $e;

                $this->updatePaymentAuthFailed($e);

                $internalErrorCode = $payment->getInternalErrorCode();

                $this->logRiskFailureForGateway($payment, $internalErrorCode);

                throw $e;
            }
            finally
            {
                $terminalData['end'] = microtime(true);

                $this->recordTerminalAudit($terminalData, $payment, $retryAttempts);
            }
        }

        return $request;
    }

    protected function logAndCheckForAuthRetry($e, $payment): bool
    {
        $traceData = array(
            'error_code'    => $e->getCode(),
            'message'       => $e->getMessage(),
            'payment_id'    => $payment->getId(),
            'terminal_id'   => $payment->terminal->getId()
        );

        $this->trace->info(TraceCode::TERMINAL_FAILURE, $traceData);

        $this->segment->trackPayment($payment, TraceCode::TERMINAL_FAILURE, $traceData);

        // retry only if it is safe to do so
        return ((property_exists($e, 'safeRetry') === true) and
                ($e->getSafeRetry() === true));
    }

    protected function updatePaymentAuthFailedAndThrowException(Exception\BaseException $e)
    {
        $this->updatePaymentAuthFailed($e);

        throw $e;
    }

    protected function updatePaymentAuthFailed(Exception\BaseException $e)
    {
        $this->updatePaymentFailed($e, TraceCode::PAYMENT_AUTH_FAILURE);

        $this->createAnalyticsLog($this->payment);
    }

    protected function verifyFeesLessThanAmount(Payment\Entity $payment)
    {
        // try calculating the fees, throws exception if fees is more than amount

        list($fee, $tax, $feesSplit) = (new Pricing\Fee)->calculateMerchantFees($payment);
    }

    /**
     * @param Payment\Entity $payment
     *
     * @return array
     */
    protected function processAuth(Payment\Entity $payment): array
    {
        $this->updateAndNotifyPaymentAuthorized();

        $this->updateTwoFactorAuthForOneStepPayment();

        $payment = $this->payment;

        return $this->postPaymentAuthorizeProcessing($payment);
    }

    protected function getOtpPaymentCreatedResponse($request, $payment)
    {
        $payment->incrementOtpCount();

        $this->repo->save($payment);

        // TODO: Return metadata in a better format
        $response = [
            'type'       => 'otp',
            'request'    => $request,
            'version'    => 1,
            'payment_id' => $payment->getPublicId(),
            'gateway'    => $this->getEncryptedGatewayText($payment->getGateway()),
            'contact'    => $payment->getContact(),
            'amount'     => number_format(($payment->getAmount() / 100), 2),
            'wallet'     => $payment->getWallet()
        ];

        // This is a hack to return direct method for IVR payments
        if ($payment->isCard() === true)
        {
            $templateData = [
               'data' => $response,
               'cdn'  => $this->config->get('url.cdn.production')
            ];

            $content = View::make('gateway.gatewayOtpPostForm')
                            ->with('data', $templateData)
                            ->render();

            $response = [
                'type'       => 'otp',
                'request'    => [
                    'method'  => 'direct',
                    'content' => $content
                ],
                'version'    => 1,
                'payment_id' => $payment->getPublicId(),
                'gateway'    => $response['gateway']
            ];
        }

        $this->segment->trackPayment($payment, TraceCode::OTP_GENERATE, $response);

        return $response;
    }

    protected function updateTwoFactorAuthForOneStepPayment()
    {
        $payment = $this->payment;

        // In one step payment, we always set the 2FA as unavailable. Basically, no 2FA done.
        // Except in the cases of recurring, because, here we know that
        // we have manually skipped/by-passed the 2FA.

        if (($payment->terminal !== null) and
            ($payment->terminal->isNon3DSRecurring() === true))
        {
            $payment->setTwoFactorAuth(TwoFactorAuth::SKIPPED);
        }
        else
        {
            $payment->setTwoFactorAuth(TwoFactorAuth::NOT_APPLICABLE);
        }

        $this->repo->saveOrFail($payment);
    }

    protected function autoCapturePaymentIfApplicable(Payment\Entity $payment)
    {
        if ($this->shouldAutoCapture($payment) === true)
        {
            // If payment_capture was sent as true in order,
            // then we capture it in this step only.
            $this->autoCapturePayment($payment);
        }
    }

    protected function updateLateAuthFlag(Payment\Entity $payment)
    {
        $payment->setLateAuthorized(false);

        $this->repo->saveOrFail($payment);
    }

    protected function getVerifyCaller(): string
    {
        $route = $this->route->getCurrentRouteName();

        switch($route)
        {
            case 'payment_verify_multiple':
                $caller = 'cron';
                break;

            case 'payment_authorize_failed':
                $caller = 'dashboard';
                break;

            case 'reconciliate':
                $caller = 'reconciliate';
                break;

            default:
                $caller = 'unknown';
                break;
        }

        return $caller;
    }

    public function authorizeFailedPayment(Payment\Entity $payment): array
    {
        $this->setPayment($payment);

        if ($payment->isStatusCreatedOrFailed() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Non failed payment given for authorization where failed payment is needed');
        }

        $paymentCreatedTime = $payment->getCreatedAt();

        $currentTime = time();

        $this->trace->info(
            TraceCode::PAYMENT_FAILED_TO_AUTHORIZED,
            [
                'payment_id'      => $payment->getId(),
                'payment_created' => $paymentCreatedTime,
                'verify_bucket'   => $payment->getVerifyBucket(),
                'authorized_at'   => $currentTime,
                'time_difference' => $currentTime - $paymentCreatedTime,
                'caller'          => $this->getVerifyCaller(),
            ]);

        $this->segment->trackPayment($payment, TraceCode::PAYMENT_FAILED_TO_AUTHORIZED);

        $this->runAuthorizeFailedTransaction($payment);

        $this->traceAuthorizeFailedOperationData($payment);

        return $payment->toArrayAdmin();
    }

    /**
     * This is a hack authorize function specially for authorizing payments
     * from gateways who provide payment information through their verify api's
     * for a limited time frame (e.g axis_migs, jiomoney). If we miss any failed
     * payment reconciliation there then we need to do it manually later.
     *
     * @param Payment\Entity $payment
     * @param array $input
     * @return array $payment
     * @throws Exception\BadRequestValidationFailureException
     */
    public function forceAuthorizeFailedPayment(Payment\Entity $payment, array $input = []): array
    {
        $this->setPayment($payment);

        if ($payment->isFailed() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Non failed payment given for authorization');
        }

        if (in_array($payment->getGateway(), Payment\Gateway::FORCE_AUTHORIZE_GATEWAYS, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                                        'Cannot force authorize on this gateway',
                                        'gateway',
                                        $payment->getGateway());
        }

        if ($payment->hasCard())
        {
            $card = $this->repo->card->fetchForPayment($payment);
        }

        $this->segment->trackPayment($payment, TraceCode::FORCE_AUTH_FAILED_PAYMENT);

        $this->repo->transaction(function() use ($payment, $input)
        {
            $data = array('payment' => $payment->toArray(), 'gateway' => $input);

            $flag = $this->callGatewayFunction(Action::FORCE_AUTHORIZE_FAILED, $data);

            if ($flag === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Payment expected to have succeeded on the gateway has actually not. ' .
                    'Should not have called this function in this scenario');
            }

            $this->lockForUpdateAndReload($payment);

            assert ($payment->isFailed() === true);

            $payment->setErrorNull();
            $payment->setVerified(true);

            // The first argument marks the payment as converted from failed
            // to authorized
            $this->updateAndNotifyPaymentAuthorized([], true);

            $this->repo->saveOrFail($payment);
        });

        // TODO: Remove reload once the branch hotfix/authorize-transaction-save is merged.
        return $payment->reload()->toArrayAdmin();
    }

    protected function runPaymentInputValidations(Payment\Entity $payment, array $input)
    {
        $this->validateCardAndCvv($payment, $input);

        $this->validateRecurringIfApplicable($payment, $input);

        $this->validateS2SIfApplicable($payment);

        $this->validateSubscriptionInputIfPresent($payment);

        $this->verifyPaymentMethodEnabled($payment);

        $this->validatePaymentNetworkSupported($payment);

        $this->runInternationalChecks($payment);

        $this->runFraudChecks($payment);
    }

    protected function validateSubscriptionInputIfPresent(Payment\Entity $payment)
    {
        //
        // Subscription association to payment happens in pre-process
        //
        if ($payment->getSubscriptionId() === null)
        {
            return;
        }

        if ($payment->isRecurring() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_SUBSCRIPTION_NOT_RECURRING,
                null,
                [
                    'payment_id'        => $payment->getId(),
                    'subscription_id'   => $payment->getSubscriptionId(),
                ]);
        }

        $subscription = $payment->subscription;

        $subscriptionStatus = $subscription->getStatus();

        if (in_array($subscriptionStatus, Subscription\Status::$nonChargeableStatuses, true) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_EXPIRED_OR_CANCELLED,
                null,
                [
                    'subscription_id'   => $subscription->getId(),
                    'status'            => $subscriptionStatus
                ]);
        }

        if ($subscription->isCreated() === true)
        {
            $this->validateNewSubscription($subscription, $payment);
        }
        else if ($subscription->hasBeenAuthenticated() === true)
        {
            $this->validateAuthenticatedSubscription($subscription, $payment);
        }
        else
        {
            throw new Exception\LogicException(
                'Subscription is neither in created state nor has ever been authenticated.',
                null,
                [
                    'subscription_id' => $subscription->getId(),
                    'status' => $subscription->getStatus(),
                ]);
        }
    }

    protected function validateAuthenticatedSubscription(
        Subscription\Entity $subscription,
        Payment\Entity $payment)
    {
        $publicAuth = $this->ba->isPublicAuth();

        if (($publicAuth === true) and
            ($subscription->isChangeCardStatus() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_CHANGE_CARD_NOT_ALLOWED,
                null,
                [
                    'subscription_id' => $subscription->getId(),
                    'status' => $subscription->getStatus(),
                ]);
        }

        //
        // Public auth when subscription is already authenticated means
        // that it is in retry flow. In retry flow, token is expected
        // to be already present, and hence we don't throw an exception.
        //
        if (($publicAuth === false) and
            ($subscription->hasToken() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_TOKEN_NOT_ASSOCIATED,
                null,
                [
                    'payment_id'            => $payment->getId(),
                    'subscription_id'       => $subscription->getId(),
                    'subscription_status'   => $subscription->getStatus(),
                ]);
        }

        if ($subscription->getPaidCount() >= $subscription->getTotalCount())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_TOTAL_COUNT_EXCEEDED,
                null,
                [
                    'payment_id'            => $payment->getId(),
                    'subscription_id'       => $subscription->getId(),
                    'subscription_status'   => $subscription->getStatus(),
                    'total_count'           => $subscription->getTotalCount(),
                    'paid_count'            => $subscription->getPaidCount(),
                ]);
        }

        //
        // This would basically be the retry flow.
        // The customer would be trying to change his card
        // here. Hence, it would be on public auth.
        //
        if (($subscription->isChangeCardStatus() === true) and
            ($publicAuth === true))
        {
            $this->validateSubscriptionAmount($subscription, $payment->getAmount());
        }
    }

    protected function validateNewSubscription(Subscription\Entity $subscription, Payment\Entity $payment)
    {
        $this->validateSubscriptionAmount($subscription, $payment->getAmount());

        $subscription->getValidator()->validateStartAtForAuthTransaction();

        //
        // For the first transaction, the subscription should be in created state
        // and should not have any token associated with it already.
        //
        if ($subscription->hasToken() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_TOKEN_ALREADY_ASSOCIATED,
                null,
                [
                    'payment_id'            => $payment->getId(),
                    'subscription_id'       => $subscription->getId(),
                    'subscription_status'   => $subscription->getStatus(),
                    'subscription_token'    => $subscription->getTokenId(),
                ]);
        }
    }

    protected function validateSubscriptionAmount(Subscription\Entity $subscription, int $paymentAmount)
    {
        $expectedAmount = (new Subscription\Core)->getAuthTransactionAmount($subscription);

        //
        // Adding `intval` because it's failing otherwise in wercker.
        // Works fine on local though. >.<
        //
        if (intval($paymentAmount) !== intval($expectedAmount))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_AUTH_TRANSACTION_AMOUNT,
                null,
                [
                    'subscription_id' => $subscription->getId(),
                    'payment_amount'  => $paymentAmount,
                    'expected_amount' => $expectedAmount
                ]);
        }
    }

    protected function validatePaymentNetworkSupported(Payment\Entity $payment)
    {
        $merchant = $payment->merchant;

        if (($payment->isMethodCardOrEmi() === true) and
            ($merchant->getCategory2() === Terminal\Category::PHARMA))
        {
            $card = $payment->card;

            if ($card->getNetworkCode() === Card\Network::DICL)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED);
            }
        }
    }

    // @codingStandardsIgnoreStart
    protected function validateS2SIfApplicable(Payment\Entity $payment)
    {
    // @codingStandardsIgnoreEnd
        $merchant = $payment->merchant;

        //
        // We need to check if S2S is enabled only if the payment create
        // call has been made via private auth.
        //
        if ($this->app['basicauth']->isPrivateAuth() === false)
        {
            return;
        }

        //
        // For first recurring payments, if it's coming via private auth, the merchant
        // should have S2S enabled, along with recurring.
        // For second recurring payments, if it's coming via private auth, the merchant
        // need not have S2S enabled. The merchant needs to be enabled only for recurring.
        //
        if ($payment->isSecondRecurring() === true)
        {
            return;
        }

        if ($merchant->isFeatureEnabled(Feature\Constants::S2S) === true)
        {
            return;
        }

        if ($payment->isOpenWalletPayment() === true)
        {
            $this->verifyFeatureForMerchant($merchant, Feature\Constants::OPENWALLET);
        }
        else if ($payment->isWallet() === true)
        {
            $this->verifyFeatureForMerchant($merchant, Feature\Constants::S2SWALLET);
        }
        else if ($payment->isUpi() === true)
        {
            $this->verifyFeatureForMerchant($merchant, Feature\Constants::S2SUPI);
        }
        else if ($payment->isAeps() === true)
        {
            $this->verifyFeatureForMerchant($merchant, Feature\Constants::S2SAEPS);
        }
        else
        {
            // If feature is not present, simply throw invalid url error.
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_URL_NOT_FOUND,
                null,
                [
                    'payment_id' => $payment->getId()
                ]);
        }
    }

    protected function validateRecurringIfApplicable(Payment\Entity $payment, array $input)
    {
        $recurring = $payment->isRecurring();

        if ($recurring === false)
        {
            return;
        }

        $merchant = $payment->merchant;

        $this->verifyFeatureForRecurring($merchant, $payment);

        $token = $payment->getGlobalOrLocalTokenEntity();

        if ($token !== null)
        {
            $this->assertTokenIsRecurring($payment, $token);
        }

        //
        // If payment type is card, validate that the card supports recurring
        // or if payment type is netbanking, validate that the bank supports recurring
        //
        if ($payment->isCard() === true)
        {
            $this->validateRecurringCard($payment);
        }
        else if ($payment->isNetbanking() === true)
        {
            $this->validateRecurringNetbanking($payment, $token, $input);
        }

        //
        // The first recurring will be on public auth for non-S2S enabled merchants.
        // The second recurring MUST always be via private auth.
        // But, if token IS PRESENT, it could just mean a different recurring payment
        // with the same token. It need not necessarily be the initial recurring payment
        // for which the token was created in the first place. Hence, here, second recurring
        // is not really second recurring and could be in fact first recurring only.
        //
        if ((empty($input[Payment\Entity::TOKEN]) === false) and
            ($payment->isSecondRecurring() === true))
        {
            $this->verifyAggregatorIfApplicable($merchant);
        }
    }

    /**
     * If the token is not recurring, but the payment is a second recurring payment,
     * then the token cannot be used for the payment.
     *
     * @param Payment\Entity $payment
     * @param Token\Entity $token
     * @throws Exception\BadRequestException
     */
    protected function assertTokenIsRecurring(Payment\Entity $payment, Token\Entity $token)
    {
        //
        // Second recurring payments have to be enabled for recurring
        //
        if (($payment->isSecondRecurring() === true) and
            ($token->isRecurring() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TOKEN_NOT_ENABLED_FOR_RECURRING,
                Token\Entity::RECURRING,
                [
                    'payment' => $payment->toArray(),
                    'token'   => $token->toArray()
                ]);
        }
    }

    protected function verifyFeatureForRecurring(Merchant\Entity $merchant, Payment\Entity $payment)
    {
        $authType = $this->app['basicauth']->getAuthType();

        switch ($authType)
        {
            case BasicAuth\Type::PRIVATE_AUTH:

                //
                // Subscriptions can also actually make payments in private auth
                // In case of manual retry, they can do it from either the dashboard
                // or API directly. But, if it's from API directly, it would mean
                // they are doing a S2S recurring payment. We cannot allow that.
                // Hence, we are going to ensure that retry can happen only from the
                // dashboard and not from the API.
                //
                if ($this->app['basicauth']->isProxyAuth() === true)
                {
                    $this->verifyAtLeastOneFeatureEnabledForMerchant(
                        $merchant,
                        [
                            Feature\Constants::SUBSCRIPTIONS,
                            Feature\Constants::CHARGE_AT_WILL,
                        ]);
                }
                else
                {
                    // Merchants with subscriptions feature cannot make S2S calls
                    // for recurring payments.
                    $this->verifyAtLeastOneFeatureEnabledForMerchant(
                        $merchant,
                        [
                            Feature\Constants::CHARGE_AT_WILL,
                        ]);
                }

                break;

            case BasicAuth\Type::PUBLIC_AUTH:

                // Public payments can be made for recurring for merchants with either
                // subscriptions or recurring features enabled.
                $this->verifyAtLeastOneFeatureEnabledForMerchant(
                    $merchant,
                    [
                        Feature\Constants::SUBSCRIPTIONS,
                        Feature\Constants::CHARGE_AT_WILL,
                    ]);

                break;

            case BasicAuth\Type::PRIVILEGE_AUTH:

                // Privilege auth for recurring should be used only for merchants
                // who have subscriptions.
                // But, since it's privilege auth, it can be used for merchants with
                // recurring feature also, but no requirement right now.
                $this->verifyFeatureForMerchant($merchant, Feature\Constants::SUBSCRIPTIONS);

                break;

            default:

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_URL_NOT_FOUND,
                    null,
                    [
                        'payment_id' => $payment->getId(),
                        'auth_type' => $authType
                    ]);
        }
    }

    protected function verifyAggregatorIfApplicable(Merchant\Entity $merchant)
    {
        // Zoho requires its merchant to use this route only through Zoho itself
        // We need to check if merchant is sending the request himself, without Zoho
        // This is temporary, will be removed when OAuth comes through
        if ($merchant->isFeatureEnabled(Feature\Constants::ZOHO) === true)
        {
            (new Feature\Validator)->validateZoho($this->request);
        }
    }

    protected function validateRecurringCard(Payment\Entity $payment)
    {
        if ($payment->card->isRecurringSupported() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_RECURRING_NOT_SUPPORTED);
        }
    }

    protected function validateRecurringNetbanking(Payment\Entity $payment, Token\Entity $token = null, array $input)
    {
        $bank = $payment->getBank();

        // TODO: Handle first recurring / second recurring based on token and route

        if (Payment\Gateway::isRecurringSupportedOnBank($bank) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_RECURRING_NOT_SUPPORTED,
                Payment\Entity::BANK,
                [
                    'payment' => $payment->toArray(),
                ]);
        }

        // We ensure that the e_mandate feature has been enabled for the merchant
        $this->verifyFeatureForMerchant($payment->merchant, Feature\Constants::E_MANDATE);

        if ($token === null)
        {
            return;
        }

        //
        // This is broken still. We should not be accepting any token
        // in private auth also for first recurring. But, in private auth,
        // it could be second recurring also, where we accept a token.
        //
        if (($this->ba->isPublicAuth() === true) and
            (empty($input[Payment\Entity::TOKEN]) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_NB_TOKEN_PASSED_IN_FIRST_RECURRING,
                Payment\Entity::BANK,
                [
                    'payment' => $payment->toArray(),
                    'token'   => $token->toArray(),
                ]);
        }

        $this->validateTokenRecurringStatus($token, $payment);

        $this->validateTokenMaxAmount($token, $payment);
    }

    protected function validateTokenRecurringStatus(Token\Entity $token, Payment\Entity $payment)
    {
        if ($payment->isSecondRecurring())
        {
            if ($token->getRecurringStatus() !== Token\RecurringStatus::CONFIRMED)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_NB_UNCONFIRMED_TOKEN_PASSED_IN_SECOND_RECURRING,
                    Payment\Entity::BANK,
                        [
                             'payment' => $payment->toArray(),
                             'token'   => $token->toArray(),
                        ]);
            }
        }
    }

    protected function validateTokenMaxAmount(Token\Entity $token, Payment\Entity $payment)
    {
        if (($token->getMaxAmount() !== null) and
            ($payment->getAmount() > $token->getMaxAmount()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_AMOUNT_GREATER_THAN_TOKEN_MAX_AMOUNT,
                Token\Entity::MAX_AMOUNT,
                [
                    'payment'        => $payment->toArray(),
                    'token'          => $token->toArray(),
                    'payment_amount' => $payment->getAmount(),
                    'token_amount'   => $token->getMaxAmount()
                ]);
        }
    }

    protected function validateOfferIfApplicable(Payment\Entity $payment)
    {
        (new Offer\Core)->validateOfferApplicableOnPayment($payment);
    }

    protected function runPostGatewaySelectionPreProcessing(Payment\Entity $payment, array & $gatewayInput)
    {
        // Fees validation can only happen after international validation has gone through
        // otherwise can cause issues with international pricing rule being not available when
        // international is not enabled.
        $this->verifyFeesLessThanAmount($payment);

        $this->repo->saveOrFail($payment);

        $this->tracePaymentInfo(TraceCode::PAYMENT_CREATED, Trace::DEBUG);
        $this->segment->trackPayment($payment, TraceCode::PAYMENT_CREATED);

        //
        // Call gateway input
        //
        $gatewayInput['payment'] = $payment->toArrayGateway();

        $gatewayInput['callbackUrl'] = $this->getCallbackUrl();

        $gatewayInput['otpSubmitUrl'] = $this->getOtpSubmitUrl();

        if ($payment->hasOrder())
        {
            $gatewayInput['order'] = $payment->order->toArray();
        }

        // set token for local card saving in gateway input
        $gatewayInput['token'] = $payment->getGlobalOrLocalTokenEntity();

        //
        // This is mostly required for first data recurring.
        // They need gateway merchant id of the terminal to be
        // sent in the recurring request.
        // The terminal is set as part of gateway_token.
        // The normal token may/will not have a terminal (not correct one at least)
        // That whole global token wala stuff. One customer, one token, multiple
        // subscriptions/terminals.
        //
        $this->setGatewayTokenInInput($payment, $gatewayInput);

        $customProperties = [
            'otpSubmitUrl' => $this->getOtpSubmitUrl(),
            'callbackUrl' => $this->getCallbackUrl()
        ];

        $this->segment->trackPayment($payment, TraceCode::GATEWAY_SELECTION_PREPROCESSING, $customProperties);
    }

    protected function setGatewayTokenInInput(Payment\Entity $payment, array & $gatewayInput)
    {
        $token = $gatewayInput['token'];

        if (empty($token) === true)
        {
            return;
        }

        $reference = $payment->getReferenceForGatewayToken();

        $gatewayTokens = $this->repo->gateway_token->findByTokenAndReference($token, $reference);

        //
        // It's possible that there are no gateway tokens for this.
        // For NB, wallets, non-recurring cards, first recurring card, etc.
        //
        if ($gatewayTokens->count() === 1)
        {
            $gatewayInput['gateway_token'] = $gatewayTokens->first();
        }
    }

    protected function dummyPrePaymentAuthorizeProcessing($payment, $input)
    {
        $gatewayInput = [];

        $this->runPaymentMethodRelatedPreProcessing($payment, $input, $gatewayInput);

        $this->processCurrencyConversions($payment);
    }

    protected function parseContact(string $contact): PhoneBook
    {
        // Constructor does the basic validation
        $phoneBook = new PhoneBook($contact, true);

        // Setting an instance just like carbon
        return $phoneBook;
    }

    protected function runInternationalChecks(Payment\Entity $payment)
    {
        // return if method is not card or card is not international
        if (($payment->getMethod() !== Method::CARD) or
            ($payment->card->isInternational() === false))
        {
            return;
        }

        $this->validateInternationalAllowed($payment);
    }

    protected function runFraudChecks(Payment\Entity $payment)
    {
        if ($payment->shouldRunFraudChecks() === true)
        {
            $this->validateEmailTld($payment);

            $this->validateFraudDetection($payment, $this->merchant);

            $this->validateBlockedCard($payment);
        }
    }

    protected function validateEmailTld(Payment\Entity $payment)
    {
        $email = $payment->getEmail();

        $tld = last(explode('.', $email));

        if (TLD::isValid($tld) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The email must be a valid email address.', 'email');
        }
    }

    protected function validateInternationalAllowed(Payment\Entity $payment)
    {
        $merchant = $payment->merchant;

        if ($merchant->isInternational() === false)
        {
            $e = new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_INTERNATIONAL_NOT_ALLOWED);

            $this->updatePaymentAuthFailedAndThrowException($e);
        }
    }

    protected function validateBlockedCard(Payment\Entity $payment)
    {
        if ($payment->hasCard() === false)
        {
            return;
        }

        $card = $payment->card;

        if ($card->isBlocked() === true)
        {
            $data = [
                'payment_id' => $payment->getPublicId(),
                'card_id'    => $card->getId(),
            ];

            $e = new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD, null, $data);

            $this->updatePaymentAuthFailed($e);

            $riskData = [
                Risk\Entity::REASON => Risk\RiskCode::PAYMENT_FAILED_DUE_TO_BLOCKED_CARD,
                Risk\Entity::FRAUD_TYPE => Risk\Type::CONFIRMED,
            ];

            (new Risk\Core)->logPaymentForSource($payment, Risk\Source::INTERNAL, $riskData);

            throw $e;
        }
    }

    protected function runAuthorizeFailedTransaction(Payment\Entity $payment)
    {
        $this->repo->transaction(function() use ($payment)
        {
            $data = array('payment' => $payment->toArray());

            if ($payment->isMethodCardOrEmi())
            {
                $data['card'] = $this->repo->card->fetchForPayment($payment)->toArray();
            }

            $flag = $this->callGatewayFunction(Action::AUTHORIZE_FAILED, $data);

            if ($flag === false)
            {
                $this->segment->trackPayment($payment,
                                                    TraceCode::PAYMENT_FAILED_EXPECTED_GATEWAY_SUCCESS,
                                                    $data);

                throw new Exception\BadRequestValidationFailureException(
                    'Payment expected to have succeeded on the gateway has actually not. ' .
                    'Should not have called this function in this scenario');
            }

            $this->lockForUpdateAndReload($payment);

            if ($payment->isStatusCreatedOrFailed() === false)
            {
                $this->segment->trackPayment($payment,
                                                    TraceCode::PAYMENT_ALREADY_AUTHORIZED,
                                                    $data);

                throw new Exception\BadRequestValidationFailureException(
                    'Payment being authorized is actually already authorized by some other thread.',
                    null,
                    ['payment_id' => $payment->getId()]);
            }

            $payment->setErrorNull();
            $payment->setVerified(true);

            // The first argument marks the payment as converted from failed
            // to authorized
            $this->updateAndNotifyPaymentAuthorized([], true);

            $this->autoCapturePaymentIfApplicable($payment);

            $this->repo->saveOrFail($payment);

            $this->setPayment($payment);
        });
    }

    protected function processCurrencyConversions(Payment\Entity $payment)
    {
        $currency = $payment->getCurrency();

        $merchant = $payment->merchant;

        if ($currency !== Currency\Currency::INR)
        {
            // mcc is supported only for merchants where this flag is set to true or false
            // or merchant is not fee bearer
            if (($merchant->convertOnApi() === null) or
                ($merchant->isFeeBearerCustomer() === true))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED,
                    null,
                    [
                        'convert_on_api'        => $merchant->convertOnApi(),
                        'fee_bearer_customer'   => $merchant->isFeeBearerCustomer(),
                        'payment_id'            => $payment->getId(),
                        'currency'              => $currency,
                    ]);
            }

            // mcc is supported only for card payments
            if ($payment->isCard() === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED,
                    null,
                    [
                        'card'          => $payment->isCard(),
                        'payment_id'    => $payment->getId(),
                        'currency'      => $currency,
                    ]);

            }

            // gateway should do currency conversion only on international cards
            // else api should do currency conersion and use INR terminals
            $convertCurrency = $merchant->convertOnApi();

            if ($payment->isInternational() === false)
            {
                $convertCurrency = true;
            }

            $payment->setConvertCurrency($convertCurrency);

        }

        $amount = $payment->getAmount();

        $baseAmount = (new Currency\Core)->getBaseAmount($amount, $currency);

        // if gateway is doing currency conversions, actual rate used by gateway
        // will use lower than current rates hence we also use 2 percentage lower
        // values
        if ($payment->getConvertCurrency() === false)
        {
            $baseAmount = (int) ceil($baseAmount * 0.98);
        }

        $payment->setBaseAmount($baseAmount);
    }

    /**
     * @param Payment\Entity $payment
     * @param array $input Input data received from checkout/merchant.
     * @param array $gatewayInput Data that is required by gateway for the payment to be processed.
     */
    protected function runPaymentMethodRelatedPreProcessing(Payment\Entity $payment, & $input, array & $gatewayInput)
    {
        //
        // Either the customer ID or the app token ID is required to get the customer.
        // Hence, fill the app token in the input if customer ID is not present.
        //
        // This should be done before `addCustomerIdToSubscriptionInput` because we
        // check if app_token is present in some conditions.
        //
        if (empty($input[Payment\Entity::CUSTOMER_ID]) === true)
        {
            $this->checkAndFillSavedAppToken($input);
        }

        if (empty($input[Payment\Entity::SUBSCRIPTION_ID]) === false)
        {
            $this->associateSubscriptionToPayment($payment, $input);

            $this->addCustomerIdToSubscriptionInput($payment->subscription, $input);
        }

        // First fetch the relevant customer (global or local)
        list($customer, $customerApp) = (new Customer\Core)->getCustomerAndApp($input, $this->merchant);

        if ($customer === null)
        {
            $this->preProcessPaymentWithoutSaving($payment, $input, $gatewayInput);
        }
        else if ($customer->isLocal() === true)
        {
            $this->preProcessPaymentForLocalCustomer($customer, $payment, $input, $gatewayInput);
        }
        else
        {
            $localCustomer = null;

            if ($payment->hasSubscription() === true)
            {
                $subscription = $payment->subscription;

                //
                // If global, create a local customer and link that to the subscription.
                // $customer is global here currently, create its local copy.
                //
                if ($subscription->hasCustomer() === false)
                {
                    $localCustomer = $this->associateLocalCustomerToSubscription($subscription, $customer);
                }
                else
                {
                    //
                    // `else` is from the second charge onwards
                    // or change card flow.
                    //
                    $localCustomer = $subscription->customer;
                }
            }

            $this->preProcessPaymentForGlobalCustomer(
                $customer, $localCustomer, $customerApp, $payment, $input, $gatewayInput);
        }

        if ($payment->isEmi() === true)
        {
            $cardNumber = $gatewayInput['card']['number'];

            $emiDuration = $input['emi_duration'];

            $this->setBankAndEmiPlanDetails($payment, $cardNumber, $emiDuration);
        }

        if ($payment->isUpi())
        {
            $this->validateUpiPspIsAllowed($payment);
        }

        if ($payment->isAeps())
        {
            $this->setGatewayInputForAeps($input, $gatewayInput);
        }

        $payment->setInternational();

        $this->handleEMandatePayments($payment);
    }

    protected function handleEMandatePayments(Payment\Entity $payment)
    {
        $token = $payment->getGlobalOrLocalTokenEntity();

        //
        // It's not an e-mandate payment if
        // - Token not set
        // - Payment not netbanking
        // - Payment not recurring
        //
        if (($token === null) or
            ($payment->isNetbanking() === false) or
            ($payment->isRecurring() === false))
        {
            return;
        }

        // True => debit, False => registration
        // TODO: Add support for when we allow recurring tokens for first payments
        $type = ($token->isRecurring() === true) ? Payment\RecurringType::DEBIT : Payment\RecurringType::REGISTRATION;

        $payment->setRecurringType($type);
    }

    protected function associateLocalCustomerToSubscription(
        Subscription\Entity $subscription, Customer\Entity $customer)
    {
        $localCustomer = (new Customer\Core)->createLocalCustomerFromGlobal($customer, $subscription->merchant);

        $localCustomer->globalCustomer()->associate($customer);

        $this->repo->saveOrFail($localCustomer);

        return $localCustomer;

        // $subscription->customer()->associate($localCustomer);
        //
        // // If this gets saved and then the payment fails, what happens?
        // // We remove the relation if the payment fails, in `updatePaymentFailed`
        // $this->repo->saveOrFail($subscription);
    }

    protected function addCustomerIdToSubscriptionInput(Subscription\Entity $subscription, array & $input)
    {
        //
        // If a subscription_id is sent in the input, the customer_id should
        // never be sent. It's either associated with the subscription (local customer)
        // or we use the global customer and associate that later.
        //
        if (isset($input[Payment\Entity::CUSTOMER_ID]) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_CUSTOMER_ID_SENT_IN_INPUT,
                null,
                [
                    'subscription_id'   => $subscription->getId(),
                ]);
        }

        //
        // A subscription can have a customer in case of local flow if it's first 2FA.
        // A subscription can have a customer in case of local or global flow if it's
        // second 2FA (change card) or subsequent charges.
        // In case of global flow, local flow will be associated with the customer.
        //
        // First 2FA:
        //  - Local flow: Subscription has customer_id associated with it.
        //  - Global flow: Subscription does not have any customer_id associated with it.
        //                 The input has app_token in it set by the session.
        // Second 2FA (change card):
        //  - Local flow: Subscription has customer_id already associated with it.
        //  - Global flow: Subscription has customer_id already associated with it.
        //                 But, it should also have app_token set in the input. Customer
        //                 should be logged in.
        // Subsequent charges:
        //  - Local flow: Subscription has customer_id associated with it.
        //  - Global flow: Subscription has customer_id already associated with it.
        //                 This customer_id is the local customer_id though.
        //                 So, here, we add the customer_id to the input so that
        //                 later in the flow, while fetching the customer entity,
        //                 we use the local customer_id to fetch the global customer_id
        //                 that would be associated with the local customer entity.
        //                 From thereon, the flow follows global.
        //
        // In case local flow, merchant should always ensure that the correct customer of the
        // subscription is logged in their checkout before sending us the payment request.
        //
        // In case of global flows, the subscription will always be associated with the global token.
        //
        if ($subscription->hasCustomer() === true)
        {
            //
            // In case the subscription already has a customer
            // and that customer has a global customer, we should
            // also ensure that app_token is present in case of
            // second 2FA (change card). In the subsequent charges flow,
            // app_token won't be present anyway, since it's internal.
            //
            if($subscription->customer->globalCustomer !== null)
            {
                if ((($this->ba->isPublicAuth() === true) and
                     ($subscription->isChangeCardStatus() === true)) and
                    (empty($input[Payment\Entity::APP_TOKEN]) === true))
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_APP_TOKEN_ABSENT,
                        null,
                        [
                            'subscription_id' => $subscription->getId(),
                            'global' => true,
                        ]);
                }
            }

            $input[Payment\Entity::CUSTOMER_ID] = Customer\Entity::getSignedId($subscription->getCustomerId());
        }
    }

    protected function associateSubscriptionToPayment(Payment\Entity $payment, array $input)
    {
        $subscriptionId = $input[Payment\Entity::SUBSCRIPTION_ID];

        $subscription = $this->repo->subscription->findByPublicIdAndMerchant($subscriptionId, $this->merchant);

        $this->trace->info(
            TraceCode::PAYMENT_SUBSCRIPTION_ASSOCIATE,
            [
                'payment_id'        => $payment->getId(),
                'subscription_id'   => $subscription->getId(),
            ]);

        $payment->subscription()->associate($subscription);
    }

    protected function setGatewayInputForAeps($input, & $gatewayInput)
    {
        if ((isset($input['aadhaar']['fingerprint']) === true) and
            (isset($input['aadhaar']['session_key']) === true) and
            (isset($input['aadhaar']['hmac']) === true))
        {
            $gatewayInput['aadhaar'] = [
                'fingerprint' => $input['aadhaar']['fingerprint'],
                'session_key' => $input['aadhaar']['session_key'],
                'hmac'        => $input['aadhaar']['hmac'],
                'cert_expiry' => $input['aadhaar']['cert_expiry'],
            ];
        }
        else
        {
            $gatewayInput['aadhaar'] = [
                'encrypted' => false,
                'fingerprint' => $input['aadhaar']['fingerprint'],
            ];
        }

        $gatewayInput['aadhaar']['number'] = $input['aadhaar']['number'];
    }

    protected function preProcessPaymentWithoutSaving($payment, array & $input, array & $gatewayInput)
    {
        //
        // In the subscription flow, card must always be saved.
        //
        if ($payment->hasSubscription() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_PAYMENT_WITHOUT_SAVING,
                null,
                [
                    'payment_id'        => $payment->getId(),
                    'subscription_id'   => $payment->subscription->getId()
                ]);
        }

        // No card saving, normal simple flow
        if ($payment->isMethodCardOrEmi())
        {
            $payment->setSave(false);

            $payment->setRecurring(false);

            $vault = $payment->shouldSaveCard();

            $gatewayInput['card'] = $this->createCardEntity($input['card'], $vault, $this->merchant);
        }
    }

    protected function preProcessPaymentForLocalCustomer(Customer\Entity $customer,
                                                         Payment\Entity $payment,
                                                         array & $input,
                                                         array & $gatewayInput)
    {
        $this->payment->customer()->associate($customer);

        //
        // if token is set, payment is either from a saved card or is second recurring
        // else, the card needs to be saved or need to mark the payment as recurring (first recurring)
        //
        if (empty($input[Payment\Entity::TOKEN]) === true)
        {
            $this->preProcessPaymentFromUserDataLocal($customer, $payment, $input, $gatewayInput);
        }
        else
        {
            $this->preProcessPaymentFromSavedMethodLocal($customer, $payment, $input, $gatewayInput);
        }
    }

    protected function preProcessPaymentForGlobalCustomer(Customer\Entity $customer,
                                                          Customer\Entity $localCustomer = null,
                                                          Customer\AppToken\Entity $customerApp = null,
                                                          Payment\Entity $payment,
                                                          array & $input,
                                                          array & $gatewayInput)
    {
        //
        // Only in the case of privilege auth, it's okay to not
        // have an app_token. In all other cases, we should have
        // an app_token when we are processing 2FA.
        //
        if (($this->ba->isPrivilegeAuth() === false) and
            ($customerApp === null))
        {
            throw new Exception\LogicException(
                'Not privilege auth and no app_token. Should not have reached here at all.',
                ErrorCode::SERVER_ERROR_APP_TOKEN_NOT_PRESENT,
                [
                    'customer_id' => $customer->getId(),
                    'payment_id' => $payment->getId(),
                ]);
        }

        $this->payment->app()->associate($customerApp);

        $this->payment->globalCustomer()->associate($customer);

        if ($localCustomer !== null)
        {
            //
            // One of the reasons to do this is so that the customer is
            // also associated with the invoice later in the flow, after
            // the invoice is marked as paid.
            //
            $this->payment->customer()->associate($localCustomer);
        }

        // If token is set, then pay using global saved card
        if (empty($input[Payment\Entity::TOKEN]) === true)
        {
            // Does processing like creating card entity, saving card if passed in the input, etc..
            $this->preProcessPaymentFromUserDataGlobal($customer, $payment, $input, $gatewayInput);
        }
        else
        {
            $this->preProcessPaymentFromSavedMethodGlobal($customer, $payment, $input, $gatewayInput);
        }
    }

    protected function preProcessPaymentFromSavedMethodLocal(Customer\Entity $customer,
                                                           Payment\Entity $payment,
                                                           array & $input,
                                                           array & $gatewayInput)
    {
        $this->trace->info(
            TraceCode::PAYMENT_PROCESS_FROM_SAVED_LOCAL,
            [
                'token_id' => $input[Payment\Entity::TOKEN]
            ]);

        $tokenId = $input[Payment\Entity::TOKEN];

        $token = (new Token\Core)->getByTokenIdAndCustomer($tokenId, $customer);

        if ($payment->isMethodCardOrEmi())
        {
            $payment->localToken()->associate($token);

            $gatewayInput['card'] = $this->associateAndGetCardArrayForSavedToken($token, $input);
        }
        else if ($payment->isNetbanking())
        {
            // TODO: Check if this is really required here.
            $payment->setBank($token->getBank());

            $payment->localToken()->associate($token);
        }

        //else @todo for wallets
    }

    protected function preProcessPaymentFromSavedMethodGlobal(Customer\Entity $customer,
                                                            Payment\Entity $payment,
                                                            array & $input,
                                                            array & $gatewayInput)
    {
        $this->trace->info(
            TraceCode::PAYMENT_PROCESS_FROM_SAVED_GLOBAL,
            [
                'token' => $input[Payment\Entity::TOKEN]
            ]);

        // Token should definitely exist in database.
        $tokenId = $input[Payment\Entity::TOKEN];

        $token = (new Token\Core)->getByTokenIdAndCustomer($tokenId, $customer);

        if ($payment->isMethodCardOrEmi())
        {
            $gatewayInput['card'] = $this->createCardEntityFromSavedToken($token, $input);

            $payment->globalToken()->associate($token);

            $payment->card->globalCard()->associate($token->card);

            $this->repo->saveOrFail($payment->card);
        }
        else if ($payment->isMethod(Payment\Method::WALLET))
        {
            $payment->setWallet($token->getWallet());
        }
        else if ($payment->isMethod(Payment\Method::NETBANKING))
        {
            //
            // This should be here since, if a token is passed,
            // the bank would not be passed in the payment input.
            //
            $payment->setBank($token->getBank());

            $payment->globalToken()->associate($token);
        }
    }

    protected function preProcessPaymentFromUserDataLocal(Customer\Entity $customer,
                                                          Payment\Entity $payment,
                                                          array $input,
                                                          array & $gatewayInput)
    {
        // If save is set to true or recurring is set to true,
        // we save the card details while processing the payment
        $saveMethod = (($payment->getSave() === true) or
                       ($payment->isRecurring() === true));

        if ($saveMethod === false)
        {
            $this->preProcessPaymentWithoutSaving($payment, $input, $gatewayInput);
        }
        else
        {
            $this->savePaymentMethodLocal($customer, $payment, $input, $gatewayInput);
        }
    }

    protected function preProcessPaymentFromUserDataGlobal(Customer\Entity $customer,
                                                           Payment\Entity $payment,
                                                           array $input,
                                                           array & $gatewayInput)
    {
        // If save is set to true or recurring is set to true,
        // we save the card details while processing the payment
        $saveMethod = (($payment->getSave() === true) or
                       ($payment->isRecurring() === true));

        if ($saveMethod === false)
        {
            $this->preProcessPaymentWithoutSaving($payment, $input, $gatewayInput);
        }
        else
        {
            $this->savePaymentMethodGlobal($customer, $payment, $input, $gatewayInput);
        }
    }

    protected function savePaymentMethodLocal(Customer\Entity $customer,
                                              Payment\Entity $payment,
                                              array $input,
                                              array & $gatewayInput)
    {
        $token = null;

        // create local saved card and link to payment
        if ($payment->isMethodCardOrEmi() === true)
        {
            $gatewayInput['card'] = $this->createCardEntity($input['card'], true, $customer->merchant);

            $savedLocalCard = $payment->card;

            // save local saved card for local customer
            $token = $this->savePaymentMethod($customer, $payment, $savedLocalCard->getId());
        }
        else if ($payment->isNetbanking() === true)
        {
            // save netbanking bank locally for local customer
            $token = $this->savePaymentMethod($customer, $payment);
        }

        if ($token !== null)
        {
            $this->payment->localToken()->associate($token);
        }
    }

    protected function savePaymentMethodGlobal(Customer\Entity $customer,
                                               Payment\Entity $payment,
                                               array $input,
                                               array & $gatewayInput)
    {
        $token = null;

        if ($payment->isMethodCardOrEmi() === true)
        {
            // create global saved card and link to payment
            $gatewayInput['card'] = $this->createCardEntity($input['card'], true, $customer->merchant);

            $savedGlobalCard = $payment->card;

            // create merchant local card entity and link to payment
            $gatewayInput['card'] = $this->createCardEntity($input['card'], false, $this->merchant);

            // link local card to global card entity
            $payment->card->globalCard()->associate($savedGlobalCard);

            $this->repo->saveOrFail($payment->card);

            // save global saved card for global customer
            $token = $this->savePaymentMethod($customer, $payment, $savedGlobalCard->getId());
        }
        else if ($payment->isNetbanking() === true)
        {
            // save netbanking bank token globally for global customer
            $token = $this->savePaymentMethod($customer, $payment);
        }

        if ($token !== null)
        {
            $this->payment->globalToken()->associate($token);
        }
    }

    protected function savePaymentMethod(
        Customer\Entity $customer, Payment\Entity $payment, $savedCardId = null): Token\Entity
    {
        $this->trace->info(
            TraceCode::PAYMENT_SAVE_METHOD,
            [
                'method'      => $payment->getMethod(),
                'payment_id'  => $payment->getId(),
                'merchant_id' => $payment->merchant->getId(),
                'customer_id' => $customer->getId(),
                'local'       => $customer->isLocal(),
                'card_id'     => $savedCardId
            ]);

        $saveMethodInput = [
            Token\Entity::METHOD => $payment->getMethod()
        ];

        if ($payment->isMethodCardOrEmi())
        {
            $saveMethodInput[Token\Entity::METHOD] = Payment\Method::CARD;

            $saveMethodInput[Token\Entity::CARD_ID] = $savedCardId;
        }
        else if ($payment->isMethod(Payment\Method::NETBANKING))
        {
            $saveMethodInput[Token\Entity::BANK] = $payment->getBank();
            // TODO: We need to get this from user input - hard coding for now
            $saveMethodInput[Token\Entity::MAX_AMOUNT] = Token\Entity::DEFAULT_MAX_AMOUNT;
        }
        else if ($payment->isMethod(Payment\Method::WALLET))
        {
            $saveMethodInput[Token\Entity::WALLET] = $payment->getWallet();
        }

        $token = null;

        // @codingStandardsIgnoreStart
        try
        {
            $token = (new Token\Core)->create($customer, $saveMethodInput);
        }
        catch (Exception\RecoverableException $e)
        {
            // Ignore the exception, can be an already saved method
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);
        }
        // @codingStandardsIgnoreEnd

        return $token;
    }

    protected function verifyPaymentMethodEnabled(Payment\Entity $payment)
    {
        $paymentMethod = $payment->getMethod();

        switch ($paymentMethod)
        {
            case Payment\Method::CARD:
                $this->verifyCardEnabledInLive($payment);
                break;

            case Payment\Method::NETBANKING:
                $this->verifyBankEnabled($payment);
                break;

            case Payment\Method::WALLET:
                $this->verifyWalletEnabled($payment);
                break;

            case Payment\Method::EMI:
                $this->verifyEmiEnabled($payment);
                break;

            case Payment\Method::UPI:
                $this->verifyUpiEnabled();
                break;

            case Payment\Method::BANK_TRANSFER:
                $this->verifyBankTransferEnabled();
                break;

            case Payment\Method::AEPS:
                $this->verifyAepsEnabled();
                break;

            default:
                throw new Exception\LogicException(
                    'Should not reach here.',
                    null,
                    ['payment_method' => $paymentMethod]);
        }
    }

    protected function setBankAndEmiPlanDetails(Payment\Entity $payment, string $cardNumber, int $emiDuration)
    {
        $iinEntity = $payment->card->iinRelation;

        // On custom checkouts, sometimes users are entering random cards for
        // which iin entity doesn't exist
        if ($iinEntity === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_EMI_NOT_AVAILABLE_ON_CARD);
        }

        IIN\IIN::validateEmiAvailableForCard($iinEntity, $cardNumber);

        $payment->setBank($iinEntity->getIssuer());

        $subvention = $payment->merchant->getEmiSubvention();

        // Set emi plan id
        $emiPlan = $this->repo->emi_plan->fetchRelevantEmiPlan(
                                            $iinEntity, $emiDuration, $subvention);

        $payment->getValidator()->validateMinAmountWithEmiPlanAmount($emiPlan);

        $payment->emiPlan()->associate($emiPlan);
    }

    protected function fillReturnRequestDataForMerchant(Payment\Entity $payment, array & $returnData)
    {
        assert ($payment->getCallbackUrl() !== null);

        //
        // This would be normal request data at this point.
        // But since we will be redirecting to merchant's callback url
        // we need to push the request data into coproto structure
        // so that controller can then redirect peacefully.
        //
        $content = $returnData;

        $returnData = [
            'version' => 1,
            'type' => 'return',
            'request' => [
                'url' => $payment->getCallbackUrl(),
                'method' => 'post',
                'content' => $content,
            ],
        ];
    }

    protected function checkAndFillSavedAppToken(array & $input)
    {
        if ($this->request->hasSession() === false)
        {
            return;
        }

        $this->trace->info(
            TraceCode::PAYMENT_FILL_SAVED_APP_TOKEN,
            [
                'session' => $this->request->session()->all()
            ]);

        $key = $this->mode . '_app_token';

        $appToken = $this->request->session()->get($key);

        if ($appToken !== null)
        {
            $input[Payment\Entity::APP_TOKEN] = $appToken;
        }
    }

    protected function getPaymentGatewayRequestData($request, Payment\Entity $payment): array
    {
        switch (true)
        {
            case $this->canRunAsyncPaymentFlow($payment):

                return $this->getAsyncPaymentCreatedResponse($request, $payment);

            case $this->canRunOtpPaymentFlow($payment):

                return $this->getOtpPaymentCreatedResponse($request, $payment);

            default:

                return $this->getFirstPaymentCreatedResponse($request, $payment);
        }
    }

    /**
     * @see  CoProto supports async payments https://github.com/razorpay/api/wiki/COPROTO
     * @param $request
     * @param Payment\Entity $payment
     * @return array payment response
     */
    protected function getAsyncPaymentCreatedResponse($request, Payment\Entity $payment): array
    {
        $id = $payment->getPublicId();

        $response = [
            'type'          => 'async',
            'version'       => 1,
            'payment_id'    => $id,
            'gateway'       => $this->getEncryptedGatewayText($payment->getGateway()),
            'data'          => $request['data'],
            'request'       => [
                'url'    => $this->route->getUrlWithPublicAuthInQueryParam('payment_get_status', ['id' => $id]),
                'method' => 'GET',
            ]
        ];

        $this->segment->trackPayment($payment, TraceCode::ASYNC_PAYMENT_RESPONSE, $response);

        return $response;
    }

    protected function getFirstPaymentCreatedResponse(array $request, Payment\Entity $payment): array
    {
        $data['type'] = 'first';

        $data['request'] = $request;

        $data['version'] = 1;

        $data['payment_id'] = $payment->getPublicId();

        $data['gateway'] = $this->getEncryptedGatewayText($payment->getGateway());

        $data['amount'] =  $payment->getFormattedAmount();

        $data['image'] = $payment->merchant->getFullLogoUrlWithSize(Merchant\Logo::MEDIUM_SIZE);

        $segmentData = $data;

        // this might log sensitive data. Remove it
        if (isset($segmentData['request']['content']))
        {
            unset($segmentData['request']['content']);
        }

        $this->segment->trackPayment($payment, TraceCode::FIRST_PAYMENT_RESPONSE, $segmentData);

        return $data;
    }

    protected function updateTokenOnAuthorized(Payment\Entity $payment, array $data)
    {
        $token = $payment->getGlobalOrLocalTokenEntity();

        if ($token === null)
        {
            return;
        }

        $this->trace->info(
            TraceCode::PAYMENT_UPDATE_TOKEN,
            [
                'payment_id'      => $payment->getId(),
                'token_id'        => $payment->getTokenId(),
                'global_token_id' => $payment->getGlobalTokenId()
            ]);

        //
        // Update token stats. Assuming same token is not getting
        // used in multiple payments. Actually we should be locking.
        //

        $createdAt = $payment->getCreatedAt();

        $token->setUsedAt($createdAt);

        $token->incrementUsedCount();

        if ($payment->isRecurring() === true)
        {
            //
            // This is just in case. Payment recurring is anyway only
            // allowed on cards and netbanking.
            //
            if (($payment->isCard() === false) and
                ($payment->isNetbanking() === false))
            {
                return;
            }

            //
            // For netbanking payments, we create a new token for every
            // single new first recurring payment.
            // For existing recurring nb tokens, we do not update it.
            //
            if (($payment->isNetbanking() === true) and
                ($token->isRecurring() === true))
            {
                return;
            }

            if ($this->shouldSetTokenRecurring($payment, $data) === true)
            {
                $token->setRecurring(true);
            }

            //
            // Currently we require the recurring details to be
            // updated only for netbanking.
            // No details need to be updated for credit cards.
            //
            if ($payment->isNetbanking() === true)
            {
                $this->updateTokenRecurringDetails($token, $data);
            }

            $this->createAndSetTerminalInGatewayToken($payment, $token);

            //
            // This is being done simply. Can be removed.
            // Shouldn't be required now since we are using
            // gateway_token for terminal.
            //
            $token->terminal()->associate($payment->terminal);
        }

        $this->repo->saveOrFail($token);
    }

    protected function shouldSetTokenRecurring(Payment\Entity $payment, array $data)
    {
        if ($payment->isCard() === true)
        {
            return true;
        }

        if ($payment->isNetbanking() === true)
        {
            //
            // The idea is that for netbanking payments, we first check if the
            // recurring status is set, and if it is, we ensure that it is confirmed
            // before updating recurring to true. Or else we throw a LogicException
            // if token's recurring status is not set. In the generic case that doesn't
            // come under either of the above cases, we don't update recurring to false.
            //
            if ((empty($data[Token\Entity::RECURRING_STATUS]) === false) and
                ($data[Token\Entity::RECURRING_STATUS] === Token\RecurringStatus::CONFIRMED))
            {
                return true;
            }
        }

        return false;
    }

    protected function updateTokenRecurringDetails(Token\Entity $token, array $gatewayData)
    {
        //
        // We update the token details and not gateway token details
        // because the merchant is exposed to only the token.
        // If we have two gateway tokens and a single token, which
        // gateway token's details do we return back?
        // On the other hand, if we have two gateway tokens for
        // the same token and we store the recurring details in the
        // token entity, we will end up overriding the recurring_status
        // and other details. So, we need to ensure that we don't reuse
        // the same token.
        // Anyway, currently, we don't reuse the same token for NB.
        // The customer always gets a new token if they want to
        // subscribe to another subscription.
        // If we don't use the same token again, there's no issue
        // since there will always be only one terminal.
        // Gateway Tokens purpose was to handle multiple terminals
        // for same token only.
        //

        $this->updateRecurringStatus($token, $gatewayData);

        if ($token->getRecurringStatus() === null)
        {
            return;
        }

        $this->updateRecurringFailureReason($token, $gatewayData);

        $this->updateGatewayTokenForRecurring($token, $gatewayData);
    }

    protected function updateGatewayTokenForRecurring(Token\Entity $token, array $gatewayData)
    {
        $recurringStatus = $token->getRecurringStatus();

        if ($recurringStatus === Token\RecurringStatus::CONFIRMED)
        {
            //
            // Not all netbanking recurring have a gateway token.
            // However, if a second recurring payment is attempted without a gateway token,
            // we throw an exception to handle the case appropriately.
            //
            if (empty($gatewayData[Token\Entity::GATEWAY_TOKEN]) === false)
            {
                $gatewayToken = $gatewayData[Token\Entity::GATEWAY_TOKEN];

                $token->setGatewayToken($gatewayToken);
            }
        }
    }

    protected function updateRecurringFailureReason(Token\Entity $token, array $gatewayData)
    {
        $recurringStatus = $token->getRecurringStatus();

        //
        // We update the recurring failure reason of the token
        // only if the gateway returned a rejected response
        //
        if ($recurringStatus === Token\RecurringStatus::REJECTED)
        {
            if (empty($gatewayData[Token\Entity::RECURRING_FAILURE_REASON]) === true)
            {
                //
                // If it's rejected, there must always be a reason.
                //

                $this->trace->critical(
                    TraceCode::GATEWAY_RECURRING_REJECTED_WITHOUT_REASON,
                    [
                        'token'        => $token->toArray(),
                        'gateway_data' => $gatewayData
                    ]);

                return;
            }

            $recurringFailureReason = $gatewayData[Token\Entity::RECURRING_FAILURE_REASON];

            $token->setRecurringFailureReason($recurringFailureReason);
        }
    }

    protected function updateRecurringStatus(Token\Entity $token, array $gatewayData)
    {
        //
        // This should trace a critical error because this method is called
        // only when the payment is a first recurring payment. If the recurring status
        // is not null, there was something wrong with the way the token was created.
        //
        if ($token->getRecurringStatus() !== null)
        {
            //
            // We don't throw an exception here because this flow
            // is called while marking the payment as authorized.
            // We don't want to mess with payment being authorized!
            //
            $this->trace->critical(
                TraceCode::TOKEN_RECURRING_STATUS_ALREADY_SET,
                [
                    'token'        => $token->toArray(),
                    'gateway_data' => $gatewayData
                ]);

            return;
        }

        $recurringStatus = $gatewayData[Token\Entity::RECURRING_STATUS];

        if (empty($recurringStatus) === true)
        {
            //
            // The recurring status should always be set for token update.
            //
            $this->trace->critical(
                TraceCode::GATEWAY_RECURRING_STATUS_ALREADY_SET,
                [
                    'token'        => $token->toArray(),
                    'gateway_data' => $gatewayData
                ]);

            return;
        }

        $token->setRecurringStatus($recurringStatus);
    }

    protected function createAndSetTerminalInGatewayToken(Payment\Entity $payment, Token\Entity $token)
    {
        $reference = $payment->getReferenceForGatewayToken();

        $gatewayTokens = $this->repo->gateway_token->findByTokenAndReference($token, $reference);

        $gatewayTokensCount = $gatewayTokens->count();

        //
        // This is the case that the payment is a first recurring payment
        //
        if ($gatewayTokensCount === 0)
        {
            (new GatewayToken\Core)->create($payment, $token, $reference);
        }
        else if ($gatewayTokensCount === 1)
        {
            if ($payment->isNetbanking() === true)
            {
                //
                // We do not reuse the tokens in case of NB.
                // Every new registration requires a new
                // token to be created.
                //
                throw new Exception\LogicException(
                    'Tokens cannot be reused in netbanking payments',
                    null,
                    [
                        'payment'        => $payment->toArray(),
                        'gateway_tokens' => $gatewayTokens->toArray(),
                    ]);
            }

            $gatewayToken = $gatewayTokens->first();

            $gatewayToken->terminal()->associate($payment->terminal);

            $this->repo->saveOrFail($gatewayToken);
        }
        else
        {
            //
            // Not throwing an exception here because it might
            // screw up with the flow. Going to just trace as critical.
            //
            $this->trace->critical(
                TraceCode::GATEWAY_TOKEN_TOO_MANY_PRESENT,
                [
                    'payment_id'            => $payment->getId(),
                    'payment_terminal_id'   => $payment->terminal->getId(),
                    'token_id'              => $token->getId(),
                    'gateway_tokens_count'  => $gatewayTokens->count(),
                    'gateway_tokens'        => $gatewayTokens->toArray()
                ]);
        }
    }

    protected function updateAndNotifyPaymentAuthorized(array $data = [], bool $wasFailed = false)
    {
        // Updates payment entity to authorized and adds a transaction.
        $updated = $this->updatePaymentAuthorized($data, $wasFailed);

        //
        // If payment has not been updated to authorized, we don't fire the webhook
        // or send an email to customer/merchant.
        // This can happen due to race conditions where this function will be called
        // twice. The first time it gets called, it would fire the webhook and notify.
        // We don't need to do that, the second time it gets called.
        //
        if ($updated === false)
        {
            return;
        }

        $this->eventPaymentAuthorized();

        $this->notifyIfCardSaved();

        $this->notifyAuthorized($wasFailed);
    }

    protected function updateAuthorizedOrderStatus(Payment\Entity $payment)
    {
        if ($payment->hasOrder())
        {
            $order = $payment->order;

            $order->setAuthorized(true);

            $this->trace->info(
                TraceCode::ORDER_STATUS_AUTHORIZED,
                [
                    'order_id' => $order->getId(),
                    'payment_id' => $payment->getId(),
                ]);

            $this->repo->saveOrFail($order);
        }
    }

    /**
     * This function is just meant for preparing the return value
     * after payment authorize processing and auto capturing, if applicable.
     *
     * @param Payment\Entity $payment
     * @return array
     */
    protected function postPaymentAuthorizeProcessing(Payment\Entity $payment): array
    {
        $this->updateLateAuthFlag($payment);

        // Auto capture payment, if applicable
        $this->autoCapturePaymentIfApplicable($payment);

        $this->postPaymentAuthorizeSubscriptionProcessing($payment);

        return $this->processAuthorizeResponse($payment);
    }

    protected function postPaymentAuthorizeSubscriptionProcessing(Payment\Entity $payment)
    {
        try
        {
            //
            // The following cannot be in a transaction because we run a capture flow
            // here. We do some processing after the capture too. If something fails
            // after capture, we should not roll back the capture status and other
            // operations that we would have done as part of capture.
            //

            if ($payment->hasSubscription() === false)
            {
                return;
            }

            $subscription = $payment->subscription;

            if ($subscription->isCreated() === true)
            {
                $this->processNewSubscription($subscription, $payment);

                //
                // We update attributes like token and status, which are done outside
                // of the handleCaptureSuccess flow. Hence, we need to save it here
                // explicitly, to ensure that these are saved even if handleCaptureSuccess
                // is not called. handleCaptureSuccess is not called in case there's no
                // addon or isn't a first charge auth txn.
                //
                $this->repo->saveOrFail($subscription);
            }
            else if ($subscription->hasBeenAuthenticated() === true)
            {
                //
                // We don't update any subscription attributes here. The ones which are updated,
                // get saved in a transaction in handleCaptureSuccess function.
                //
                $this->processAlreadyAuthenticatedSubscription($subscription, $payment);
            }
            else
            {
                throw new Exception\LogicException(
                    'The subscription should have been in either created or authenticated state.',
                    null,
                    [
                        'subscription_id'     => $subscription->getId(),
                        'subscription_status' => $subscription->getStatus(),
                        'subscription_auth'   => $subscription->hasBeenAuthenticated(),
                    ]);
            }
        }
        catch (\Exception $ex)
        {
            //
            // If an exception gets thrown here, it would basically mean that
            // capture failed or updating subscription details failed.
            // If capture failed, we should still return back success and
            // handle capture failed scenario later somehow in charge class.
            //
            // Ideally, updating subscription details should never fail.
            // In case it does fail, we return back success and handle updating
            // the subscription details later somehow -- offline data correction.
            //

            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::SUBSCRIPTION_PROCESSING_FAILED,
                [
                    'payment_id' => $payment->getId()
                ]);
        }
    }

    protected function processAlreadyAuthenticatedSubscription(
        Subscription\Entity $subscription,
        Payment\Entity $payment)
    {
        if ($subscription->hasBeenAuthenticated() === false)
        {
            throw new Exception\LogicException(
                'This should have been called only if the subscription was already authenticated.',
                null,
                [
                    'payment_id'            => $payment->getId(),
                    'subscription_id'       => $subscription->getId(),
                    'subscription_status'   => $subscription->getStatus(),
                ]);
        }

        //
        // This means that it's a change card flow
        //
        if (($this->ba->isPublicAuth() === true) and
            ($subscription->isChangeCardStatus() === true))
        {
            $this->processChangeCardForSubscription($subscription, $payment);

            return;
        }

        if ($this->shouldAutoCaptureAlreadyAuthenticatedSubscription($payment) === true)
        {
            $this->autoCapturePayment($payment);

            $invoice = $payment->invoice;

            (new Subscription\Charge)->handleCaptureSuccess($subscription, $payment, $invoice);
        }
    }

    protected function processChangeCardForSubscription(
        Subscription\Entity $subscription,
        Payment\Entity $payment): bool
    {
        //
        // We need this to fire a webhook later.
        //
        $activated = false;

        $oldStatus = $subscription->getStatus();

        if ($oldStatus !== Subscription\Status::ACTIVE)
        {
            $subscription->setStatus(Subscription\Status::ACTIVE);

            $activated = true;
        }

        $this->updateSubscriptionToken($subscription, $payment);

        $this->refundAuthorizedPayment($payment);

        $this->repo->saveOrFail($subscription);

        if ($activated === true)
        {
            (new Subscription\Core)->fireWebhookForStatusUpdate($subscription, Subscription\Status::ACTIVE, $payment);
        }

        return $activated;
    }

    protected function processNewSubscription(Subscription\Entity $subscription, Payment\Entity $payment)
    {
        //
        // We do not need any special handling of late auth payments for subscriptions.
        // If a payment gets late authorized, we don't auto capture it in the normal flow,
        // since, in the function `shouldAutoCapture`, we return a `false` if the
        // payment has a subscription. The subscription's auto capture flow is never
        // called in a late auth case. Hence, no special handling required for late auth.
        // It'll just get auto refunded in a few days, as per the merchant's config.
        //

        //
        // We don't do this in the normal auth and capture flow because we need
        // to do some things after authorization and before capture.
        // And some more things after capture.
        //
        if ($this->shouldAutoCaptureNewSubscription($payment) === true)
        {
            $this->autoCapturePayment($payment);
        }

        $this->updateSubscriptionToken($subscription, $payment);
        $this->updateSubscriptionCustomer($subscription, $payment);

        $subscription->setStatus(Subscription\Status::AUTHENTICATED);

        //
        // We have an explicit check for auth txn charge because we don't want
        // to run `handleCaptureSuccess` for capturing an upfront amount or
        // authorizing just the auth txn amount.
        //
        if ($subscription->isAuthTxnCharge() === true)
        {
            if ($payment->isCaptured() === false)
            {
                throw new Exception\LogicException(
                    'Payment should have been in captured state.',
                    ErrorCode::SERVER_ERROR_SUBSCRIPTION_PAYMENT_NOT_CAPTURED,
                    [
                        'payment_id'        => $payment->getId(),
                        'payment_status'    => $payment->getStatus(),
                        'subscription_id'   => $subscription->getId(),
                    ]);
            }

            //
            // If this is auth txn charge, it means that start_at was null. This,
            // in turn, means that some fields were not filled when the subscription
            // was created. We fill those fields here.
            //
            $this->updateSubscriptionDetails($subscription, $payment);

            $invoice = $payment->invoice;

            (new Subscription\Charge)->handleCaptureSuccess($subscription, $payment, $invoice);
        }

        $this->autoRefundAuthTransactionIfApplicable($payment, $subscription);
    }

    protected function updateSubscriptionDetails(Subscription\Entity $subscription, Payment\Entity $payment)
    {
        $plan = $subscription->plan;

        $subscription->setStartAt($payment->getCreatedAt());

        $subscriptionCore = new Subscription\Core;

        $subscriptionCore->fillScheduleDetailsForNewSubscription($subscription);

        (new Subscription\Creator)->fillEndAtAndTotalCount($subscription, $plan);
    }

    protected function autoRefundAuthTransactionIfApplicable(Payment\Entity $payment, Subscription\Entity $subscription)
    {
        if ($payment->isCaptured() === true)
        {
            return;
        }

        $subscriptionInvoices = $this->repo->invoice->fetchIssuedInvoicesOfSubscription($subscription);

        //
        // If addons are present or start_at is null (first charge in auth txn itself) (invoice created),
        // the payment should have been captured before it reaches this stage.
        //
        if (($payment->isCaptured() === false) and
            ($subscriptionInvoices->count() !== 0))
        {
            throw new Exception\LogicException(
                'The subscription should have been captured by now.',
                null,
                [
                    'subscription_id'   => $subscription->getId(),
                    'payment_id'        => $payment->getId(),
                    'payment_status'    => $payment->getStatus(),
                    'invoice_id'        => $subscriptionInvoices->first()->getId(),
                ]);
        }

        //
        // This would mean that this was a 5rs auth transaction.
        // There was no addon (upfront_amount) or this is not
        // being used as first charge.
        //
        $this->refundAuthorizedPayment($payment);
    }

    protected function updateSubscriptionCustomer(Subscription\Entity $subscription, Payment\Entity $payment)
    {
        $paymentCustomer = $payment->customer;

        $valid = $this->validateSubscriptionState($payment, $paymentCustomer, $subscription);

        if ($valid === false)
        {
            return;
        }

        $this->trace->info(
            TraceCode::SUBSCRIPTION_CUSTOMER_ASSOCIATE,
            [
                'payment_id'        => $payment->getId(),
                'subscription_id'   => $subscription->getId(),
                'customer_id'       => $paymentCustomer->getId(),
            ]);

        //
        // The reason for doing this here and not before authorization
        // is that a payment may fail during an authorization. If we
        // associate the customer to the subscription before itself,
        // we can end up having wrong data and causes issues like
        // re-setting the customer later for the subscription.
        //
        $subscription->customer()->associate($paymentCustomer);
    }

    protected function updateSubscriptionToken(Subscription\Entity $subscription, Payment\Entity $payment)
    {
        $paymentToken = $payment->getGlobalOrLocalTokenEntity();

        $this->trace->info(
            TraceCode::SUBSCRIPTION_TOKEN_ASSOCIATE,
            [
                'payment_id'        => $payment->getId(),
                'subscription_id'   => $subscription->getId(),
                'payment_token_id'  => $paymentToken->getId(),
            ]);

        $subscription->token()->associate($paymentToken);
    }

    protected function validateSubscriptionState(
        Payment\Entity $payment,
        Customer\Entity $paymentCustomer,
        Subscription\Entity $subscription)
    {
        $valid = true;

        $subscriptionCustomer = $subscription->customer;

        //
        // From the second charge onwards, the customer would have
        // already been associated with the subscription.
        // Hence, we don't need to associate it again.
        //
        if ($subscriptionCustomer !== null)
        {
            $this->trace->critical(
                TraceCode::SUBSCRIPTION_CUSTOMER_ALREADY_ASSOCIATED,
                [
                    'payment_id'                => $payment->getId(),
                    'subscription_id'           => $subscription->getId(),
                    'payment_customer_id'       => $paymentCustomer->getId(),
                    'subscription_customer_id'  => $subscriptionCustomer->getId(),
                ]);

            $valid = false;
        }

        //
        // If a customer is not associated with the subscription already,
        // it means that the subscription is in created state, because,
        // no transaction yet happened on this subscription, due to which,
        // there's no customer associated with it yet.
        //
        if ($subscription->isCreated() === false)
        {
            $this->trace->critical(
                TraceCode::SUBSCRIPTION_STATE_UNEXPECTED,
                [
                    'payment_id'            => $payment->getId(),
                    'subscription_id'       => $subscription->getId(),
                    'subscription_status'   => $subscription->getStatus(),
                ]);

            $valid = false;
        }

        return $valid;
    }

    /**
     * Returns the proper response to checkout
     * in case of the payment is authorized
     * @param  Payment\Entity $payment
     * @return array
     */
    protected function processAuthorizeResponse(Payment\Entity $payment): array
    {
        //
        // If callback url has been set, then we need to redirect
        // to the callback url and prepare data using coproto protocol.
        //
        // Otherwise we simply return 'razorpay_payment_id' as is normal.
        //

        $returnData = [
            'razorpay_payment_id' => $payment->getPublicId()
        ];

        //
        // If being run via cron (recurring), we don't care
        // about the signature at all.
        // Also, when run via cron, we cannot create a signature
        // for the payment since the merchant key is not set in scope.
        // The cron key is set in scope.
        // A hacky way to do this would be to override the cron auth
        // with merchant auth. This might cause other issues though.
        //
        if ($this->app['basicauth']->isPrivilegeAuth() === false)
        {
            if ($payment->hasSubscription() === true)
            {
                $this->fillReturnDataWithSubscription($payment, $returnData);
            }
            else if ($payment->hasInvoice() === true)
            {
                assertTrue($payment->hasBeenCaptured() === true);

                $this->fillReturnDataWithInvoice($payment, $returnData);
            }
            else if ($payment->hasOrder() === true)
            {
                if ($payment->order->getPaymentCapture() === true)
                {
                    assertTrue($payment->hasBeenCaptured() === true);
                }

                $this->fillReturnDataWithOrder($payment, $returnData);
            }
        }

        if (($this->app['basicauth']->isPrivateAuth() === false) and
            ($payment->getCallbackUrl()))
        {
            $this->fillReturnRequestDataForMerchant($payment, $returnData);
        }

        return $returnData;
    }

    protected function fillReturnDataWithSubscription(Payment\Entity $payment, array & $data)
    {
        $data['razorpay_subscription_id'] = $payment->subscription->getPublicId();

        $data['razorpay_signature'] = $this->getSignature($data);
    }

    protected function fillReturnDataWithInvoice(Payment\Entity $payment, array & $data)
    {
        $invoice = $payment->invoice;

        //
        // Need to refresh invoice entity as in recordCapture() method
        // post authorization order's invoice association gets updated.
        // And not payment's invoice association. Also there that's
        // needed(using order's invoice) as invoice inherits amount_paid
        // and stuff from order associated.
        //

        $invoice->refresh();

        $data['razorpay_invoice_id']      = $invoice->getPublicId();
        $data['razorpay_invoice_status']  = $invoice->getStatus();
        $data['razorpay_invoice_receipt'] = $invoice->getReceipt();

        $data['razorpay_signature'] = $this->getSignature($data);
    }

    protected function fillReturnDataWithOrder(Payment\Entity $payment, array & $data)
    {
        $data['razorpay_order_id'] = $payment->order->getPublicId();

        $data['razorpay_signature'] = $this->getSignature($data);
    }

    /**
     * @param boolean $wasFailed If a payment is being converted from failed to authorized.
     */
    protected function notifyAuthorized(bool $wasFailed)
    {
        // Trigger notification events for authorization
        $notifier = new Notify($this->payment);

        $hasInvoiceAndNotSubscription = (
            ($this->payment->hasInvoice() === true) and
            ($this->payment->hasSubscription() === false));

        if ($wasFailed)
        {
            $currentTime = Carbon::now()->getTimestamp();

            // If a payment has been authorized 15 minutes after the creation, we do not send a notification.

            if (($this->payment->getCreatedAt() - $currentTime) > self::FAILED_TO_AUTHORIZED_NOTIFY_DURATION)
            {
                return;
            }

            $trigger = $hasInvoiceAndNotSubscription ?
                        Payment\Event::INVOICE_PAYMENT_AUTHORIZED :
                        Payment\Event::FAILED_TO_AUTHORIZED;
        }
        else
        {
            $trigger = $hasInvoiceAndNotSubscription ?
                            Payment\Event::INVOICE_PAYMENT_AUTHORIZED :
                            Payment\Event::AUTHORIZED;
        }

        $notifier->trigger($trigger);
    }

    protected function notifyIfCardSaved()
    {
        $payment = $this->payment;

        if (($payment->isMethod(Payment\Method::CARD)) and
            ($payment->getSave() === true) and
            ($payment->getGlobalTokenId() !== null))
        {
            $notifier = new Notify($this->payment);

            $trigger = Payment\Event::CARD_SAVED;

            $notifier->trigger($trigger);
        }
    }

    protected function eventPaymentAuthorized()
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $this->payment,
        ];

        $this->app['events']->fire('api.payment.authorized', $eventPayload);
    }

    protected function traceAuthorizeFailedOperationData(Payment\Entity $payment)
    {
        $traceData = array(
            'payment_id' => $payment->getId(),
            'error' => $payment->getErrorDetails(),
        );

        $message = 'Payment failed earlier converted to authorized';

        $slackData = ['id' => $payment->getDashboardEntityLinkForSlack()];

        $this->app['slack']->queue(
            $message, $slackData, ['color' => 'good', 'channel' => Config::get('slack.channels.tech_logs')]);

        $this->trace->info(
            TraceCode::PAYMENT_FAILED_TO_AUTHORIZED,
            $traceData);

        $this->segment->trackPayment($payment, TraceCode::PAYMENT_FAILED_TO_AUTHORIZED, $traceData);
    }


    protected function recordTerminalAudit(array $terminalData, Payment\Entity $payment, int $retryAttempts)
    {
        try
        {
            $log = [
                TerminalAnalytics\Entity::PAYMENT_ID    => $terminalData['payment_id'],
                TerminalAnalytics\Entity::TERMINAL_ID   => $terminalData['terminal_id']
            ];

            // convert difference to milliseconds to record as integer
            $responseTime = (int) (($terminalData['end'] - $terminalData['start']) * 1000);

            $log[TerminalAnalytics\Entity::TERMINAL_RESPONSE_TIME] = $responseTime;

            $log[TerminalAnalytics\Entity::PAYMENT_TYPE] = 1;

            $log[TerminalAnalytics\Entity::TERMINAL_STATUS] = 1;

            $errorCode = null;

            $errorMsg = null;

            if (isset($terminalData['exception']))
            {
                $e = $terminalData['exception'];

                $log[TerminalAnalytics\Entity::TERMINAL_STATUS] = 0;

                // we care about this exception, since its an indicator of
                // terminal failure
                $log[TerminalAnalytics\Entity::TERMINAL_STATUS_CODE] = $e->getError()->getHttpStatusCode();

                $log[TerminalAnalytics\Entity::TERMINAL_STATUS_MSG] = $e->getError()->getDescription();
            }

            (new TerminalAnalytics\Core)->create($log);

            $tStatus = $log[TerminalAnalytics\Entity::TERMINAL_STATUS];

            $terminalStatus = ($tStatus === 1) ? TraceCode::TERMINAL_SUCCESS : TraceCode::TERMINAL_FAILURE;

            $log['retry_attempt'] = $retryAttempts;

            $this->segment->trackPayment($payment, $terminalStatus, $log);
        }
        catch(\Exception $e)
        {
            $this->trace->error(
                TraceCode::TERMINAL_ANALYTICS_SAVE_FAILED,
                ['terminalData' => $terminalData]
            );

            $this->trace->traceException($e);
        }
    }

    protected function createAnalyticsLog(Payment\Entity $payment)
    {
        try
        {
            (new Analytics\Service)->createLog($payment);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::WARNING,
                TraceCode::PAYMENT_ANALYTICS_SAVE_FAILED);
        }
    }

    protected function callGatewayAuthorize(array $data)
    {
        return $this->callGatewayFunction(Action::AUTHORIZE, $data);
    }

    /**
     * Do we support the OTP flow for a given payment
     * and input combination
     *
     * @param  Payment\Entity $payment
     *
     * @return bool
     */
    protected function canRunOtpPaymentFlow(Payment\Entity $payment): bool
    {
        // All the IVR terminal use Otp payment flow regardless of their method
        if ($payment->terminal->isIvr() === true)
        {
            return true;
        }

        $wallet = $payment->getWallet();

        // Only wallets have otp flow currently.
        // Plus, only power wallets support otp flow.
        if (($payment->isWallet() === false) or
            (Payment\Gateway::isPowerWallet($wallet) === false))
        {
            return false;
        }

        //
        // Special case check for mobikwik
        // Only checkout currently supports otp flow
        // not on android so we need to check for source. Slightly hacky.
        //

        if ($wallet === Wallet::MOBIKWIK)
        {
            $sources = ['checkoutjs', 's2s'];

            if (in_array($payment->getMetadata('source'), $sources, true) === false)
            {
                return false;
            }
        }

        if (Payment\Gateway::isAuthAndPowerWallet($wallet) === true)
        {
            return $this->isOtpOrAuthFlow($input);
        }

        return true;
    }

    /**
     * For gateways that support both auth and otp flow,
     * determine whether the payment is in auth or otp mode.
     * This is mainly used for the test cases
     *
     * @param array $input
     * @return bool
     */
    protected function isOtpOrAuthFlow(array $input): bool
    {
        if ((isset($input['_']['source']) === true) and
            ($input['_']['source'] === 's2s'))
        {
            return false;
        }

        return true;
    }

    protected function canRunAsyncPaymentFlow($payment)
    {
        if ((Payment\Method::supportsAsync($payment->getMethod()) === true) and
            (Payment\Gateway::supportsAsync($payment->getGateway()) === true))
        {
            return true;
        }

        return false;
    }

    protected function callGatewayOtpGenerate(array $data, Payment\Entity $payment, $otpResend = false)
    {
        try
        {
            $data['otp_resend'] = $otpResend;

            $request = $this->callGatewayFunction(Action::OTP_GENERATE, $data);

            return $this->getPaymentGatewayRequestData($request, $payment);
        }
        catch (Exception\BaseException $e)
        {
            $this->updatePaymentFailed($e, TraceCode::PAYMENT_AUTH_FAILURE);

            throw $e;
        }
    }

    protected function createCardEntity(array $cardInput, bool $vault, Merchant\Entity $merchant)
    {
        //
        // Creates card entity. Card number is vaulted if vault is true
        //

        if ($vault === true)
        {
            $cardInput[Card\Entity::VAULT] = Card\Vault::TOKENEX;
        }

        $cardCore = new Card\Core;

        $cardData = $cardCore->createAndReturnWithSensitiveData($cardInput, $merchant);

        $card = $cardCore->getCard();

        if ($card->isUnsupported())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED);
        }

        $this->payment->card()->associate($card);

        $this->repo->saveOrFail($card);

        return $cardData;

    }

    /**
     * creates gateway input using saved card token, this method is used for
     * local card saving and we can associate the same card with the payment
     *
     * @param Token\Entity $token
     * @param array        $input
     *
     * @return array
     * @throws \Exception
     */
    protected function associateAndGetCardArrayForSavedToken(Token\Entity $token, array & $input): array
    {
        $card = $this->repo->card->fetchForToken($token);

        $cardNumber = (new Card\Tokenex)->getCardNumber($card->getVaultToken());

        // Recurring terminals accept null cvv.
        $cvv = isset($input['card']['cvv']) ? $input['card']['cvv'] : null;

        $this->payment->card()->associate($card);

        return array_merge(
                $card->toArray(),
                [
                    'number' => $cardNumber,
                    'cvv' => $cvv
                ]);
    }

    /**
     * creates gateway input using saved card token, this method is used for
     * global card saving. we need to create a new card entity for merchant
     * and associate with the payment
     *
     * @param Token\Entity $token
     * @param array        $input
     *
     * @return array
     * @throws \Exception
     */
    protected function createCardEntityFromSavedToken(Token\Entity $token, array & $input): array
    {
        $card = $this->repo->card->fetchForToken($token);

        $cardNumber = (new Card\Tokenex)->getCardNumber($card->getVaultToken());

        $cvv = isset($input['card']['cvv']) ? $input['card']['cvv'] : null;

        $savedCard = $token->card->toArray();
        $savedCard['number'] = $cardNumber;
        $savedCard['cvv'] = $cvv;

        // Create a card entity for merchant
        $cardCore = new Card\Core;

        $card = $cardCore->createDuplicateCard($savedCard, $this->merchant);

        $this->payment->card()->associate($card);

        return array_merge(
            $card->toArray(),
            [
                'number' => $cardNumber,
                'cvv' => $cvv
            ]);
    }

    protected function verifyBankEnabled(Payment\Entity $payment)
    {
        $merchant = $payment->merchant;

        $merchantMethods = (new Methods\Core)->getMethods($merchant);

        $merchantBanks = ($merchantMethods === null) ? [] : $merchantMethods->getSupportedBanks();

        $paymentBank = $payment->getBank();

        if (in_array($paymentBank, $merchantBanks, true) === false)
        {
            $customProperties = [
                'merchant_banks' => $merchantBanks,
                'payment_bank' => $paymentBank,
            ];

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_NOT_ENABLED_FOR_MERCHANT);
        }
    }

    protected function verifyWalletEnabled(Payment\Entity $payment)
    {
        $merchantMethods = $this->methods;

        $paymentWallet = $payment->getWallet();

        if (($merchantMethods === null) or
            ($merchantMethods->isWalletEnabled($paymentWallet) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_ENABLED_FOR_MERCHANT);
        }
    }

    protected function verifyEmiEnabled(Payment\Entity $payment)
    {
        $merchantMethods = $this->methods;

        if (($merchantMethods === null) or
            ($merchantMethods->isEmiEnabled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_EMI_NOT_ENABLED_FOR_MERCHANT);
        }

        $this->checkAndValidateAmexIfNotEnabled($merchantMethods, $payment->card);
    }

    protected function verifyUpiEnabled()
    {
        $merchantMethods = $this->methods;

        if (($merchantMethods === null) or
            ($merchantMethods->isUPIEnabled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_NOT_ENABLED_FOR_MERCHANT);
        }
    }

    protected function verifyBankTransferEnabled()
    {
        $merchantMethods = $this->methods;

        if (($merchantMethods === null) or
            ($merchantMethods->isBankTransferEnabled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_TRANSFER_NOT_ENABLED_FOR_MERCHANT);
        }
    }

    protected function verifyAepsEnabled()
    {
        $merchantMethods = $this->methods;

        if (($merchantMethods === null) or
            ($merchantMethods->isAepsEnabled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_AEPS_NOT_ENABLED_FOR_MERCHANT);
        }
    }

    protected function verifyCardEnabledInLive(Payment\Entity $payment)
    {
        $card = $payment->card;

        $merchantMethods = $this->methods;

        $this->checkAndValidateAmexIfNotEnabled($merchantMethods, $card);

        // Only check enabled or not on live mode
        if ($this->mode === Mode::TEST)
        {
            return;
        }

        if ($merchantMethods->isCardEnabled() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_NOT_ENABLED_FOR_MERCHANT);
        }

        $type = $card->getType();

        if ($type === Card\Type::UNKNOWN)
        {
            return;
        }

        $type = ucfirst($type);

        $func = 'is' . $type . 'CardEnabled';

        if ($merchantMethods->$func() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $type . ' card transactions are not allowed',
                'number');
        }
    }

    protected function verifyFeatureForMerchant(Merchant\Entity $merchant, $feature)
    {
        if ($merchant->isFeatureEnabled($feature) === false)
        {
            // If feature is not present, simply throw invalid url error.
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }
    }

    protected function verifyAtLeastOneFeatureEnabledForMerchant(Merchant\Entity $merchant, array $features)
    {
        $atLeastOneEnabled = false;

        foreach ($features as $feature)
        {
            if ($merchant->isFeatureEnabled($feature) === true)
            {
                $atLeastOneEnabled = true;

                break;
            }
        }

        if ($atLeastOneEnabled === false)
        {
            //
            // If not even one of the features is enabled for the merchant,
            // throw an invalid URL error
            //
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }

        return $atLeastOneEnabled;
    }

    protected function validateCardAndCvv(Payment\Entity $payment, array $input)
    {
        // if not recurring, validate that card data and cvv in card data is present
        if (($payment->isRecurring() === false) and
            ($payment->getTokenId() !== null) and
            ($payment->localToken->isRecurring() === false))
        {
            $payment->getValidator()->validateCardAndCvv($input);
        }
    }

    protected function validateUpiPspIsAllowed(Payment\Entity $payment)
    {
        $disallowedPspJson = $this->cache->get(Upi\Core::EXCLUDED_PSPS, '[]');

        $disallowedPsps = json_decode($disallowedPspJson, true);

        $payment->getValidator()->validateUpiVpaPsp(
            $payment->getVpa(), $disallowedPsps);
    }

    protected function checkAndValidateAmexIfNotEnabled($methods, $card)
    {
        $amex = $methods->getAmex();

        if (($card->isAmex() === true) and
            ($amex === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED,
                'number');
        }
    }

    protected function updatePaymentAuthorized($data = [], bool $wasFailed = false)
    {
        $payment = $this->payment;

        $updated = $this->repo->transaction(function() use ($payment, $data, $wasFailed)
        {
            $this->lockForUpdateAndReload($payment);

            $status = $this->payment->getStatus();

            // We do not want the payments which failed captured
            // and got marked as failed to be authorized again.
            if ($payment->hasBeenAuthorized() === true)
            {
                return false;
            }

            $payment->setErrorNull();

            $payment->setAmountAuthorized();

            $payment->setStatus(Payment\Status::AUTHORIZED);

            $payment->setAuthorizeTimestamp();

            $this->updateAcquirerData($payment, $data);

            // If payment was earlier failed, then that means it's
            // getting authorized late.
            $payment->setLateAuthorized($wasFailed);

            $this->trace->info(
                TraceCode::PAYMENT_STATUS_AUTHORIZED,
                [
                    'payment_id'        => $payment->getId(),
                    'late_authorize'    => $wasFailed,
                    'old_status'        => $status,
                ]);

            //
            // If gateway is authorizing the payment (basically, no authAndCapture support), create transaction.
            //
            if ($this->isGatewayActuallyAuthorizingPayment($payment) === false)
            {
                $payment->setGatewayCaptured(true);

                // Also sets the transaction association with the payment.
                // Fee Split would be null, as its the dummy transaction, so we are not saving fee split.
                list($txn, $feesSplit) = (new Transaction\Core)->createFromPaymentAuthorized($payment);

                $this->repo->saveOrFail($txn);
            }

            $this->repo->saveOrFail($payment);

            if ($payment->terminal !== null)
            {
                $payment->terminal->setUsed();

                $this->repo->saveOrFail($payment->terminal);
            }

            $this->updateAssociatedPaymentEntities($payment, $data);

            $customProperties = $payment->toArrayTraceRelevant();

            $this->segment->trackPayment($payment, TraceCode::PAYMENT_AUTH_SUCCESS, $customProperties);

            $this->tracePaymentInfo(TraceCode::PAYMENT_AUTH_SUCCESS);

            return true;
        });

        return $updated;
    }

    protected function updateAcquirerData(Payment\Entity $payment, $data = [])
    {
        // We don't want the acquirer update to fail the payment
        // This can happen if validation check fails.
        try
        {
            if (isset($data['acquirer']) === true)
            {
                $payment->edit($data['acquirer']);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::ERROR_EXCEPTION,
                $data['acquirer']);
        }

    }

    protected function updateAssociatedPaymentEntities(Payment\Entity $payment, array $data)
    {
        $this->updateTokenOnAuthorized($payment, $data);

        //
        // If payment has an associated order
        // set the order to be paid
        //
        $this->updateAuthorizedOrderStatus($payment);
    }

    protected function isGatewayActuallyAuthorizingPayment(Payment\Entity $payment): bool
    {
        // No gateway for bank transfer, everything is internal
        if ($payment->isBankTransfer() === true)
        {
            return false;
        }

        $terminalMode = $payment->terminal->getMode();

        if ($terminalMode === Terminal\Mode::AUTH_CAPTURE)
        {
            return true;
        }
        else if ($terminalMode === Terminal\Mode::PURCHASE)
        {
            return false;
        }

        // We handle dual and null terminal mode as the default case
        // In the default case, we check if the card network supports
        // purchase or auth+capture. Example. FSS uses Auth and capture
        // for MC and VISA and purchases for RUPAY, DICL, and MAESTRO
        $gateway = $payment->getGateway();

        $networkCode = null;

        // If payment method is wallet or net banking.
        if ($payment->hasCard())
        {
            $networkCode = $payment->card->getNetworkCode();
        }

        return Payment\Gateway::supportsAuthAndCapture($gateway, $networkCode);
    }

    protected function getEncryptedGatewayText(string $gateway)
    {
        return Crypt::encrypt($gateway . '__' . time());
    }

    protected function verifyHash(string $inputHash, string $paymentPublicId)
    {
        $expectedHash = $this->getHashOf($paymentPublicId);

        if (hash_equals($expectedHash, $inputHash) !== true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Callback payment hash does not match. Please notify the admin of this error.');
        }
    }

    /**
     * Creates the callback url for payment
     * where the gateway can hit back to say payment
     * is finished/authorized.
     *
     * @return string Callback url
     */
    protected function getCallbackUrl(): string
    {
        $params = $this->getPaymentIdAndHashParams();

        $callbackUrl = $this->route->getUrlWithPublicCallbackAuth($params);

        return $callbackUrl;
    }

    protected function getOtpSubmitUrl(): string
    {
        $params = $this->getPaymentIdAndHashParams();

        $otpSubmitUrl = $this->route->getUrlWithPublicAuth('payment_otp_submit', $params);

        return $otpSubmitUrl;
    }

    protected function getPaymentIdAndHashParams(): array
    {
        $publicId = $this->payment->getPublicId();

        $hash = $this->getHashOf($publicId);

        return ['id' => $publicId, 'hash' => $hash];
    }

    /**
     * Returns a hash of a string.
     *
     * @param string $string
     * @return string Hash of the string
     */
    protected function getHashOf(string $string): string
    {
        $secret = $this->app->config->get('app.key');

        return hash_hmac('sha1', $string, $secret);
    }
}
