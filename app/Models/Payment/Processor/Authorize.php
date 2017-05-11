<?php

namespace RZP\Models\Payment\Processor;

use App;
use Carbon\Carbon;
use Config;
use Crypt;
use Lib\PhoneBook;
use Mail;
use RZP\Constants\Mode;
use RZP\Http\BasicAuth;
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
use RZP\Models\Terminal;
use RZP\Models\Transaction;
use RZP\Models\Upi;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

trait Authorize
{
    /**
     * There are different ways of doing payment authorization.
     */
    protected $type;

    public function authorize(Payment\Entity $payment, array $input): array
    {
        $this->verifyMerchantIsLiveForLiveRequest();

        $gatewayInput = [];

        $this->processCurrencyConversions($payment);

        // $gatewayInput is being passed by reference.
        // Adds callback url, payment and card info to $gatewayInput
        $this->runPaymentMethodRelatedPreProcessing($payment, $input, $gatewayInput);

        $this->runPaymentInputValidations($payment, $input);

        $this->validateOfferIfApplicable($payment);

        $this->selectedTerminals = (new TerminalProcessor)->getTerminalsForPayment($payment);

        return $this->authorizeAcrossTerminals($payment, $input, $gatewayInput);
    }

    protected function authorizeAcrossTerminals(Payment\Entity $payment, array $input, array $gatewayInput): array
    {
        $totalTerminals = count($this->selectedTerminals);

        $maxRetryAttempts = min($totalTerminals, self::MAX_RETRY_ATTEMPTS);

        $retryAttempts = 0;

        $request = null;

        $retry = false;

        //
        // We are attempting to rotate across multiple terminals to get a successful payment here.
        // For each of the terminals tried, we want to record the terminal metrics using recordTerminalAudit()
        // At the end of a successful/failed payment, we want to record the payment details
        // using createAnalyticsLog. There could be cases where terminal #1 failed and terminal #2 succeeded.
        // In the above scenario, we will have 2 records in terminal analytics, but only one record
        // for the entire payment in payment analytics. The terminal chosen here in payment analytics
        // will be the last terminal tried.
        //

        while ($retryAttempts < $maxRetryAttempts)
        {
            $terminalGatewayInput = $gatewayInput;

            $currentTerminal = $this->selectedTerminals[$retryAttempts];

            $payment->associateTerminal($currentTerminal);

            $this->runPostGatewaySelectionPreProcessing($payment, $terminalGatewayInput);

            $segmentCustomProps = [
                'selected_terminal' => $currentTerminal->getId(),
                'retry_attempt' => $retryAttempts
            ];

            $this->segment->trackPayment($payment, TraceCode::GATEWAY_POSTPROCESSING, $segmentCustomProps);

            if ($this->canRunOtpPaymentFlow($payment, $input))
            {
                $this->createAnalyticsLog($payment);

                $request = $this->runOtpPaymentFlow($terminalGatewayInput, $payment);

                return $request;
            }

            // data for terminal analytics
            $terminalData = [
                            'payment_id'    => $payment['id'],
                            'input'         => $input,
                            'terminal_id'   => $payment['terminal_id'],
                            'start'         => microtime(true),
                        ];

            try
            {
                $request = $this->callGatewayAuthorize($terminalGatewayInput);

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

                $this->updatePaymentAuthFailedAndThrowException($e);
            }
            finally
            {
                $terminalData['end'] = microtime(true);

                $this->recordTerminalAudit($terminalData, $payment, $retryAttempts);

                if (($retry === false) or
                    ($retryAttempts >= $maxRetryAttempts))
                {
                    $this->createAnalyticsLog($payment);
                }
            }
        }

        return $this->processAuthResponse($request, $payment);
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

    protected function updatePaymentAuthFailedAndThrowException($e)
    {
        $this->updatePaymentFailed($e, TraceCode::PAYMENT_AUTH_FAILURE);

        throw $e;
    }

    protected function verifyFeesLessThanAmount(Payment\Entity $payment)
    {
        // try calculating the fees, throws exception if fees is more than amount

        list($fee, $serviceTax, $feesSplit) = (new Pricing\Fee)->calculateMerchantFees($payment);
    }

    /**
     * @param array|null     $request
     * @param Payment\Entity $payment
     *
     * @return array|mixed
     */
    protected function processAuthResponse($request, Payment\Entity $payment): array
    {
        //
        // If $request is not null, then payment is two-step process
        // where client needs to provide additional info via his browser.
        //
        if ($request !== null)
        {
            return $this->getPaymentGatewayRequestData($request, $payment);
        }

        $this->updateAndNotifyPaymentAuthorized();

        $this->updateTwoFactorAuthForOneStepPayment();

        $payment = $this->payment;

        return $this->postPaymentAuthorizeProcessing($payment);
    }

    protected function updateTwoFactorAuthForOneStepPayment()
    {
        $payment = $this->payment;

        // In one step payment, we always set the 2FA as unavailable. Basically, no 2FA done.
        // Except in the cases of recurring, because, here we know that
        // we have manually skipped/by-passed the 2FA.

        if ($payment->terminal->isNon3DSRecurring() === true)
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

            $flag = $this->callGatewayFunction('forceAuthorizeFailed', $data);

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
            $this->updateAndNotifyPaymentAuthorized(true);

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
    }

    protected function validateSubscriptionInputIfPresent(Payment\Entity $payment)
    {
        //
        // Subscription association to payment happens in pre-process
        //
        if ($payment->subscription === null)
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
                    'subscription_id'   => $payment->subscription->getId(),
                ]);
        }

        $subscription = $payment->subscription;

        if ($subscription->isExpired() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_EXPIRED,
                null,
                [
                    'subscription_id' => $subscription->getId(),
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
        $publicAuth = $this->app['basicauth']->isPublicAuth();

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

        if ($payment->isWallet() === true)
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

        // Validate that the card supports recurring
        $this->validateRecurringCard($payment);

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
            $this->verifyAuthForRecurring();

            $this->verifyAggregatorIfApplicable($merchant);
        }
    }

    protected function verifyFeatureForRecurring(Merchant\Entity $merchant, Payment\Entity $payment)
    {
        $authType = $this->app['basicauth']->getAuthType();

        if ($authType === BasicAuth\Type::PRIVATE_AUTH)
        {
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
                    $merchant, [Feature\Constants::SUBSCRIPTIONS, Feature\Constants::RECURRING]);
            }
            else
            {
                // Merchants with subscriptions feature cannot make S2S calls
                // for recurring payments.
                $this->verifyFeatureForMerchant($merchant, Feature\Constants::RECURRING);
            }
        }
        else if ($authType === BasicAuth\Type::PUBLIC_AUTH)
        {
            // Public payments can be made for recurring for merchants with either
            // subscriptions or recurring features enabled.
            $this->verifyAtLeastOneFeatureEnabledForMerchant(
                $merchant, [Feature\Constants::SUBSCRIPTIONS, Feature\Constants::RECURRING]);
        }
        else if ($authType === BasicAuth\Type::PRIVILEGE_AUTH)
        {
            // Privilege auth for recurring should be used only for merchants
            // who have subscriptions.
            // But, since it's privilege auth, it can be used for merchants with
            // recurring feature also, but no requirement right now.
            $this->verifyFeatureForMerchant($merchant, Feature\Constants::SUBSCRIPTIONS);
        }
        else
        {
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

    protected function validateOfferIfApplicable(Payment\Entity $payment)
    {
        (new Offer\Core)->validateOfferApplicableOnPayment($payment);
    }

    protected function runPostGatewaySelectionPreProcessing($payment, array & $gatewayInput)
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
        if ($payment->getTokenId() !== null)
        {
            $gatewayInput['token'] = $payment->localToken;
        }

        $customProperties = [
            'otpSubmitUrl' => $this->getOtpSubmitUrl(),
            'callbackUrl' => $this->getCallbackUrl()
        ];

        $this->segment->trackPayment($payment, TraceCode::GATEWAY_SELECTION_PREPROCESSING, $customProperties);
    }

    protected function dummyPrePaymentAuthorizeProcessing($payment, $input)
    {
        $gatewayInput = [];

        $this->processCurrencyConversions($payment);

        $this->runPaymentMethodRelatedPreProcessing($payment, $input, $gatewayInput);
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

        $this->validateFraudDetection($payment);

        $this->validateBlockedInternationalCard($payment->card);
    }

    protected function validateInternationalAllowed(Payment\Entity $payment)
    {
        $merchant = $payment->merchant;

        if ($merchant->isInternational() === false)
        {
            $e = new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_INTERNATIONAL_NOT_ALLOWED);

            $this->updatePaymentFailed($e, TraceCode::PAYMENT_AUTH_FAILURE);

            throw $e;
        }
    }

    protected function validateBlockedInternationalCard(Card\Entity $card)
    {
        if ($card->isBlocked())
        {
            $e = new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD);

            $this->updatePaymentFailed($e, TraceCode::PAYMENT_AUTH_FAILURE);

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

            $flag = $this->callGatewayFunction('authorizeFailed', $data);

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
            $this->updateAndNotifyPaymentAuthorized(true);

            $this->autoCapturePaymentIfApplicable($payment);

            $this->repo->saveOrFail($payment);

            $this->setPayment($payment);
        });
    }

    protected function verifyAuthForRecurring()
    {
        if (($this->app['basicauth']->isPrivateAuth() === false) and
            ($this->app['basicauth']->isPrivilegeAuth() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_RECURRING_AUTH_NOT_SUPPORTED);
        }
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
        }

        $amount = $payment->getAmount();

        $baseAmount = (new Currency\Core)->getBaseAmount($amount, $currency);

        $payment->setBaseAmount($baseAmount);

        if ($payment->isCard() === true)
        {
            $payment->setConvertCurrency($merchant->convertOnApi());
        }
    }

    /**
     * @param Payment\Entity $payment
     * @param array $input Input data received from checkout/merchant.
     * @param array $gatewayInput Data that is required by gateway for the payment to be processed.
     */
    protected function runPaymentMethodRelatedPreProcessing(Payment\Entity $payment, & $input, array & $gatewayInput)
    {
        $this->associateSubscriptionIfApplicable($payment, $input);

        $this->setCustomerIdForSubscriptionInput($payment, $input);

        //
        // Either the customer ID or the app token ID is required to get the customer.
        // Hence, fill the app token in the input if customer ID is not present.
        //
        if (empty($input[Payment\Entity::CUSTOMER_ID]) === true)
        {
            $this->checkAndFillSavedAppToken($input);
        }

        // First fetch the relevant customer
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
            $this->preProcessPaymentForGlobalCustomer($customer, $customerApp, $payment, $input, $gatewayInput);
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
    }

    protected function associateSubscriptionIfApplicable(Payment\Entity $payment, array $input)
    {
        if (empty($input[Payment\Entity::SUBSCRIPTION_ID]) === true)
        {
            return;
        }

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
        // No card saving, normal simple flow
        if ($payment->isMethodCardOrEmi())
        {
            $payment->setSave(false);

            $payment->setRecurring(false);

            $vault = $payment->isEmi();

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
        if (empty($input[Payment\Entity::TOKEN]) === false)
        {
            $this->preProcessPaymentFromSavedCardLocal($customer, $payment, $input, $gatewayInput);
        }
        else
        {
            $this->preProcessPaymentFromUserDataLocal($customer, $payment, $input, $gatewayInput);
        }
    }

    protected function preProcessPaymentForGlobalCustomer(Customer\Entity $customer,
                                                          Customer\AppToken\Entity $customerApp,
                                                          Payment\Entity $payment,
                                                          array & $input,
                                                          array & $gatewayInput)
    {
        $this->payment->app()->associate($customerApp);

        $this->payment->globalCustomer()->associate($customer);

        // If token is set, then pay using global saved card
        if (empty($input[Payment\Entity::TOKEN]) === false)
        {
            $this->preProcessPaymentFromSavedCardGlobal($customer, $payment, $input, $gatewayInput);
        }
        else
        {
            // Does processing like creating card entity, saving card if passed in the input, etc..
            $this->preProcessPaymentFromUserDataGlobal($customer, $payment, $input, $gatewayInput);
        }
    }

    protected function preProcessPaymentFromSavedCardLocal(Customer\Entity $customer,
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

        //else @todo for netbanking/wallets
    }

    protected function preProcessPaymentFromSavedCardGlobal(Customer\Entity $customer,
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
            $payment->setBank($token->getBank());
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
        // Flow if card details are entered with save set to true/false
        $saveMethod = $payment->getSave();

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
        // create local saved card and link to payment
        $gatewayInput['card'] = $this->createCardEntity($input['card'], true, $customer->merchant);

        $savedLocalCard = $payment->card;

        // save local saved card for local customer
        $token = $this->savePaymentMethod($customer, $payment, $savedLocalCard->getId());

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

        if ($token !== null)
        {
            $this->payment->globalToken()->associate($token);
        }
    }

    protected function savePaymentMethod(Customer\Entity $customer, Payment\Entity $payment, $savedCardId): Token\Entity
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

        $saveMethodInput = array(
            'method' => $payment->getMethod(),
        );

        if ($payment->isMethodCardOrEmi())
        {
            $saveMethodInput[Token\Entity::METHOD] = Payment\Method::CARD;

            $saveMethodInput[Token\Entity::CARD_ID] = $savedCardId;
        }
        else if ($payment->isMethod(Payment\Method::NETBANKING))
        {
            $saveMethodInput[Token\Entity::BANK] = $payment->getBank();
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

        IIN\IIN::validateEmiAvailableForCard($iinEntity, $cardNumber);

        $payment->setBank($iinEntity->getIssuer());

        // Set emi plan id
        $emiPlan = $this->repo->emi_plan->fetchRelevantEmiPlan(
                                            $iinEntity, $emiDuration);

        $payment->getValidator()->validateMinAmountWithEmiPlanAmount($emiPlan);

        $payment->emiPlan()->associate($emiPlan);
    }

    protected function fillReturnRequestDataForMerchant(Payment\Entity $payment, array & $returnData)
    {
        assert ($payment->getCallbackUrl() !== null);

        // This would be normal request data at this point.
        // But since we will be redirecting to merchant's callback url
        // we need to push the request data into coproto structure
        // so that controller can then redirect peacefully.
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
            $input['app_token'] = $appToken;
        }
    }

    protected function setCustomerIdForSubscriptionInput(Payment\Entity $payment, array & $input)
    {
        if (empty($input[Payment\Entity::SUBSCRIPTION_ID]) === false)
        {
            $subscription = $payment->subscription;

            $input[Payment\Entity::CUSTOMER_ID] = Customer\Entity::getSignedId($subscription->getCustomerId());
        }
    }

    protected function getPaymentGatewayRequestData($request, Payment\Entity $payment): array
    {
        if ((Payment\Method::supportsAsync($payment->getMethod()) === true) and
            (Payment\Gateway::supportsAsync($payment->getGateway()) === true))
        {
            return $this->getAsyncPaymentCreatedResponse($request, $payment);
        }

        return $this->getFirstPaymentCreatedResponse($request, $payment);
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

        $amount = $payment->getAmount() / 100;

        $data['amount'] = sprintf($amount == intval($amount) ? '%d' : '%.2f', $amount);

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

    protected function updateTokenOnAuthorized()
    {
        $payment = $this->payment;

        $token = $this->repo->token->getGlobalOrLocalTokenEntityOfPayment($payment);

        $this->trace->info(
            TraceCode::PAYMENT_UPDATE_TOKEN,
            [
                'payment_id'      => $payment->getId(),
                'token_id'        => $payment->getTokenId(),
                'global_token_id' => $payment->getGlobalTokenId()
            ]);

        // update token stats, assuming same token is not getting used in
        // multiple payments, actually we should locking
        if ($token !== null)
        {
            $createdAt = $payment->getCreatedAt();

            $token->setUsedAt($createdAt);

            $token->incrementUsedCount();

            if (($token->isLocal()) and
                ($payment->isCard()) and
                ($payment->isRecurring() === true) and
                ($token->isRecurring() === false))
            {
                $token->setRecurring(true);

                $token->terminal()->associate($payment->terminal);
            }

            $this->repo->saveOrFail($token);
        }
    }

    protected function updateAndNotifyPaymentAuthorized(bool $wasFailed = false)
    {
        // Updates payment entity to authorized and adds a transaction.
        $updated = $this->updatePaymentAuthorized($wasFailed);

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
        if ($this->app['basicauth']->isPublicAuth() === true)
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
            (new Subscription\Core)->fireWebhookForStatusUpdate($subscription, Subscription\Status::ACTIVE);
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
        // There was no addon (upfront_amount) or this is not being used as first charge.
        //
        $this->refundAuthorizedPayment($payment);
    }

    protected function updateSubscriptionToken(Subscription\Entity $subscription, Payment\Entity $payment)
    {
        // TODO: This will have to be fixed when we bring in global for subscriptions
        $paymentToken = $payment->localToken;

        $this->trace->info(
            TraceCode::SUBSCRIPTION_TOKEN_ASSOCIATE,
            [
                'payment_id'        => $payment->getId(),
                'subscription_id'   => $subscription->getId(),
                'payment_token_id'  => $paymentToken->getId(),
            ]);

        //
        // We are commenting this piece of code because token can be associated even if is
        // in active state and not only in created state. Basically, a change card/token flow.
        //

        // $valid = $this->validateSubscriptionState($payment, $paymentToken, $subscription);

        // if ($valid === false)
        // {
        //     return;
        // }

        $subscription->token()->associate($paymentToken);
    }

    protected function validateSubscriptionState(
        Payment\Entity $payment,
        Token\Entity $paymentToken,
        Subscription\Entity $subscription)
    {
        $valid = true;

        $subscriptionToken = $subscription->token;

        //
        // From the second charge onwards, the token would have already been
        // associated with the subscription.
        // Hence, we don't need to associate it again.
        //
        if ($subscriptionToken !== null)
        {
            $this->trace->info(
                TraceCode::SUBSCRIPTION_TOKEN_ALREADY_ASSOCIATED,
                [
                    'payment_id'            => $payment->getId(),
                    'subscription_id'       => $subscription->getId(),
                    'payment_token_id'      => $paymentToken->getId(),
                    'subscription_token_id' => $subscriptionToken->getId(),
                ]);

            $valid = false;
        }

        //
        // If a token is not associated with the subscription already,
        // it means that the subscription is in created state, because,
        // no transaction yet happened on this subscription, due to which,
        // there's no token associated with it yet.
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
        if (($payment->getApiOrderId() !== null) and
            ($this->app['basicauth']->isPrivilegeAuth() === false))
        {
            $this->fillReturnDataWithOrder($payment, $returnData);
        }

        if (($this->app['basicauth']->isPrivateAuth() === false) and
            ($payment->getCallbackUrl()))
        {
            $this->fillReturnRequestDataForMerchant($payment, $returnData);
        }

        return $returnData;
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
            $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

            // If a payment has been authorized 15 minutes after the creation, we do not send a notification.

            if (($this->payment->getCreatedAt() - $currentTime) > self::FAILED_TO_AUTHORIZED_NOTIFY_DURATION)
            {
                return;
            }

            $trigger = $hasInvoiceAndNotSubscription ? Notify::INVOICE_PAYMENT_AUTHORIZED : Notify::FAILED_TO_AUTHORIZED;
        }
        else
        {
            $trigger = $hasInvoiceAndNotSubscription ? Notify::INVOICE_PAYMENT_AUTHORIZED : Notify::AUTHORIZED;
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

            $trigger = Notify::CARD_SAVED;

            $notifier->trigger($trigger);
        }
    }

    protected function eventPaymentAuthorized()
    {
        $this->app['events']->fire('api.payment.authorized', [$this->payment]);
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
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::WARNING,
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
     * @param  Payment\Entity $payment
     * @param  array $input
     * @return boolean
     */
    protected function canRunOtpPaymentFlow(Payment\Entity $payment, array $input): bool
    {
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

            if ((isset($input['_']['source']) === false) or
                (in_array($input['_']['source'], $sources, true) === false))
            {
                return false;
            }
        }

        return true;
    }

    protected function runOtpPaymentFlow(array $gatewayInput, Payment\Entity $payment)
    {
        return $this->callGatewayOtpGenerate($gatewayInput, $payment);
    }

    protected function callGatewayOtpGenerate(array $data, Payment\Entity $payment, $otpResend = false)
    {
        try
        {
            $data['otp_resend'] = $otpResend;

            $request = $this->callGatewayFunction(Action::OTP_GENERATE, $data);

            return $this->processOtpFlowResponse($request, $payment);
        }
        catch (Exception\BaseException $e)
        {
            $this->updatePaymentFailed($e, TraceCode::PAYMENT_AUTH_FAILURE);

            throw $e;
        }
    }

    protected function processOtpFlowResponse($request, Payment\Entity $payment): array
    {
        if ($request !== null)
        {
            $payment->incrementOtpCount();

            $payment->save();

            $response = [
                'type' => 'otp',
                'request' => $request,
                'version' => 1,
                'payment_id' => $payment->getPublicId(),
                'gateway' => $this->getEncryptedGatewayText($payment->getGateway()),
                // TODO: Return metadata in a better format
                'contact' => $payment->getContact(),
                'amount'  => number_format(($payment->getAmount() / 100), 2),
                'wallet'  => $payment->getWallet()
            ];

            $this->segment->trackPayment($payment, TraceCode::OTP_GENERATE, $response);

            return $response;
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

        //create a card entity for merchant
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

        $merchantBanks = ($merchantMethods === null) ? [] : $merchantMethods->getBanks();

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
                ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_ENALBED_FOR_MERCHANT);
        }
    }

    protected function verifyEmiEnabled(Payment\Entity $payment)
    {
        $merchantMethods = $this->methods;

        if (($merchantMethods === null) or
            ($merchantMethods->isEmiEnabled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_EMI_NOT_ENALBED_FOR_MERCHANT);
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
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_NOT_ENALBED_FOR_MERCHANT);
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

    protected function updatePaymentAuthorized(bool $wasFailed = false)
    {
        $payment = $this->payment;

        $updated = $this->repo->transaction(function() use ($payment, $wasFailed)
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

            $payment->terminal->incrementUsedCount();

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

            $this->repo->saveOrFail($payment->terminal);

            $this->updateAssociatedPaymentEntities($payment);

            $customProperties = $payment->toArrayTraceRelevant();

            $this->segment->trackPayment($payment, TraceCode::PAYMENT_AUTH_SUCCESS, $customProperties);

            $this->tracePaymentInfo(TraceCode::PAYMENT_AUTH_SUCCESS);

            return true;
        });

        return $updated;
    }

    protected function updateAssociatedPaymentEntities(Payment\Entity $payment)
    {
        $this->updateTokenOnAuthorized();

        // If payment has an associated order
        // set the order to be paid
        $this->updateAuthorizedOrderStatus($payment);
    }

    protected function isGatewayActuallyAuthorizingPayment(Payment\Entity $payment): bool
    {
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
