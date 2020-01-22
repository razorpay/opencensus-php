<?php

namespace RZP\Models\Payment\Processor;

use App;
use Mail;
use Cache;
use Crypt;
use Config;
use Route;
use Carbon\Carbon;
use Lib\PhoneBook;

use RZP\Jobs;
use RZP\Exception;
use RZP\Models\Upi;
use RZP\Models\Emi;
use RZP\Models\Base;
use RZP\Models\Risk;
use RZP\Models\Card;
use RZP\Models\Admin;
use RZP\Models\Offer;
use RZP\Constants\TLD;
use RZP\Diag\EventCode;
use RZP\Http\BasicAuth;
use RZP\Models\Pricing;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Models\Address;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Currency;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Models\Customer;
use RZP\Models\Discount;
use RZP\Models\Card\IIN;
use RZP\Services\Doppler;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Entity;
use RZP\Models\Transaction;
use RZP\Models\PaymentLink;
use RZP\Constants\Timezone;
use RZP\Models\PaymentsUpi;
use RZP\Constants\Environment;
use RZP\Models\Card\Network;
use RZP\Jobs\RunShieldCheck;
use RZP\Models\EntityOrigin;
use RZP\Models\Payment\Action;
use RZP\Models\Payment\Method;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Gateway;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Methods;
use RZP\Models\Plan\Subscription;
use RZP\Models\Payment\Analytics;
use Razorpay\Trace\Logger as Trace;
use RZP\Services\CardPaymentService;
use RZP\Models\Payment\TwoFactorAuth;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Customer\GatewayToken;
use RZP\Models\SubscriptionRegistration;
use RZP\Models\Payment\TerminalAnalytics;
use RZP\Gateway\Mozart\GetSimpl\Constants;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Gateway\Enach\Npci\Netbanking\Gateway as enachNpciGateway;

trait Authorize
{
    /**
     * There are different ways of doing payment authorization.
     */
    protected $type;

    protected $headlessError = false;

    protected $isS2SJsonRoute = false;

    /**
     * @param Payment\Entity $payment
     * @param array          $input
     * @param array          $gatewayInput
     *
     * @return array
     */
    public function authorize(Payment\Entity $payment, array $input, array $gatewayInput = []): array
    {
        $this->verifyMerchantIsLiveForLiveRequest();

        $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_INPUT_VALIDATIONS_PROCESSED, $payment);

        // $gatewayInput is being passed by reference.
        // Adds callback url, payment and card info to $gatewayInput
        $this->runPaymentMethodRelatedPreProcessing($payment, $input, $gatewayInput);

        $this->modifyAmountForDiscountedOfferIfApplicable($payment, $input);

        // this needs to be done after we have card entity as we need to know if
        // cards used in payment is international
        $this->processCurrencyConversions($payment);

        $this->setAnalyticsLog($payment);

        $this->runPaymentInputValidations($payment, $input);

        return $this->gatewayRelatedProcessing($payment, $input, $gatewayInput);
    }

    //
    // Called from to places
    // 1. authorize - regular flow
    // 2. processRedirectToAuthorize - s2s redirect flow
    //
    public function gatewayRelatedProcessing(Payment\Entity $payment, array $input, array $gatewayInput = []): array
    {
        $ret = $this->hitGatewayIfRequired($payment, $input, $gatewayInput);

        $this->validateAndSaveInputDetailsIfRequired($payment, $input, $gatewayInput, $ret);

        $this->updateTokenOnCreatedIfRequired($payment, $ret);

        $data = [];

        // For those payments which does auth in a single step, we need to store the acquirer data
        // If `request` is set from gateway response, we should not
        if ((isset($ret['request']) === false) AND
            (isset($ret['acquirer']) === true))
        {
            $data['acquirer'] = $ret['acquirer'];
            unset($ret['acquirer']);

            // To set ret to null instead of keeping it as an empty array
            if (empty($ret) === true)
            {
                $ret = null;
            }
        }

        if ($ret !== null)
        {
            return $ret;
        }

        return $this->processPaymentFinal($payment, $gatewayInput, $data);
    }

    protected function setSelectedTerminals(Payment\Entity $payment, array $gatewayInput)
    {
        $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_TERMINAL_SELECTION_INITIATED, $payment);

        // Ensure that the selectedTerminals set here is an array of terminal entities and not a terminal collection.
        try
        {
            if (empty($gatewayInput['selected_terminals_ids']) === false)
            {
                $this->selectedTerminals = (new TerminalProcessor)->getTerminalFromTerminalIds($gatewayInput['selected_terminals_ids']);
            }
            else if (($payment->isPushPaymentMethod() === true) and
                    ((empty($gatewayInput[Payment\Entity::TERMINAL_ID])) === false))
            {
                $this->selectedTerminals = [(new TerminalProcessor)->getTerminalFromGatewayData($gatewayInput)];
            }
            else
            {
                $this->selectedTerminals = (new TerminalProcessor)->getTerminalsForPayment($payment);
            }

            $this->trace->info(
                TraceCode::SELECTED_TERMINAL_IDS,
                [
                    'selected_terminals_ids'  => array_pluck($this->selectedTerminals, Terminal\Entity::ID),
                ]);

            $this->app['diag']->trackPaymentEvent(
                EventCode::PAYMENT_TERMINAL_SELECTION_PROCESSED,
                $payment,
                null,
                [
                    'terminal_count' => count($this->selectedTerminals)
                ]);
        }
        catch (\Throwable $ex)
        {
            $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_TERMINAL_SELECTION_PROCESSED, $payment, $ex);

            throw $ex;
        }
    }

    protected function setAuthenticationGatewayViaGatewayRules(Payment\Entity $payment, array & $gatewayInput)
    {
        $this->trace->info(
            TraceCode::AUTH_SELECTION_VIA_GATEWAY_RULES,
            [
                'payment_id'        => $payment->getId(),
            ]);

        (new TerminalProcessor)->setAuthenticationGateway($payment, $gatewayInput);
    }

    protected function hitGatewayIfRequired(Payment\Entity $payment, array $input, array & $gatewayInput)
    {
        //
        // The instance variable selectedTerminals need to be set
        // even if the gateway doesn't need to be hit. This is needed
        // for s2s recurring payments, so that terminal can be set later
        // using this instance variable.
        //
        $this->setSelectedTerminals($payment, $gatewayInput);

        // we are doing this after terminal selection since we might reject payemnt if there are no terminals found
        $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_CREATION_PROCESSED, $payment);

        if ($this->shouldHitGatewayForPayment($payment, $gatewayInput) === false)
        {
            $currentTerminal = $this->selectedTerminals[0];

            //
            // TODO:: Add a check to verify that this terminal is same as
            // the terminal id stored in token used for the first payment
            //
            $payment->associateTerminal($currentTerminal);

            // Fees validation can only happen after terminal selection has gone through
            // otherwise can cause issues with procurer and international pricing rule being
            // not available when international is not enabled.
            $this->verifyFeesLessThanAmount($payment);

            $this->repo->saveOrFail($payment);

            $this->validateAndSaveBillingAddressIfApplicable($payment, $input);

            return null;
        }

        $this->trace->info(
            TraceCode::TRACE_FOR_INCREASED_RESPONSE_TIMES,
            [
                'line'      => "Models/Payment/Processor/Authorize.php:238"
            ]
        );

        if ($this->canAuthorizeViaCps($payment) === true)
        {

            $request =  $this->authorizeViaCps($payment, $input, $gatewayInput);

            $this->trace->info(
                TraceCode::TRACE_FOR_INCREASED_RESPONSE_TIMES,
                [
                    'line'      => "Models/Payment/Processor/Authorize.php:251"
                ]
            );

        }
        else
        {
            $request = $this->authorizeAcrossTerminals($payment, $input, $gatewayInput);

            $this->trace->info(
                TraceCode::TRACE_FOR_INCREASED_RESPONSE_TIMES,
                [
                    'line'      => "Models/Payment/Processor/Authorize.php:263"
                ]
            );
        }

        if (($request !== null) and
            (empty($request['redirect']) === false))
        {
            return $request;
        }

        //
        // If $request is not null, then payment is two-step process
        // where client needs to provide additional info via his browser.
        //
        if ($request !== null)
        {
            //
            // If the request contains the acquirer field, we do not need corpoto here
            // Instead, we store this acquirer data in payment entity and proceeds to authorize the payment.
            // We DO NOT support both coproto and setting acquirer data together in auth response from
            // gateway as of now.
            //
            if (isset($request['acquirer']) === true)
            {
                return $request;
            }
            else
            {
                //
                // If $request is not null, then payment is two-step process
                // where client needs to provide additional info via his browser.
                //
                $request = $this->getPaymentGatewayRequestData($request, $payment);

                return $request;
            }
        }

        return null;

    }

    protected function authorizeAcrossTerminals(Payment\Entity $payment, array $input, array & $gatewayInput)
    {
        $totalTerminals = count($this->selectedTerminals);

        $maxRetryAttempts = min($totalTerminals, self::MAX_RETRY_ATTEMPTS);

        $retryAttempts = 0;

        $request = [];

        $retry = false;

        // Checking razorX flag for feedback loop here per paymentId
        $razorXForDoppler = $this->app->doppler->checkRazorXForFeedbackLoop($payment->getId());
        $this->trace->info(
            TraceCode::TRACE_FOR_INCREASED_RESPONSE_TIMES,
            [
                'line'      => "Models/Payment/Processor/Authorize.php:323"
            ]
        );
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

            // Using Hitachi terminals for paysecure until we create new ones for paysecure.
            if ($this->shouldCreatePaysecurePayment($payment, $input, $currentTerminal))
            {
                $currentTerminal[Terminal\Entity::GATEWAY] = Gateway::PAYSECURE;
            }
            // Uncomment this to test with Sharp or any other terminal locally.
            // $currentTerminal = Terminal\Entity::findOrFail('2czHdeTG32rFhB');
            $payment->associateTerminal($currentTerminal);

            // assigning $gatewayInput to $terminalGatewayInput because we need to
            // persist gateway input in redirection flow,in
            // runPostGatewaySelectionPreProcessing() other attributes and
            // payment analytics, gateway_tokens entities gets appended inside $terminalGatewayInput.
            $terminalGatewayInput = $gatewayInput;

            $this->runPostGatewaySelectionPreProcessing($payment, $terminalGatewayInput);

            $this->validateAndSaveBillingAddressIfApplicable($payment, $input);

            // passing $terminalGateawyInput and $gatewayInput
            $request = $this->validateAndReturnRedirectResponseIfApplicable($payment, $terminalGatewayInput, $gatewayInput);

            if ($request !== null)
            {
                break;
            }

            // TODO: This is temporarily added here until we make
            // gateway functions like authorize for bank transfer.
            if (($payment->isBankTransfer() === true) or
                ($payment->isNach() === true))
            {
                return null;
            }

            // data for terminal analytics
            $terminalData = [
                'payment_id'    => $payment['id'],
                'input'         => $input,
                'terminal_id'   => $payment['terminal_id'],
                'start'         => microtime(true),
            ];

            $this->app['diag']->trackPaymentEvent(
                EventCode::PAYMENT_AUTHENTICATION_INITIATED,
                $payment,
                null,
                [
                    'attempt'     => $retryAttempts,
                    'terminal_id' => $payment->getTerminalId(),
                    'gateway'     => $payment->getGateway(),
                    'shared'      => $currentTerminal->isShared()
                ] + ($terminalGatewayInput['authenticate'] ?? []));

            try
            {
                if ($this->canRunOtpPaymentFlow($payment, $terminalGatewayInput) === true)
                {
                    $request = $this->runOtpPaymentFlow($payment, $terminalGatewayInput);
                }
                else
                {
                    $request = $this->callGatewayAuthorize($payment, $terminalGatewayInput);
                }

                $this->app['diag']->trackPaymentEvent(
                    EventCode::PAYMENT_AUTHENTICATION_2FA_URL_SENT,
                    $payment,
                    null,
                    [
                        'url' => $request['url'] ?? ''
                    ]);

                $retry = false;

                if (($this->headlessError === false) and
                    ($this->canRunHeadlessOtpFlow($payment, $terminalGatewayInput) === true))
                {
                    $request = $this->runHeadlessOtpFlow($payment, $request);
                }

                if ($this->canRunOmnichannelFlow($payment) === true)
                {
                    $request = $this->runOmnichannelFlow($payment, $request);
                }

                break;
            }
            catch (Exception\BaseException $e)
            {
                // Payment Authentication failed for the gateway.
                // That means we could not redirect to the ACS page using $terminal->gateway() or,
                // mpi_blade in case terminal is authorization terminals like Hitachi.

                $retryOnSameGateway = $this->handleOtpElfFailureWithSameGatewayRetry($e, $payment);

                if ($retryOnSameGateway === true)
                {
                    continue;
                }

                $errorCode = $e->getError()->getPublicErrorCode();

                $internalErrorCode = $e->getError()->getInternalErrorCode();

                if ($razorXForDoppler === true)
                {
                    //TODO: Remove this later
                    try
                    {
                        $this->app->doppler->sendFeedback($payment, Doppler::PAYMENT_AUTHORIZATION_FAILURE_EVENT,
                            $errorCode, $internalErrorCode);
                    }
                    catch (\Throwable $e)
                    {
                        $this->trace->info(
                            TraceCode::DOPPLER_SERVICE_SNS_PUBLISH_FAILED,
                            [
                                'payment'             => $payment->toArray(),
                                'code'                => $errorCode,
                                'internal_code'       => $internalErrorCode,
                                'error'               => $e->getMessage()
                            ]
                        );
                    }
                }

                // An error occurred on gateway due to user or gateway.
                // We need to record this and mark payment as failed.
                //
                $terminalData['exception'] = $e;

                $retryAttempts++;

                $retry = $this->logAndCheckForAuthRetry($e, $payment);

                $this->disableIinFlowIfApplicable($payment, $internalErrorCode);

                if (($retry === true) and
                    ($retryAttempts < $maxRetryAttempts))
                {
                    $this->preProcessAuthBeforeRetry($payment);

                    continue;
                }

                $this->migrateCardDataIfApplicable($payment);

                $this->logRiskFailureForGateway($payment, $internalErrorCode);

                $this->updatePaymentOnExceptionAndThrow($e);
            }
            finally
            {
                $terminalData['end'] = microtime(true);

                $this->recordTerminalAudit($terminalData, $payment, $retryAttempts);
            }
        }

        return $request;
    }

    protected function runOtpPaymentFlow(Payment\Entity $payment, array $gatewayInput)
    {
        //
        // If the appToken and walletToken is set then for a power wallet, run the
        // power wallet flow. Run otp flow if appToken and walletToken are set
        // but the wallet is not a power wallet.
        //
        if ((Payment\Gateway::isAutoDebitPowerWalletSupported($payment) === true) and
            ($payment->getGlobalTokenId() !== null) and
            ($this->merchant->isFeatureEnabled(Feature\Constants::WALLET_AUTO_DEBIT) === true))
        {
            $request = $this->runAutoDebitFlow($payment, $gatewayInput);
        }
        else if ($payment->getCpsRoute() === Payment\Entity::CARD_PAYMENT_SERVICE)
        {
            $request = $this->callGatewayFunction(Action::AUTHORIZE, $gatewayInput);
        }
        else
        {
            $request = $this->callGatewayFunction(Action::OTP_GENERATE, $gatewayInput);
        }

        return $request;
    }

    protected function preProcessAuthBeforeRetry($payment)
    {
        $isPreferredAuthEmpty = (empty($payment->getMetadata(Payment\Entity::PREFERRED_AUTH)) === true);

        if ($isPreferredAuthEmpty === false)
        {
            $payment->setAuthType(null);
            return;
        }

        if (($payment->getAuthType() !== null) and
            (in_array($payment->getAuthType(), Payment\AuthType::$otpAuthTypes, true) === true))
        {
            $payment->setAuthType(Payment\AuthType::OTP);
        }
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

    public function updatePaymentAuthFailed(Exception\BaseException $e)
    {
        $this->updatePaymentFailed($e, TraceCode::PAYMENT_AUTH_FAILURE);

        $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_AUTHORIZATION_PROCESSED, $this->payment, $e);
    }

    protected function verifyFeesLessThanAmount(Payment\Entity $payment)
    {
        if ($payment->isGooglePayCard() === true)
        {
            return;
        }
        // try calculating the fees, throws exception if fees is more than amount
        list($fee, $tax, $feesSplit) = $this->repo->useSlave(function () use ($payment)
        {
            return (new Pricing\Fee)->calculateMerchantFees($payment);
        });
    }

    /**
     * This method is used when on payment create request,
     * authorization is done – i.e. Gateway is hit for auth
     *
     * @param Payment\Entity $payment
     *
     * @param array $data Acquirer data to be saved in payment entity
     * @return array
     */
    public function processAuth(Payment\Entity $payment, array $data = []): array
    {
        $this->updateAndNotifyPaymentAuthorized($data);

        $this->updateTwoFactorAuthForOneStepPayment();

        $payment = $this->payment;

        return $this->postPaymentAuthorizeProcessing($payment);
    }

    /**
     * This method is used when on payment create request,
     * authorization is skipped – i.e. Gateway isn't hit for auth
     * These payments are picked up asynchronously for auth later.
     *
     * @param Payment\Entity $payment
     *
     * @return array
     */
    protected function processCreated(Payment\Entity $payment): array
    {
        $payment = $this->payment;

        return ['razorpay_payment_id' => $payment->getPublicId()];
    }

    protected function processNachPaymentCreated(Payment\Entity $payment)
    {
        $token = $payment->getGlobalOrLocalTokenEntity();

        if (($payment->isRecurringTypeInitial() === true) and
            ($token->getRecurringStatus() === null))
        {
            $token->setRecurringStatus(Token\RecurringStatus::INITIATED);

            $this->repo->saveOrFail($token);

            if ($payment->hasInvoice() === true)
            {
                $invoice = $payment->invoice;

                if ($invoice->getEntityType() === Entity::SUBSCRIPTION_REGISTRATION)
                {
                    $subscriptionRegistration = $invoice->entity;

                    (new SubscriptionRegistration\Core)->associateToken($subscriptionRegistration, $token);
                }
            }
        }

        return ['razorpay_payment_id' => $payment->getPublicId()];
    }

    protected function processPaymentFinal(Payment\Entity $payment, array & $gatewayInput, array $data): array
    {
        if ((isset($gatewayInput['skip_gateway_call']) === true) and
            ($gatewayInput['skip_gateway_call'] === true))
        {
            return $this->processCreated($payment);
        }

        if ($payment->isFileBasedEmandateDebitPayment() === true)
        {
            return $this->processCreated($payment);
        }

        if ($payment->isNach() === true)
        {
            return $this->processNachPaymentCreated($payment);
        }

        return $this->processAuth($payment, $data);
    }

    protected function getOtpPaymentCreatedResponse($request, $payment)
    {
        $payment->incrementOtpCount();

        $this->repo->save($payment);

        // TODO: Return metadata in a better format
        $response = [
            'type'                  => 'otp',
            'request'               => $request,
            'version'               => 1,
            'payment_id'            => $payment->getPublicId(),
            'gateway'               => $this->getEncryptedGatewayText($payment->getGateway()),
            'contact'               => $payment->getContact(),
            'amount'                => number_format(($payment->getAmount() / 100), 2),
            'formatted_amount'      => $payment->getFormattedAmount(),
            'wallet'                => $payment->getWallet(),
            'merchant'              => $payment->merchant->getBillingLabel(),
            'merchant_id'           => $payment->merchant->getId(),
        ];

        // This is a hack to return direct method for IVR payments
        if ($payment->isMethodCardOrEmi() === true)
        {
            $card = $payment->card;
            $redirectUrl = null;

            if ($payment->getGateway() !== Payment\Gateway::BAJAJ)
            {
                $redirectUrl = $this->getPaymentRedirectTo3dsUrl();
            }

            $response['redirect'] = $redirectUrl;

            $metaData = [
                'issuer'     => $card->getIssuer(),
                'network'    => $card->getNetworkCode(),
                'last4'      => $card->getLast4(),
                'iin'        => $card->getIin(),
            ];

            $response['metadata'] = $metaData;

            $templateData = [
               'data'       => $response,
               'cdn'        => $this->app['config']->get('url.cdn.production'),
               'production' => $this->app->environment() === Environment::PRODUCTION,
            ];

            $content = $this->app['view']
                            ->make('gateway.gatewayOtpPostForm')
                            ->with('data', $templateData)
                            ->render();

            $next = ['otp_submit'];

            if (isset($request['content']['next']) === true)
            {
                $next = $this->getNextOtpAction($request['content']['next']);

                unset($request['content']['next']);
            }

            $otpResend = 'otp_resend';

            $resendUrl = null;
            $resendUrlPrivate = null;

            if (in_array($otpResend, $next, true) === true)
            {
                $resendUrl        = $this->getOtpResendUrl();
                $resendUrlPrivate = $this->getOtpResendUrl();
            }

            $response = [
                'type'       => 'otp',
                'request'    => [
                    'method'  => 'direct',
                    'content' => $content
                ],
                'version'    => 1,
                'payment_id' => $payment->getPublicId(),
                'next'       => $next,
                'gateway'    => $response['gateway'],
                'submit_url' => $request['url'],
                'resend_url' => $resendUrl,
                'metadata'   => $metaData,
                'redirect'   => $redirectUrl,
            ];

            $response['submit_url_private'] = $this->getOtpSubmitUrlPrivate();
            $response['resend_url_private'] = $resendUrlPrivate;

        }

        $this->segment->trackPayment($payment, TraceCode::OTP_GENERATE, $response);

        return $response;
    }

    protected function getGooglePayCardPaymentCreatedResponse($request, $payment)
    {
        $this->repo->saveOrFail($payment);

        $response = [
            'version'               => 1,
            'type'                  => 'application',
            'application_name'      => 'google_pay',
            'payment_id'            => $payment->getPublicId(),
            'gateway'               => $this->getEncryptedGatewayText($payment->getGateway()),
            'request'               => $request,
        ];

        return $response;
    }

    protected function updateTwoFactorAuthForOneStepPayment()
    {
        $payment = $this->payment;

        // In one step payment, we always set the 2FA as unavailable. Basically, no 2FA done.
        // Except in the cases of recurring, because, here we know that
        // we have manually skipped/by-passed the 2FA.

        if (($payment->terminal !== null) and
            ($payment->terminal->isNon3DSRecurring() === true) and
            ($payment->isRecurring() === true))
        {
            $payment->setTwoFactorAuth(TwoFactorAuth::SKIPPED);
        }
        else
        {
            $payment->setTwoFactorAuth(TwoFactorAuth::NOT_APPLICABLE);
        }

        $this->repo->saveOrFail($payment);
    }

    public function autoCapturePaymentIfApplicable(Payment\Entity $payment)
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
                'payment_id'          => $payment->getId(),
                'payment_created'     => $paymentCreatedTime,
                'verify_bucket'       => $payment->getVerifyBucket(),
                'authorized_at'       => $currentTime,
                'time_difference'     => $currentTime - $paymentCreatedTime,
                'caller'              => $this->getVerifyCaller(),
                'error_code'          => $payment->getErrorCode(),
                'internal_error_code' => $payment->getInternalErrorCode(),
                'gateway'             => $payment->getGateway(),
            ]);

        $this->segment->trackPayment($payment, TraceCode::PAYMENT_FAILED_TO_AUTHORIZED);

        $this->runAuthorizeFailedTransaction($payment);

        $this->trace->info(
            TraceCode::PAYMENT_FAILED_TO_AUTHORIZED,
            [
                'payment_id' => $payment->getId(),
                'error' => $payment->getErrorDetails(),
            ]);

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
     */
    public function forceAuthorizeFailedPayment(Payment\Entity $payment, array $input = []): array
    {
        $payment->getValidator()->validateGatewayForForceAuth();

        $this->setPayment($payment);

        $this->mutex->acquireAndRelease($payment->getId(), function() use ($payment, $input)
        {
            $this->repo->reload($payment);

            if ($payment->isFailed() === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Non failed payment given for authorization');
            }

            $this->repo->transaction(function() use ($payment, $input)
            {
                $this->forceAuthorizeFailedOnGateway($payment, $input);

                $this->authorizeFailedPaymentOnApi($payment, []);
            });

        });

        return $payment->toArrayAdmin();
    }

    protected function runPaymentInputValidations(Payment\Entity $payment, array $input)
    {
        $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_INPUT_VALIDATIONS2_INITIATED, $payment);

        try
        {
            // this is a temporary fix for irctc case, where a validation has to be done on supported method level,
            // which will be stored in notes during order creation
            // Slack Ref - https://razorpay.slack.com/archives/C2ZL6H76U/p1578388168053200
            $this->validateOrderMethods($payment);

            $this->validateCardAndCvv($payment, $input);

            $this->validateRecurringIfApplicable($payment, $input);

            $this->validateCardAuthenticationIfApplicable($payment, $input);

            $this->validateS2SIfApplicable($payment);

            $this->validateSubscriptionInputIfPresent($payment, $input);

            $this->verifyPaymentMethodEnabled($payment);

            $this->validatePaymentNetworkSupported($payment);

            $this->runInternationalChecks($payment);

            $this->runFraudChecksIfApplicable($payment);

            $this->validateOfferIfApplicable($payment, $input);

            $this->validateCardlessEmiIfApplicable($payment, $input);

            $this->validatePayLaterIfApplicable($payment, $input);

            $this->validateApplicationIfApplicable($payment, $input);

            $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_INPUT_VALIDATIONS2_PROCESSED, $payment);
        }
        catch (\Throwable $ex)
        {
            $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_INPUT_VALIDATIONS2_PROCESSED, $payment, $ex);

            throw $ex;
        }

    }

    protected function validateCardlessEmiIfApplicable(Payment\Entity $payment, $input)
    {
        if ($payment->isCardlessEmi() === false)
        {
            return;
        }

        if (in_array($input[Payment\Entity::PROVIDER], Payment\Gateway::$cardlessEmiRedirectFlowProvider, true) === true)
        {
            return;
        }

        $this->validateContactAndProviderFromToken($payment, $input);
    }

    protected function validatePayLaterIfApplicable(Payment\Entity $payment, $input)
    {
        if ($payment->isPayLater() === false)
        {
            return;
        }

        $this->validateContactAndProviderFromToken($payment, $input);
    }


    protected function shouldSkipContactAndProviderValidation($input)
    {
        if (($input['provider'] === PayLater::ICICI and $input['method'] === Gateway::PAYLATER) or ($input['ott'] === Constants::GETSIMPLTOKEN))
        {
            return true;
        }
        return false;
    }

    protected function validateApplicationIfApplicable(Payment\Entity $payment, $input)
    {
        if (isset($input['application']) === true)
        {
            switch($input['application'])
            {
                case 'google_pay':
                    if ($payment->merchant->isFeatureEnabled(Feature\Constants::GOOGLE_PAY_CARDS) === false)
                    {
                        throw new Exception\BadRequestValidationFailureException(
                            'Google Pay Cards not enabled for merchant.');
                    }
                    break;
            }
        }
    }

    private function validateContactAndProviderFromToken(Payment\Entity $payment, $input)
    {
        if ($this->shouldSkipContactAndProviderValidation($input) === true)
        {
            return;
        }

        $key = Payment\Entity::getCardlessEmiOnetimeTokenCacheKey($input['ott']);

        $cardlessEmiData = $this->app['cache']->get($key);

        if ($cardlessEmiData === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Token provided is invalid for '. $input['method'] ?? 'cardless emi',
                null,
                $cardlessEmiData);
        }

        $cardlessEmiData = Customer\Validator::validateAndParseContactInInput($cardlessEmiData);

        if ((empty($input['payment_id']) === false) and
            ($cardlessEmiData['payment_id'] !== $input['payment_id']))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARDLESS_EMI_INVALID_PAYMENT_ID,
                null,
                [
                    'payment_id'        => $input['payment_id'] ?? null,
                ]);
        }

        if ((empty($cardlessEmiData['contact']) === true) or
            ($cardlessEmiData['contact'] !== $input['contact']))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARDLESS_EMI_CONTACT_MISMATCH,
                null,
                [
                    'payment_id'        => $payment->getId(),
                    'input_contact'     => $input['contact'],
                    'contact'           => $cardlessEmiData['contact'] ?? null,
                ]);
        }

        if ((empty($cardlessEmiData[Payment\Entity::PROVIDER]) === true) or
            ($cardlessEmiData[Payment\Entity::PROVIDER] !== $input[Payment\Entity::PROVIDER]))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARDLESS_EMI_INVALID_PROVIDER,
                null,
                [
                    'payment_id'        => $payment->getId(),
                    'input_provider'    => $input[Payment\Entity::PROVIDER],
                    'provider'          => $cardlessEmiData[Payment\Entity::PROVIDER] ?? null,
                ]);
        }
    }

    protected function validateSubscriptionInputIfPresent(Payment\Entity $payment, $input)
    {
        //
        // Subscription association to payment happens in pre-process
        //
        if ($this->subscription === null)
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

        //
        // Allow manual charge of older invoices, even when subscription is in terminal state
        // TODO: Rethink, won't work for S2S payments which are always in private auth
        //

        //
        // For subserv subscriptions, these checks validations will take place
        // in subserv, when we make the first call to fetch and validate subscription
        //
        if ($this->subscription->isExternal() === true)
        {
            return;
        }

        //
        // Storing subscription in a separate variable here, so as not
        // to change signature of several legacy methods.
        //
        $subscription = $this->subscription;

        if (($subscription->isTerminalStatus() === true) and
            ($this->app['basicauth']->isPrivateAuth() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUBSCRIPTION_IN_TERMINAL_STATE,
                null,
                [
                    'subscription_id'   => $subscription->getId(),
                    'status'            => $subscription->getStatus()
                ]);
        }

        if ($subscription->isCreated() === true)
        {
            $this->validateNewSubscription($subscription, $payment);
        }
        else if ($subscription->hasBeenAuthenticated() === true)
        {
            $this->validateAuthenticatedSubscription($subscription, $payment, $input);
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
        Payment\Entity $payment,
        array $input)
    {
        $cardChange = $payment->isRecurringTypeCardChange();

        if ($cardChange === true)
        {
            if ($subscription->isCardChangeStatus() === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_SUBSCRIPTION_CARD_CHANGE_NOT_ALLOWED,
                    null,
                    [
                        'subscription_id'   => $subscription->getId(),
                        'status'            => $subscription->getStatus(),
                    ]);
            }

            if (($subscription->isGlobal() === true) and
                (empty($input[Payment\Entity::APP_TOKEN]) === true))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_APP_TOKEN_ABSENT,
                    null,
                    [
                        'subscription_id'   => $subscription->getId(),
                        'card_change'       => true,
                    ]);
            }
        }
        else
        {
            $subscriptionPublicTokenId = Token\Entity::getSignedId($subscription->getTokenId());

            //
            // For an authenticated subscription, if it's not a card change flow,
            // there should be no card details in the input.
            // Token would be there in the input for recurring charge. But, it would
            // be the same as the token associated with the subscription.
            //
            if ((empty($input[Payment\Entity::CARD]) === false) or
                ((isset($input[Payment\Entity::TOKEN]) === true) and
                 ($subscriptionPublicTokenId !== $input[Payment\Entity::TOKEN])))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_SUBSCRIPTION_ALREADY_AUTHENTICATED,
                    null,
                    [
                        'subscription_id'       => $subscription->getId(),
                        'card_details'          => (empty($input[Payment\Entity::CARD]) === false),
                        'subscription_token_id' => $subscription->getTokenId(),
                    ]);
            }
        }

        //
        // For an already authenticated subscription, we should always have
        // a token present. If the token is not present, we throw an exception.
        //
        // This flow can reach from either public auth (card change) or
        // privilege auth (charge/retry cron).
        //
        if ($subscription->hasToken() === false)
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
        if ($cardChange === true)
        {
            $this->validateSubscriptionAmount($subscription, $payment->getAmount(), $cardChange = true);
        }
    }

    protected function validateNewSubscription(Subscription\Entity $subscription, Payment\Entity $payment)
    {
        $this->validateSubscriptionAmount($subscription, $payment->getAmount(), $cardChange = false);

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

    protected function validateSubscriptionAmount(
        Subscription\Entity $subscription,
        int $paymentAmount,
        $cardChange = false)
    {
        $expectedAmount = (new Subscription\Core)->getAuthTransactionAmount($subscription, $cardChange);

        //
        // Adding `intval` because it's failing otherwise in wercker.
        // Works fine on local though. >.<
        //
        if (intval($paymentAmount) !== intval($expectedAmount))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_TRANSACTION_AMOUNT,
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

            if ($card->isDiners() === true)
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
        // Check for bank transfer batch insertion, S2S validation
        // is not relevant here in case of queue flow.
        //
        if (($payment->isBankTransfer() === true) and
            (($this->app->runningInQueue() === true) or
             (Route::currentRouteName() === 'bank_transfer_process_test')))
        {
            return;
        }

        if (($payment->isBharatQr() === true) or
            ($payment->isNach()))
        {
            return;
        }

        if ($payment->isUpiTransfer() === true)
        {
            return;
        }
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

        $oAuthApplicationId = $this->app['basicauth']->getOAuthApplicationId();

        //refer testAppBlacklistedFeatureEnabledOnApp
        if ($oAuthApplicationId !== null)
        {
            $feature = $this->repo
                            ->feature
                            ->findByEntityTypeEntityIdAndName(Feature\Constants::APPLICATION, $oAuthApplicationId, Feature\Constants::S2S);

            if ($feature !== null)
            {
                return;
            }
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
        else if ($payment->getAuthType() === Payment\AuthType::SKIP)
        {
            $this->verifyFeatureForMerchant($merchant, Feature\Constants::DIRECT_DEBIT);
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

    protected function validateCardAuthenticationIfApplicable(Payment\Entity $payment, array $input)
    {
        if ($payment->isMethodCardOrEmi() === false)
        {
            return;
        }

        switch ($payment->getAuthType())
        {
            case Payment\AuthType::PIN:
                if (($payment->card->iinRelation === null) or
                    ($payment->card->iinRelation->supports(IIN\Flow::PIN) === false))
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'The pin authentication type is not applicable on the given card');
                }
                break;

            case Payment\AuthType::OTP:
                // We support OTP flow with native supports from the gateway, headless_otp
                // flow is something which is a hack and not natively supported by the gateway
                if (($payment->card->iinRelation === null) or
                    ((($payment->merchant->isAxisExpressPayEnabled() === false) or
                      ($payment->card->iinRelation->supports(IIN\Flow::OTP) === false)) and
                     (($payment->merchant->isHeadlessEnabled() === false) or
                      ($payment->card->iinRelation->supports(IIN\Flow::HEADLESS_OTP) === false)) and
                     (($payment->merchant->isFeatureEnabled(Feature\Constants::IVR) === false) or
                      ($payment->card->iinRelation->supports(IIN\Flow::IVR) === false))))
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'The otp authentication type is not applicable on the given card');
                }

                break;

            case Payment\AuthType::SKIP:
                // Skip auth flow is supported only for Master Card, Visa and Rupay.
                if (Payment\Gateway::isDirectDebitSupported($payment->card->getNetworkCode()) === false)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'The skip authentication type is not applicable on the given card');
                }
                break;
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

        if ($token === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TOKEN_ABSENT_FOR_RECURRING_PAYMENT,
                null,
                [
                    'payment_id'    => $payment->getId(),
                ]);
        }

        //
        // If payment type is card, validate that the card supports recurring
        // or if payment type is emandate, validate that the bank supports emandate
        //
        if ($payment->isCard() === true)
        {
            $this->validateRecurringForCard($payment, $token);
        }
        else if ($payment->isEmandate() === true)
        {
            $this->validateRecurringForEmandate($payment, $token, $input);
        }
        else if ($payment->isUpiRecurring() === true)
        {
            $this->validateRecurringForUpi($payment, $token, $input);
        }
        else if ($payment->isNach() === true)
        {
            $this->validateRecurringForNach($payment, $token, $input);
        }

        //
        // The first recurring will be on public auth for non-S2S enabled merchants.
        // The second recurring MUST always be via private auth.
        // But, if token IS PRESENT, it could just mean a different recurring payment
        // with the same token. It need not necessarily be the initial recurring payment
        // for which the token was created in the first place. Hence, here, second recurring
        // is not really second recurring and could be in fact first recurring only.
        //
        // We don't have to verify that the payment is coming from Zoho for
        // a Zoho merchant if it's on public auth. It won't be second recurring
        // if it's coming from public auth. It's possible that it won't be
        // second recurring if it's coming from private auth also, but we don't
        // have any way to figure that out. Adding access check here to at least
        // handle second recurring type payments (recurring payments with recurring token)
        // coming via public auth. These can be safely treated as first recurring.
        //
        if ($payment->isSecondRecurring() === true)
        {
            $this->verifyAggregatorIfApplicable($merchant);
        }
    }

    protected function verifyFeatureForRecurring(Merchant\Entity $merchant, Payment\Entity $payment)
    {
        //
        // When we are charging tokens via batch using sqs, auth type won't be set.
        // Adding this condition to verify recurring feature enabled for batch charge tokens
        //
        if ($this->app->runningInQueue() === true)
        {
            $this->verifyRecurringEnabledForMerchant($merchant);

            return;
        }

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
                    $this->verifyRecurringEnabledForMerchant($merchant);
                }
                else
                {
                    //
                    // Merchants with subscriptions feature cannot
                    // make S2S calls for recurring payments.
                    //
                    $this->verifyFeatureForMerchant($merchant, Feature\Constants::CHARGE_AT_WILL);
                }

                break;

            case BasicAuth\Type::PUBLIC_AUTH:

                //
                // Public payments can be made for recurring for merchants
                // with either subscriptions or recurring features enabled.
                //
                $this->verifyRecurringEnabledForMerchant($merchant);

                break;

            case BasicAuth\Type::PRIVILEGE_AUTH:

                //
                // Privilege auth for recurring should be used only for merchants
                // who have subscriptions.
                // But, since it's privilege auth, it can be used for merchants with
                // recurring feature also, but no requirement right now.
                //
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

    protected function validateRecurringForCard(Payment\Entity $payment, Token\Entity $token)
    {
        if ($payment->card->isRecurringSupported() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_RECURRING_NOT_SUPPORTED);
        }

        $this->validateTokenExpiredAt($token);
    }

    protected function validateRecurringForEmandate(
        Payment\Entity $payment, Token\Entity $token, array $input)
    {
        //
        // The below two validations are being done here and not as part of
        // Validator Rules because after the payment build, we give the
        // control to frontend to take missing attributes from the customer.
        // So, as part of validation, we use `sometimes` for these fields.
        // Ideally, this should never happen since we anyway ensure that
        // we collect the missing attributes from the customer before
        // proceeding further.
        //
        // We need bank_account details and auth_type only for first recurring payments.
        // For the second recurring payments, we don't require auth type and bank_account
        // details would be present in the token itself.
        //
        // ISSUE: Since we are doing the validation here (after the token is created),
        // it's possible that the tokens are created without the required bank account details.
        //
        if ($payment->isRecurringTypeInitial() === true)
        {
            $this->validateInitialRecurringForEmandate($payment, $input);
        }
        else if ($payment->isRecurringTypeAuto() === true)
        {
            $this->validateAutoRecurringForEmandate($payment, $input);
        }
        else
        {
            throw new Exception\LogicException(
                'Shouldn\'t have reached here.',
                null,
                [
                    'payment'        => $payment->getId(),
                    'recurring_type' => $payment->getRecurringType(),
                    'auth_type'      => $payment->getAuthType()
                ]);
        }

        //
        // TODO: This is broken still. We should not be accepting any token
        // in private auth also for first recurring. But, in private auth,
        // it could be second recurring also, where we accept a token.
        //
        if (($this->ba->isPublicAuth() === true) and
            (empty($input[Payment\Entity::TOKEN]) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_EMANDATE_TOKEN_PASSED_IN_FIRST_RECURRING,
                Payment\Entity::BANK,
                [
                    'payment' => $payment->toArray(),
                    'token'   => $token->toArray(),
                ]);
        }

        // Customer fee bearer is not allowed on netbanking recurring
        if ($payment->isFeeBearerCustomer() === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Payment failed. Please contact the merchant for further assistance.',
                null,
                [
                    'payment_id' => $payment->getId()
                ]);
        }

        $this->validateTokenRecurringStatus($token, $payment);

        $this->validateTokenMaxAmount($token, $payment);

        $this->validateTokenExpiredAt($token);
    }

    protected function validateRecurringForNach(Payment\Entity $payment,
                                                Token\Entity $token,
                                                array $input)
    {
        if ($payment->isRecurringTypeInitial() === true)
        {
            $this->validateInitialRecurringForNach($payment, $input);
        }
        else if ($payment->isRecurringTypeAuto() === true)
        {
            $this->validateAutoRecurringForNach($payment, $input);
        }
        else
        {
            throw new Exception\LogicException(
                'Shouldn\'t have reached here.',
                null,
                [
                    'payment'        => $payment->getId(),
                    'recurring_type' => $payment->getRecurringType(),
                    'auth_type'      => $payment->getAuthType()
                ]);
        }

        $this->validateTokenRecurringStatus($token, $payment);

        $this->validateTokenMaxAmount($token, $payment);

        $this->validateTokenExpiredAt($token);
    }

    protected function validateInitialRecurringForEmandate(Payment\Entity $payment, array $input)
    {
        if ((Payment\Gateway::isZeroRupeeFlowSupported($payment->getBank()) === true) and
            ($payment->getAmount() !== 0))
        {
            throw new Exception\BadRequestValidationFailureException(
                'The amount must be 0 for eMandate registration',
                Payment\Entity::AMOUNT,
                [
                    'amount'            => $payment->getAmount(),
                    'payment_id'        => $payment->getId(),
                    'method'            => $payment->getMethod(),
                    'auth_type'         => $payment->getAuthType(),
                    'recurring_type'    => $payment->getRecurringType(),
                    'bank'              => $payment->getBank(),
                ]);
        }

        if (empty($input[Payment\Entity::BANK_ACCOUNT]) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The bank_account field is required when method is ' . Method::EMANDATE
            );
        }

        $authType = $payment->getAuthType();

        if ($authType === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The auth_type field is required when method is ' . Method::EMANDATE
            );
        }

        if ((in_array($authType, [Payment\AuthType::AADHAAR, Payment\AuthType::AADHAAR_FP], true) === true) and
            ((bool) Admin\ConfigKey::get(Admin\ConfigKey::BLOCK_AADHAAR_REG, true) === true))
        {
            throw new Exception\BadRequestValidationFailureException(
                'The selected auth_type is invalid',
                Payment\Entity::AUTH_TYPE,
                [
                    Payment\Entity::AUTH_TYPE => $authType,
                ]);
        }

        $bank = $payment->getBank();

        // TODO: Handle first recurring / second recurring based on token and route

        if (in_array(
                $bank,
                Payment\Gateway::getAvailableEmandateBanksForAuthType($authType),
                true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_RECURRING_NOT_SUPPORTED,
                Payment\Entity::BANK,
                [
                    'payment' => $payment->toArray(),
                ]);
        }
    }

    protected function validateInitialRecurringForNach(Payment\Entity $payment, array $input)
    {
        $authType = $payment->getAuthType();

        if ($authType === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The auth_type field is required when method is ' . Method::NACH
            );
        }

        $order = $payment->order;

        if ($order !== null)
        {
            $subscriptionRegistration = $order->getTokenRegistration();

            if ($subscriptionRegistration !== null)
            {
                if ($subscriptionRegistration->getAuthType() !== $authType)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'payment auth type is not same as order auth type'
                    );
                }
            }
        }
    }

    protected function validateAutoRecurringForEmandate(Payment\Entity $payment, array $input)
    {
        if ($payment->getAmount() < 100)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The amount must be at least 100.',
                'amount',
                [
                    'amount'            => $payment->getAmount(),
                    'payment_id'        => $payment->getId(),
                    'method'            => $payment->getMethod(),
                    'auth_type'         => $payment->getAuthType(),
                    'recurring_type'    => $payment->getRecurringType(),
                    'bank'              => $payment->getBank(),
                ]);
        }
    }

    protected function validateAutoRecurringForNach(Payment\Entity $payment, array $input)
    {
        if ($payment->getAmount() < 100)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The amount must be at least 100.',
                'amount',
                [
                    'amount'            => $payment->getAmount(),
                    'payment_id'        => $payment->getId(),
                    'method'            => $payment->getMethod(),
                    'auth_type'         => $payment->getAuthType(),
                    'recurring_type'    => $payment->getRecurringType(),
                    'bank'              => $payment->getBank(),
                ]);
        }
    }

    protected function validateTokenRecurringStatus(Token\Entity $token, Payment\Entity $payment)
    {
        if (($payment->isSecondRecurring() === true) and
            ($token->getRecurringStatus() === Token\RecurringStatus::PAID))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TOKEN_STATUS_ALREADY_PAID,
                Payment\Entity::BANK,
                [
                    'payment' => $payment->toArray(),
                    'token'   => $token->toArray(),
                ]);
        }

        if (($payment->isSecondRecurring() === true) and
            ($token->getRecurringStatus() !== Token\RecurringStatus::CONFIRMED))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_UNCONFIRMED_TOKEN_PASSED_IN_SECOND_RECURRING,
                Payment\Entity::BANK,
                    [
                         'payment' => $payment->toArray(),
                         'token'   => $token->toArray(),
                    ]);
        }
    }

    protected function validateTokenExpiredAt(Token\Entity $token)
    {
        $currentTime = Carbon::now()->getTimestamp();

        if (($token !== null) and
            ($token->getExpiredAt() !== null) and
            ($token->getExpiredAt() < $currentTime) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_RECURRING_TOKEN_EXPIRED,
                null,
                [
                    Token\Entity::ID         => $token->getId(),
                    Token\Entity::EXPIRED_AT => $token->getExpiredAt(),
                ]);
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

    protected function validateOfferIfApplicable(Payment\Entity $payment, array $input)
    {
        $offer = $this->offer;

        if ($offer !== null)
        {
            (new Offer\Core)->validateOfferApplicableOnPayment($offer, $payment, $input);
        }
    }

    protected function runPostGatewaySelectionPreProcessing(Payment\Entity $payment, array & $gatewayInput)
    {
        // Fees validation can only happen after international validation has gone through
        // otherwise can cause issues with international pricing rule being not available when
        // international is not enabled.
        $this->verifyFeesLessThanAmount($payment);

        // We are doing it in post processing because terminal id is required for
        // fetching the wallet token as they are terminal specific
        $this->associateWalletTokenIfApplicable($payment);

        $this->setAuthAndAuthenticationGateway($payment, $gatewayInput);

        $this->setPaymentRoutedThroughCpsIfApplicable($payment, $gatewayInput);


        $this->trace->info(
            TraceCode::TRACE_FOR_INCREASED_RESPONSE_TIMES,
            [
                'line'      => "Models/Payment/Processor/Authorize.php:1917"
            ]
        );

        $this->repo->saveOrFail($payment);

        $this->tracePaymentInfo(TraceCode::PAYMENT_CREATED, Trace::DEBUG);

        $this->segment->trackPayment($payment, TraceCode::PAYMENT_CREATED);

        //
        // Call gateway input
        //
        $gatewayInput['payment'] = $payment->toArrayGateway();
        $gatewayInput['callbackUrl'] = $this->getCallbackUrl();
        $gatewayInput['otpSubmitUrl'] = $this->getOtpSubmitUrl();
        $gatewayInput['payment_analytics'] = $payment->getMetadata('payment_analytics');

        // Bank such as Netbanking Canara enforces to send fee in request.
        // Adding fee calculation as part of gateway input only if applicable
        $this->addFeeIfApplicable($payment, $gatewayInput);

        if ($payment->hasOrder() === true)
        {
            $gatewayInput['order'] = $payment->order->toArray();
            $orderBankAccount = $payment->order->bankAccount;

            if ($orderBankAccount !== null)
            {
                $gatewayInput['order']['bank_account'] = $orderBankAccount->toArray();
            }
        }

        // modify account number in gateway input for some banks
        // to be called only in case of upi tpv transactions
        if (($payment->getMethod() == Method::UPI) and
            ($payment->merchant->isTPVRequired() === true))
        {
            $this->modifyAccountNumberForSpecificBanks($payment, $gatewayInput);
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
    }

    protected function associateWalletTokenIfApplicable(Payment\Entity $payment)
    {
        if (($payment->getGlobalCustomerId() !== null) and
            (Payment\Gateway::isAutoDebitPowerWalletSupported($payment) === true))
        {
            $terminalId = $payment->getTerminalId();
            $wallet = $payment->getWallet();
            $customerId = $payment->getGlobalCustomerId();

            $token = (new Token\Repository)->getByWalletTerminalAndCustomerId($wallet, $terminalId, $customerId);

            if (($token !== null) and (($token->getExpiredAt() === null) or ($token->getExpiredAt() > time())))
            {
                $payment->globalToken()->associate($token);
            }
        }
    }

    /**
     * @param Payment\Entity $payment
     * @param array $gatewayInput
     */
    protected function setAuthAndAuthenticationGateway(Payment\Entity $payment, array & $gatewayInput)
    {
        $payment->setAuthenticationGateway(null);

        try
        {
            if (($payment->isMethodCardOrEmi() === true) and
                ($payment->isMoto() === false) and
                ($payment->isSecondRecurring() === false) and
                ($payment->isPushPaymentMethod() === false))
            {
                $this->setAuthenticationGatewayViaGatewayRules($payment, $gatewayInput);

                $this->setAuthInPaymentViaGatewayRules($payment, $gatewayInput);

                return;
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::AUTH_SELECTION_FAILURE,
                [
                    'payment_id'  => $payment->getId(),
                    'payment_auth_type' => $payment->getAuthType(),
                ]
            );
        }

        // Keeping this condition for backward compatibility
        // @todo: Remove the authorization gateway check once it's live
        //
        if ((Payment\Gateway::isOnlyAuthorizationGateway($payment->getGateway()) === true) or
            ($payment->terminal->getCapability() === Terminal\Capability::AUTHORIZE))
        {
            $method = $payment->getMethod();

            switch ($method)
            {
                case Payment\Method::EMANDATE:
                    //
                    // Todo: During NPCI eMandate integration, we need to have one more condition
                    // here to check if the auth method is aadhaar.
                    //

                    $eSignerGateway = Payment\Gateway::DEFAULT_ESIGNER_GATEWAY;

                    $key = ConfigKey::MERCHANT_ENACH_CONFIGS;

                    $merchantId = $payment->merchant->getId();

                    $esignerConfigs = null;

                    try
                    {
                        $esignerConfigs = Cache::get($key);
                    }
                    catch (\Throwable $ex)
                    {
                        // If cache fetch fails(say, the cache service is down), do not fail the payment.
                        // Instead, fallback to the default eSigner gateway.
                        $this->trace->traceException(
                            $ex,
                            Trace::CRITICAL,
                            TraceCode::REDIS_KEY_FETCH,
                            ['key' => $key]
                        );
                    }

                    /*
                     * If we want to override all the merchant's esigner configs to use
                     * a particular esigner gateway, we use this
                     */
                    if (isset($esignerConfigs['auth_gateway']['override']) === true)
                    {
                        $eSignerGateway = $esignerConfigs['auth_gateway']['override'];
                    }
                    else if (isset($esignerConfigs['auth_gateway'][$merchantId]) === true)
                    {
                        $eSignerGateway = $esignerConfigs['auth_gateway'][$merchantId];
                    }

                    $gatewayInput['authenticate']['gateway'] = $eSignerGateway;
                    break;

                case Payment\Method::CARD:
                case Payment\Method::EMI:
                    if (($payment->isRecurring() === false) or
                        ($payment->isRecurringTypeInitial() === true))
                    {
                        $gateway = Payment\Gateway::authorizationToAuthenticationGateway($payment->getGateway(), Payment\Gateway::MPI_BLADE);
                        $authType = '3ds';

                        if ($this->canRunIvrFlow($payment) === true)
                        {
                            $authType = 'otp';
                            $gateway = Payment\Gateway::MPI_BLADE;
                        }

                        if ($this->canRunAxisExpressPay($payment) === true)
                        {
                            $authType = 'otp';
                            $gateway = Payment\Gateway::MPI_ENSTAGE;
                        }

                        $gatewayInput['authenticate'] = [
                            'gateway'   => $gateway,
                            'auth_type' => $authType,
                        ];
                    }
                    break;
            }
        }

        $this->setAuthTypeInPayment($payment);
    }

    protected function setAuthInPaymentViaGatewayRules(Payment\Entity $payment, array $gatewayInput)
    {
        $authType = $payment->getAuthType();

        $payment->setAuthType(null);

        if (empty($gatewayInput['auth_type']) === true)
        {
            return;
        }

        if ($gatewayInput['auth_type'] === Payment\AuthType::PIN)
        {
            $payment->setAuthType(Payment\AuthType::PIN);
        }

        $otpAuth = [
            Payment\AuthType::IVR,
            Payment\AuthType::OTP,
        ];

        if (in_array($gatewayInput['auth_type'], $otpAuth, true) === true)
        {
            $payment->setAuthType(Payment\AuthType::OTP);
            return;
        }

        if (($authType !== null) and
            ($authType === Payment\AuthType::OTP) and
            ($gatewayInput['auth_type'] === Payment\AuthType::HEADLESS_OTP))
        {
            $payment->setAuthType(Payment\AuthType::OTP);
        }
    }

    protected function setAuthTypeInPayment(Payment\Entity $payment)
    {
        //
        // We set `auth_type` from `preferred_auth` field here
        // on the basis of the used terminal
        //
        if (($payment->isMethodCardOrEmi() === false) or
            (empty($payment->getMetadata(Payment\Entity::PREFERRED_AUTH)) === true))
        {
            return;
        }

        // Setting default auth type as null for cards
        $payment->setAuthType(null);

        //
        // Currently, we are only storing auth type for
        // debit pin payments
        //
        if ($payment->terminal->isPin() === true)
        {
            $payment->setAuthType(Payment\AuthType::PIN);
        }

        if (($this->canRunOtpPaymentFlow($payment) === true) and
            ($payment->isMethodCardOrEmi() === true))
        {
            $payment->setAuthType(Payment\AuthType::OTP);
        }
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

        $gateway = $payment->getGateway();

        $gatewayTokensForTheGateway = $gatewayTokens->filter(
                                            function($gatewayToken) use ($gateway)
                                            {
                                                return ($gatewayToken->getGateway() === $gateway);
                                            });

        //
        // It's possible that there are no gateway tokens for this.
        // For NB, wallets, non-recurring cards, first recurring card, etc.
        //
        if ($gatewayTokensForTheGateway->count() === 1)
        {
            $gatewayInput['gateway_token'] = $gatewayTokensForTheGateway->first();
        }
    }

    /**
     * Function gets called processAndReturnTerminal and processAndReturnFees, in this flow
     * runPaymentMethodRelatedPreProcessing creates cards and tokens which is not used at all.
     * to avoid this we run the flow in beginTransactionAndRollback
     *
     * @param $payment
     * @param $input
     */
    protected function dummyPrePaymentAuthorizeProcessing($payment, $input)
    {
        $this->repo->beginTransactionAndRollback(
            function() use ($payment, $input)
            {
                $gatewayInput = [];

                $this->runPaymentMethodRelatedPreProcessing($payment, $input, $gatewayInput);

                $this->processCurrencyConversions($payment);

                $this->attachEntityOrigin($payment);
            });
    }

    /**
     * When calculating commission, we proceed only if there is an entity origin associated with the payment.
     * Here we fetch the entity origin for the current request and associate with the payment so that
     * explicit fees can be shown as a part of fee breakup to the customer if applicable
     *
     * @param $payment
     */
    protected function attachEntityOrigin($payment)
    {
        try
        {
            // Fetch origin entity for the payment based on the auth used to initiate the payment.
            $entityOrigin = (new EntityOrigin\Core)->fetchEntityOrigin($payment);

            if (empty($entityOrigin) === false)
            {
                // associate origin entity to payment relation
                $payment->setRelation('entityOrigin', $entityOrigin);
            }
        }
        catch (\Throwable $e)
        {
            // The payment should not be blocked even if the origin cannot be fetched. Log an error and proceed.
            $this->trace->critical(TraceCode::ORIGIN_SET_FAILED,
                [
                    'message'     => $e->getMessage(),
                    'entity_type' => $payment->getEntity(),
                    'entity_id'   => $payment->getId(),
                    'stack_trace' => $e->getTraceAsString(),
                ]);
        }
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
        //return if payment is of GPay Cards
        if ($payment->isGooglePayCard() === true)
        {
            return;
        }

        // return if method is not card or card is not international
        if (($payment->getMethod() !== Method::CARD) or
            ($payment->card->isInternational() === false))
        {
            return;
        }

        $this->validateInternationalAllowed($payment);

        $this->validateInternationalRecurringPaymentsAllowed($payment);
    }

    protected function runFraudChecksIfApplicable(Payment\Entity $payment)
    {
        $fallbacktoV1Flow = false;

        try
        {
            $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_RISKCHECK_INITIATED, $payment);

            $riskSource = Risk\Source::INTERNAL;

            // for now use api only for bin based blocking until shield is not live 100%
            $this->validateBlockedCard($payment);

            $razorxResult = $this->app->razorx->getTreatment($payment->getId(), 'shield_risk_evaluation', $this->mode);

            $this->trace->info(TraceCode::RAZORX_VARIANT_SHIELD, [
                'payment_id'     => $payment->getId(),
                'razorx_variant' => $razorxResult,
            ]);

            $shouldRunFraudDetectionV2 = (($razorxResult === 'shield_on') and
                                          ($payment->shouldRunShieldChecks() === true));

            if ($shouldRunFraudDetectionV2 === true)
            {
                $riskSource = Risk\Source::SHIELD;

                try
                {
                    $this->validateFraudDetectionV2($payment, $this->merchant);
                }
                catch (Exception\IntegrationException $exception)
                {
                    $fallbacktoV1Flow = true;
                }
                catch (\Requests_Exception $exception)
                {
                    $fallbacktoV1Flow = true;
                }
                finally
                {
                    $payment->setMetadataKey('shield_risk_execution', $razorxResult);
                }
            }

            /*
             * Firstly, $payment->shouldRunFraudChecks tells us whether maxmind can handle the request in the first
             * place.
             *
             * Now, provided maxmind can handle the request, we check:
             * If a fraud check ran on shield, then we do not fallback to maxmind.
             * If a fraud check was not run on shield, or it ran and failed, we fallback to maxmind.
             */
            if (($payment->shouldRunFraudChecks() === true) and
                (($shouldRunFraudDetectionV2 === false) or
                 ($fallbacktoV1Flow === true)))
            {
                $this->validateEmailTld($payment);

                $riskSource = Risk\Source::MAXMIND;

                $this->validateFraudDetection($payment, $this->merchant);
            }

            $this->app['diag']->trackPaymentEvent(
                EventCode::PAYMENT_RISKCHECK_PROCESSED,
                $payment,
                null,
                [
                    'risk_source'   => $riskSource
                ]);
        }
        catch (\Throwable $ex)
        {
            $this->app['diag']->trackPaymentEvent(
                EventCode::PAYMENT_RISKCHECK_PROCESSED,
                $payment,
                $ex,
                [
                    'riskSource' => $riskSource
                ]);

            throw $ex;
        }
    }

    /**
     * @deprecated
     *
     * This is called in 2 places, both after creation for payment/payment analytics
     * as both entity should have persisted at this time
     *
     * This is not called in case of emandate, BharatQR, and Bank Transfer.
     *
     * Currently this call happens after payment auth success or fail.
     * Ideally this should be a pre-auth step as we want to block the payment before authorization itself
     * But because current code limitation, and time constraint this has to be done this way.
     *
     * As per YV, to block the payment in pre-auth the whole class need to be refractored.
     *
     * @param Payment\Entity $payment
     */
    protected function runShieldCheck(Payment\Entity $payment)
    {
        if ($payment->getMetadata('shield_risk_execution') === 'on')
        {
            return;
        }

        // We do not want to call shield in case for Payments in Test mode
        if ($this->mode === Mode::TEST)
        {
            return;
        }

        if ($payment->isNach() === true)
        {
            return;
        }

        try
        {
            RunShieldCheck::dispatch($this->mode, $payment);
        }
        catch (\Throwable $e)
        {
             $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SHIELD_JOB_DISPATCH_ERROR
            );
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
                Risk\Entity::REASON     => Risk\RiskCode::PAYMENT_FAILED_DUE_TO_BLOCKED_CARD,
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
            $response = $this->runAuthorizeFailedOnGateway($payment);

            $this->authorizeFailedPaymentOnApi($payment, $response);
        });
    }

    protected function runAuthorizeFailedOnGateway(Payment\Entity $payment)
    {
        $data = ['payment' => $payment->toArrayGateway()];

        if ($payment->getGlobalOrLocalTokenEntity() !== null)
        {
            $data['token'] = $payment->getGlobalOrLocalTokenEntity();
        }

        if ($payment->isMethodCardOrEmi())
        {
            $data['card'] = $this->repo->card->fetchForPayment($payment)->toArray();
        }

        $response = $this->callGatewayFunction(Action::AUTHORIZE_FAILED, $data);

        return $response;
    }

    protected function forceAuthorizeFailedOnGateway(Payment\Entity $payment, array $input)
    {
        $data = [
            'payment' => $payment->toArray(),
            'gateway' => $input
        ];

        $flag = $this->callGatewayFunction(Action::FORCE_AUTHORIZE_FAILED, $data);

        if ($flag === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Payment expected to have succeeded on the gateway has actually not. ' .
                'Should not have called this function in this scenario');
        }
    }

    protected function authorizeFailedPaymentOnApi(Payment\Entity $payment, array $response)
    {
        $this->lockForUpdateAndReload($payment);

        if ($payment->isStatusCreatedOrFailed() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Payment being authorized is actually already authorized by some other thread.',
                null,
                ['payment_id' => $payment->getId()]);
        }

        $payment->setVerified(true);

        // handle the special caes when timeout cron marks a payment as failed
        // because of race conditions with verify,
        // We just need to reverse the things done in timeout cron, we dont
        // need to update the acquirer data here as that should have already
        // been set in the payment when payment was intitally authorized.
        if (($payment->hasBeenAuthorized() === true) and
            ($payment->isFailed() === true))
        {
            $payment->setErrorNull();

            $payment->setStatus(Payment\Status::AUTHORIZED);

            $payment->setLateAuthorized(true);
        }
        else
        {
            // The first argument marks the payment as converted from failed
            // to authorized
            $this->updateAndNotifyPaymentAuthorized($response, true);
        }

        $this->autoCapturePaymentIfApplicable($payment);

        $this->repo->saveOrFail($payment);

        $this->setPayment($payment);
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
                ($merchant->isFeeBearerCustomerOrDynamic() === true))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED,
                    null,
                    [
                        'convert_on_api'        => $merchant->convertOnApi(),
                        'fee_bearer_customer'   => $merchant->isFeeBearerCustomerOrDynamic(),
                        'payment_id'            => $payment->getId(),
                        'currency'              => $currency,
                    ]);
            }

            // mcc is supported only for card payments and wallet paypal.
            if ($payment->isMccSupported() === false)
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
        // will use lower than current rates hence we also use 1 percentage lower
        // values
        if ($payment->getConvertCurrency() === false)
        {
            $baseAmount = (int) ceil($baseAmount * 0.99);
        }

        $payment->setBaseAmount($baseAmount);
    }

    /**
     * @param Payment\Entity $payment
     * @param array          $input        Input data received from checkout/merchant.
     * @param array          $gatewayInput Data that is required by gateway for the payment to be processed.
     *
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
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

        $followGlobal = false;

        if (empty($input[Payment\Entity::SUBSCRIPTION_ID]) === false)
        {
            if ($this->subscription === null)
            {
                $this->subscription = $this->app['module']
                     ->subscription
                     ->fetchSubscriptionInfo(
                        [
                            Payment\Entity::AMOUNT          => $payment->getAmount(),
                            Payment\Entity::SUBSCRIPTION_ID => Subscription\Entity::getSignedId($payment->getSubscriptionId()),
                        ],
                        $payment->merchant,
                        $callback = true);
            }

            if ($this->subscription->isExternal() === false)
            {
                $this->associateSubscriptionToPayment($payment, $input);
            }
            // Even if external subscription, we can add customerId to input only here.
            // This function has to be called only after checkAndFillSavedAppToken.
            // Otherwise user session will not be set.
            $this->addCustomerIdToSubscriptionInput($input);

            $this->addTestSuccessFlagToGatewayInput($input, $gatewayInput);

            if ($this->subscription->isGlobal() === true)
            {
                $followGlobal = true;
            }
        }

        //
        // Appends dummy cvv if auth type of payment is skip. Validate merchant later
        // for moto feature else decline the payment.
        // TODO: Need to change if AMEX card is enabled for skip
        //
        if ($payment->getAuthType() === Payment\AuthType::SKIP)
        {
            $input['card']['cvv'] = Card\Entity::DUMMY_CVV;
        }

        // First fetch the relevant customer (global or local)
        list($customer, $customerApp) = (new Customer\Core)->getCustomerAndApp(
                                                                $input, $this->merchant, $followGlobal);

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

            //
            // TODO: all these checks won't work in case of auth transaction for subserv,
            // needs to be refactored so that we pass all the recurring input directly to processor
            //
            if ($this->subscription !== null)
            {
                //
                // If global, create a local customer and link that to the subscription.
                // $customer is global here currently, create its local copy.
                //
                if ($this->subscription->hasCustomer() === false)
                {
                    $localCustomer = $this->createLocalCustomerForSubscription($customer);
                }
                else
                {
                    //
                    // `else` is from the second charge onwards
                    // or change card flow.
                    //
                    $localCustomer = $this->subscription->customer;
                }
            }

            $this->preProcessPaymentForGlobalCustomer(
                $customer, $localCustomer, $customerApp, $payment, $input, $gatewayInput);
        }

        if ($payment->isEmi() === true)
        {
            $cardNumber = $gatewayInput['card']['number'];

            $emiDuration = $input['emi_duration'];

            $gatewayInput['emi_plan'] = $this->setBankAndEmiPlanDetails($payment, $cardNumber, $emiDuration);
        }

        if ($payment->isCardlessEmi() === true)
        {
            $gatewayInput['gateway'] = [
                'emi_duration' => $input['emi_duration']
            ];

            $merchantId = $payment->getMerchantId();

            $input = Customer\Validator::validateAndParseContactInInput($input);

            $contact = $input['contact'];

            if (isset($input['payment_id']) === true)
            {
                $paymentIdString = '_' . $input['payment_id'];
            }
            else
            {
                $paymentIdString = '';
            }

            $cacheKey = strtoupper($input[Payment\Entity::PROVIDER]) . '_' . $contact . '_' . $merchantId . $paymentIdString;

            $cacheKey = sprintf('gateway:emi_plans_%s', $cacheKey);

            $emiPlans = (array) $this->app['cache']->get($cacheKey, null);

            $key = array_search($input['emi_duration'], array_column($emiPlans, 'duration'));

            if ($key === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_EMI_DURATION_NOT_VALID,
                    null,
                    $input['emi_duration']);
            }
        }

        if ($payment->isUpi() === true)
        {
            $gatewayInput['upi']['flow'] = $input['_']['flow'] ?? null;

            if ((isset($input['_']['flow']) === false) or
                ($input['_']['flow'] !== 'intent'))
            {
                if (empty($payment->getVpa()) === true)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'The vpa field is required when method is upi.');
                }

                $this->setGatewayInputForUpi($input, $gatewayInput);

                $this->validateUpiPspIsAllowed($payment);
            }
            else
            {
                try
                {
                    $this->updateGatewayInputForInvoice($gatewayInput, $payment);
                }
                catch (\Throwable $e)
                {
                    $this->trace->traceException($e);
                }

                $this->validateIfIntentEnabled($payment);

                if (isset($input[Payment\Entity::UPI_PROVIDER]) === true)
                {
                    $this->validateIfOmnipayEnabled($payment);
                }
            }
        }

        if ($payment->isWallet() === true)
        {
            $gatewayInput['wallet']['flow'] = $input['_']['flow'] ?? null;
        }

        if ($payment->isAeps() === true)
        {
            $this->setGatewayInputForAeps($input, $gatewayInput);
        }

        $this->validateRecurringAndPreferredRecurring($payment, $input);

        $payment->setInternational();

        if (($this->subscription === null) or ($this->subscription->isExternal() === false))
        {
            $this->setRecurringType($payment, $input);
        }

        $this->setAutoRefundTimestamp($payment);

        $this->setPreferredAuthIfApplicable($payment);

        // this needs to be done after we have card entity as we need to know if card is debit or credit
        $this->validateForMaxAmount($input, $payment);
    }

    protected function setPreferredAuthIfApplicable(Payment\Entity $payment)
    {
        if (($payment->isMethodCardOrEmi() === false) or
            ($payment->getAuthType() !== null) or
            ($payment->isSecondRecurring() === true) or
            ($payment->isPushPaymentMethod() === true))
        {
            return;
        }

        if ($this->merchant->isFeatureEnabled(Feature\Constants::OTP_AUTH_DEFAULT) === true)
        {
            $preferredAuth = $payment->getMetadata(Payment\Entity::PREFERRED_AUTH, []);

            if (in_array(Payment\AuthType::PIN, $preferredAuth, true) === true)
            {
                return;
            }

            $payment->setMetadataKey(Payment\Entity::PREFERRED_AUTH, [Payment\AuthType::OTP, Payment\AuthType::_3DS]);
        }
    }

    protected function updateGatewayInputForInvoice(& $gatewayInput, Payment\Entity $payment)
    {
        $config = Cache::getFacadeRoot()->get(ConfigKey::NPCI_UPI_DEMO, []);

        $merchants = $config['merchants'] ?? [];

        if (isset($merchants[$payment->getMerchantId()]) === false)
        {
            return;
        }

        $elfin = $this->app['elfin'];

        $baseUrl = $merchants[$payment->getMerchantId()];

        $query = [
            'payment_id'    => $payment->getPublicId(),
            'amount'        => $payment->getAmount(),
            'contact'       => $payment->getContact(),
            'email'         => $payment->getEmail(),
            'description'   => $payment->getDescription(),
        ];

        $referenceUrl = $elfin->shorten($baseUrl . http_build_query($query));

        $shouldEncode = $config['should_encode_invoice_url'] ?? false;

        if ($shouldEncode === true)
        {
            $referenceUrl = urlencode($referenceUrl);
        }

        $gatewayInput['upi']['reference_url'] = $referenceUrl;
    }

    protected function validateRecurringAndPreferredRecurring(Payment\Entity $payment, array $input)
    {
        if ((isset($input[Payment\Entity::RECURRING]) === true) and
            ($input[Payment\Entity::RECURRING]) === '1')
        {
            if (in_array($payment->getMethod(), Payment\Method::$recurringMethods, true) === false)
            {
              throw new Exception\BadRequestValidationFailureException(
                    'Recurring field may be sent only when method is card, eMandate or upi');
            }
        }
        else if ($this->isPreferredRecurring($input) === true)
        {
            $recurring = false;

            if (($payment->isCard() === true) and
                ($payment->hasCard() === true) and
                ($payment->card->isRecurringSupported() === true))
            {
                $recurring = true;
            }

            $payment->setRecurring($recurring);
        }
    }

    protected function setRecurringType(Payment\Entity $payment, array $input)
    {
        $type = null;

        if ($payment->isRecurring() === true)
        {
            $token = $payment->getGlobalOrLocalTokenEntity();

            $type = Payment\RecurringType::INITIAL;

            if (($token !== null) and
                ($token->isLocal() === true) and
                ($token->isRecurring() === true) and
                (isset($input['token']) === true))
            {
                if (($this->app['basicauth']->isPrivateAuth() === true) or
                    ($this->app->runningInQueue() === true))
                {
                    $type = Payment\RecurringType::AUTO;
                }
            }
        }

        //
        // TODO: Will have to figure out the recurring type when we allow
        // the end-users to pay for the subscription themselves manually
        // before we charge. This can happen when we create an invoice first
        // and then an hour later, we auto-charge. In that 1 hr gap, the
        // customer can make a payment (via public auth and all)
        //
        if ($this->subscription !== null)
        {
            $type = Payment\RecurringType::AUTO;

            if ($this->subscription->hasBeenAuthenticated() === false)
            {
                $type = Payment\RecurringType::INITIAL;
            }
            else if ((isset($input[Subscription\Entity::SUBSCRIPTION_CARD_CHANGE]) === true) and
                     (boolval($input[Subscription\Entity::SUBSCRIPTION_CARD_CHANGE]) === true))
            {
                $type = Payment\RecurringType::CARD_CHANGE;
            }
        }

        $payment->setRecurringType($type);
    }

    protected function setAutoRefundTimestamp(Payment\Entity $payment)
    {
        $currentTime = Carbon::now()->getTimestamp();

        $minAutoRefundTime = $currentTime + Merchant\Entity::MIN_AUTO_REFUND_DELAY;

        $merchantAutoRefundTime = $currentTime + $payment->merchant->getAutoRefundDelay();

        $merchantAutoRefundTime = max($minAutoRefundTime, $merchantAutoRefundTime);

        if ($payment->isEmandate() === true)
        {
            $emandateAutoRefundTime = $currentTime + Merchant\Entity::AUTO_REFUND_DELAY_FOR_EMANDATE;

            $merchantAutoRefundTime = $emandateAutoRefundTime;
        }
        else if ($payment->isNach() === true)
        {
            $merchantAutoRefundTime = $currentTime + Merchant\Entity::AUTO_REFUND_DELAY_FOR_NACH;
        }

        $payment->setRefundAt($merchantAutoRefundTime);
    }

    protected function addTestSuccessFlagToGatewayInput(array $input, array & $gatewayInput)
    {
        if (isset($input['test_success']) === false)
        {
            return;
        }

        if (($this->mode === MODE::TEST) and
            ($this->ba->isProxyAuth() === true) and
            (isset($input[Payment\Entity::TOKEN]) === true))
        {
            $gatewayInput['test_success'] = boolval($input['test_success']);
        }
    }

    protected function createLocalCustomerForSubscription(
        Customer\Entity $customer)
    {
        $localCustomer = (new Customer\Core)->createLocalCustomerFromGlobal($customer, $this->subscription->merchant);

        $localCustomer->globalCustomer()->associate($customer);

        $this->repo->saveOrFail($localCustomer);

        return $localCustomer;
    }

    protected function addCustomerIdToSubscriptionInput(array & $input)
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
                    'subscription_id'   => $this->subscription->getId(),
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
        if ($this->subscription->hasCustomer() === true)
        {
            //
            // In case the subscription already has a customer
            // and that customer has a global customer, we should
            // also ensure that app_token is present in case of
            // second 2FA (change card). In the subsequent charges flow,
            // app_token won't be present anyway, since it's internal.
            //
            if ($this->subscription->isGlobal() === true)
            {
                $cardChange = boolval($input[Subscription\Entity::SUBSCRIPTION_CARD_CHANGE] ?? false);

                if (($cardChange === true) and
                    (empty($input[Payment\Entity::APP_TOKEN]) === true))
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_APP_TOKEN_ABSENT,
                        null,
                        [
                            'subscription_id' => $this->subscription->getId(),
                            'global' => true,
                        ]);
                }
            }

            $input[Payment\Entity::CUSTOMER_ID] = Customer\Entity::getSignedId($this->subscription->getCustomerId());
        }
    }

    protected function associateSubscriptionToPayment(Payment\Entity $payment, array $input)
    {
        $this->trace->info(
            TraceCode::PAYMENT_SUBSCRIPTION_ASSOCIATE,
            [
                'payment_id'        => $payment->getId(),
                'subscription_id'   => $this->subscription->getId(),
            ]);

        $payment->subscription()->associate($this->subscription);
    }

    protected function setGatewayInputForUpi($input, & $gatewayInput)
    {
        // Key may not be present. Hence `??` and not `?:`
        $gatewayInput['upi']['expiry_time'] = $input['upi']['expiry_time'] ??
                                              Processor::UPI_COLLECT_EXPIRY;
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
                    'subscription_id'   => $this->subscription->getId()
                ]);
        }

        // No card saving, normal simple flow
        if ($payment->isMethodCardOrEmi() and
            ($payment->isGooglePayCard() === false))
        {
            $payment->setSave(false);

            $payment->setRecurring(false);

            $vault = $payment->shouldSaveCard();

            $gatewayInput['card'] = $this->createCardEntity($input['card'], $vault, $this->merchant, $input);
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
        if (($this->ba->isProxyOrPrivilegeAuth() === false) and
            ($customerApp === null))
        {
            throw new Exception\LogicException(
                'Not privilege/proxy auth and no app_token. Should not have reached here at all.',
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

        if ($payment->isMethodCardOrEmi() === true)
        {
            $payment->localToken()->associate($token);

            $gatewayInput['card'] = $this->associateAndGetCardArrayForSavedToken($token, $input);
        }
        else if ($payment->isEmandate() === true)
        {
            $payment->setBank($token->getBank());

            $payment->localToken()->associate($token);
        }
        else if ($payment->isUpiRecurring() === true)
        {
            $payment->localToken()->associate($token);
        }
        else if ($this->shouldSaveVpaForUpiPayments() === true)
        {
            $payment->localToken()->associate($token);

            $vpa = $token->vpa;

            $payment->setVpa($vpa->getAddress());
        }
        else if ($payment->isNach() === true)
        {
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

        if (($payment->isMethodCardOrEmi() === true) and ($payment->isGooglePayCard() === false))
        {
            $gatewayInput['card'] = $this->createCardEntityFromSavedToken($token, $input);

            $payment->globalToken()->associate($token);

            $payment->card->globalCard()->associate($token->card);

            $this->repo->saveOrFail($payment->card);
        }
        else if ($payment->isWallet() === true)
        {
            $payment->setWallet($token->getWallet());

            $payment->globalToken()->associate($token);
        }
        else if ($payment->isEmandate() === true)
        {
            //
            // This should be here since, if a token is passed,
            // the bank would not be passed in the payment input.
            //
            $payment->setBank($token->getBank());

            $payment->globalToken()->associate($token);
        }
        else if ($payment->isUpiRecurring() === true)
        {
            $payment->globalToken()->associate($token);
        }
        else if ($this->shouldSaveVpaForUpiPayments() === true)
        {
            $payment->globalToken()->associate($token);

            $vpa = $token->vpa;

            $payment->setVpa($vpa->getAddress());
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
                       ($payment->isRecurring() === true) or
                       ($this->isPreferredRecurring($input) === true));

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
                       ($payment->isRecurring() === true) or
                       ($this->isPreferredRecurring($input) === true));

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
        if (($payment->isMethodCardOrEmi() === true) and ($payment->isGooglePayCard() === false))
        {
            $gatewayInput['card'] = $this->createCardEntity($input['card'], true, $customer->merchant, $input);

            $savedLocalCard = $payment->card;

            // save local saved card for local customer
            $token = $this->savePaymentMethod($customer, $payment, $savedLocalCard->getId(), $input);
        }
        else if ($payment->isEmandate() === true or $payment->isUpiRecurring() === true)
        {
            // save emandate bank locally for local customer
            $token = $this->savePaymentMethod($customer, $payment, null, $input);
        }
        else if ($this->shouldSaveVpaForUpiPayments() === true)
        {
            $token = $this->savePaymentMethod($customer, $payment, null, $input);
        }
        else if ($payment->isNach() === true)
        {
            $token = $this->savePaymentMethod($customer, $payment, null, $input);
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
            $gatewayInput['card'] = $this->createCardEntity($input['card'], true, $customer->merchant, $input);

            $savedGlobalCard = $payment->card;

            // create merchant local card entity and link to payment
            $gatewayInput['card'] = $this->createCardEntity($input['card'], false, $this->merchant, $input);

            // link local card to global card entity
            $payment->card->globalCard()->associate($savedGlobalCard);

            $this->repo->saveOrFail($payment->card);

            // save global saved card for global customer
            $token = $this->savePaymentMethod($customer, $payment, $savedGlobalCard->getId(), $input);
        }
        else if ($payment->isEmandate() === true or $payment->isUpiRecurring() === true)
        {
            // save emandate bank token globally for global customer
            $token = $this->savePaymentMethod($customer, $payment, null, $input);
        }
        else if ($this->shouldSaveVpaForUpiPayments() === true)
        {
            $token = $this->savePaymentMethod($customer, $payment, null, $input);
        }

        if ($token !== null)
        {
            $this->payment->globalToken()->associate($token);
        }
    }

    protected function savePaymentMethod(
        Customer\Entity $customer, Payment\Entity $payment, $savedCardId = null, array $input = []): Token\Entity
    {
        $this->trace->info(
            TraceCode::PAYMENT_SAVE_METHOD,
            [
                'method'            => $payment->getMethod(),
                'payment_id'        => $payment->getId(),
                'merchant_id'       => $payment->merchant->getId(),
                'customer_id'       => $customer->getId(),
                'local'             => $customer->isLocal(),
                'card_id'           => $savedCardId,
                'auth_type'         => $payment->getAuthType(),
                'account_number'    => $input[Payment\Entity::BANK_ACCOUNT][Payment\Entity::ACCOUNT_NUMBER] ?? null,
                'account_type'      => $input[Payment\Entity::BANK_ACCOUNT][Token\Entity::ACCOUNT_TYPE] ?? null,
                'beneficiary_name'  => $input[Payment\Entity::BANK_ACCOUNT][Payment\Entity::NAME] ?? null,
                'ifsc'              => $input[Payment\Entity::BANK_ACCOUNT][Payment\Entity::IFSC] ?? null,
                'max_amount'        => $input[Payment\Entity::RECURRING_TOKEN][Payment\Entity::MAX_AMOUNT] ?? null,
                'expire_by'         => $input[Payment\Entity::RECURRING_TOKEN][Payment\Entity::EXPIRE_BY] ?? null
            ]);

        $saveMethodInput = [
            Token\Entity::METHOD => $payment->getMethod()
        ];

        if ($payment->isMethodCardOrEmi())
        {
            $saveMethodInput[Token\Entity::METHOD] = Payment\Method::CARD;

            $saveMethodInput[Token\Entity::CARD_ID] = $savedCardId;
        }
        else if ($payment->isEmandate() === true)
        {
            $saveMethodInput[Token\Entity::BANK] = $payment->getBank();

            $saveMethodInput[Token\Entity::AUTH_TYPE] = $payment->getAuthType();

            $order = $payment->order;

            $tokenRegistration = $order->getTokenRegistration();

            $tokenMaxAmount = null;

            $tokenExpireBy  = null;

            if ($tokenRegistration !== null)
            {
                $tokenMaxAmount = $tokenRegistration->getMaxAmount();

                $tokenExpireBy  = $tokenRegistration->getExpireAt();
            }

            $saveMethodInput[Token\Entity::MAX_AMOUNT] =
                    $input[Payment\Entity::RECURRING_TOKEN][Payment\Entity::MAX_AMOUNT] ?? $tokenMaxAmount;

            $saveMethodInput[Token\Entity::ACCOUNT_NUMBER] =
                    $input[Payment\Entity::BANK_ACCOUNT][Payment\Entity::ACCOUNT_NUMBER] ?? null;

            $saveMethodInput[Token\Entity::ACCOUNT_TYPE] =
                $input[Payment\Entity::BANK_ACCOUNT][Token\Entity::ACCOUNT_TYPE] ?? null;

            $saveMethodInput[Token\Entity::BENEFICIARY_NAME] =
                    $input[Payment\Entity::BANK_ACCOUNT][Payment\Entity::NAME] ?? null;

            $saveMethodInput[Token\Entity::IFSC] =
                    $input[Payment\Entity::BANK_ACCOUNT][Payment\Entity::IFSC] ?? null;

            $saveMethodInput[Token\Entity::AADHAAR_NUMBER] =
                    $input[Payment\Entity::AADHAAR]['number'] ?? null;

            $saveMethodInput[Token\Entity::AADHAAR_VID] =
                $input[Payment\Entity::AADHAAR]['vid'] ?? null;

            $saveMethodInput[Token\Entity::EXPIRED_AT] =
                    $input[Payment\Entity::RECURRING_TOKEN][Payment\Entity::EXPIRE_BY] ?? $tokenExpireBy;
        }
        else if ($payment->isMethod(Payment\Method::WALLET))
        {
            $saveMethodInput[Token\Entity::WALLET] = $payment->getWallet();
        }
        else if ($payment->isMethod(Payment\Method::NACH) === true)
        {
            $order = $payment->order;

            $tokenRegistration = $order->getTokenRegistration();

            $tokenMaxAmount = null;

            if ($tokenRegistration !== null)
            {
                $saveMethodInput[Token\Entity::MAX_AMOUNT] = $tokenRegistration->getMaxAmount();

                $saveMethodInput[Token\Entity::AUTH_TYPE]  = $payment->getAuthType();

                $paperMandate = $tokenRegistration->paperMandate;

                if ($paperMandate !== null)
                {
                    $bankAccount = $paperMandate->bankAccount;

                    if ($bankAccount !== null)
                    {
                        $saveMethodInput[Token\Entity::BANK]             = $bankAccount->getBankCode();

                        $saveMethodInput[Token\Entity::BENEFICIARY_NAME] = $bankAccount->getBeneficiaryName();

                        $saveMethodInput[Token\Entity::ACCOUNT_NUMBER]   = $bankAccount->getAccountNumber();

                        $saveMethodInput[Token\Entity::ACCOUNT_TYPE]     = $bankAccount->getAccountType();

                        $saveMethodInput[Token\Entity::IFSC]             = $bankAccount->getIfscCode();
                    }

                    $saveMethodInput[Token\Entity::TERMINAL_ID] = $paperMandate->getTerminalId();

                    $saveMethodInput[Token\Entity::START_TIME]  = $paperMandate->getStartAt();

                    $saveMethodInput[Token\Entity::EXPIRED_AT]  = $paperMandate->getEndAt();
                }
            }
        }
        else if ($payment->isUpiRecurring() === true)
        {
            $saveMethodInput[Token\Entity::MAX_AMOUNT] =
                                        $input[Payment\Entity::RECURRING_TOKEN][Payment\Entity::MAX_AMOUNT] ?? null;
            $saveMethodInput[Token\Entity::EXPIRED_AT] =
                                            $input[Payment\Entity::RECURRING_TOKEN][Payment\Entity::EXPIRE_BY] ?? null;
            $saveMethodInput[Token\Entity::START_TIME] =
                                            $input[Payment\Entity::RECURRING_TOKEN][Token\Entity::START_TIME] ?? null;
        }
        else if ($payment->isUpi() === true)
        {
            $saveMethodInput[Token\Entity::METHOD] = Payment\Method::UPI;

            $vpa = $this->createVpaEntity($input);

            $saveMethodInput[Token\Entity::VPA_ID] = $vpa[PaymentsUpi\Vpa\Entity::ID];
        }

        $token = null;

        // @codingStandardsIgnoreStart
        try
        {
            $token = (new Token\Core)->create($customer, $saveMethodInput);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);
        }
        // @codingStandardsIgnoreEnd

        $this->app['diag']->trackPaymentEvent(
            EventCode::PAYMENT_CARDSAVING_PROCESSED,
            $payment,
            null,
            [
                'local' => $token->isLocal()
            ]);

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

            case Payment\Method::EMANDATE:
                $this->verifyEmandateEnabled();
                break;

            case Payment\Method::CARDLESS_EMI:
                $this->verifyCardlessEmiEnabled();
                break;

            case Payment\Method::PAYLATER:
                $this->verifyPayLaterEnabled();
                break;

            case Payment\Method::NACH:
                $this->verifyNachEnabled();
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

        // Set emi plan id
        $emiPlan = $this->getMerchantEmiPlans($iinEntity, $emiDuration, $payment->merchant);

        $payment->setEmiSubvention(Emi\Subvention::CUSTOMER);

        $payment->emiPlan()->associate($emiPlan);

        return $emiPlan->toArray();
    }

    protected function fillReturnRequestDataForMerchant(Payment\Entity $payment, array & $returnData)
    {
        assertTrue ($payment->getCallbackUrl() !== null);

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

            case $this->canRunAsyncIntentPaymentFlow($payment):

                return $this->getIntentPaymentCreatedResponse($request, $payment);

            case $this->canRunOtpPaymentFlow($payment):

                return $this->getOtpPaymentCreatedResponse($request, $payment);

            case $this->canRunGooglePayCardPaymentFlow($payment):

                return $this->getGooglePayCardPaymentCreatedResponse($request, $payment);

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

        return $response;
    }

    protected function getIntentPaymentCreatedResponse($request, Payment\Entity $payment): array
    {
        $id = $payment->getPublicId();

        $response = [
            'type'          => 'intent',
            'version'       => 1,
            'payment_id'    => $id,
            'gateway'       => $this->getEncryptedGatewayText($payment->getGateway()),
            'data'          => $request['data'],
            'request'       => [
                'url'    => $this->route->getUrlWithPublicAuthInQueryParam('payment_get_status', ['id' => $id]),
                'method' => 'GET',
            ]
        ];

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

        $data['magic'] = $this->isMagicEnabled($payment);

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
                'global_token_id' => $payment->getGlobalTokenId(),
                'gateway_data'    => $data,
            ]);

        //
        // TODO: Update token stats. Assuming same token is not getting
        // used in multiple payments. Actually we should be locking.
        //

        $createdAt = $payment->getCreatedAt();

        $token->setUsedAt($createdAt);

        $token->incrementUsedCount();

        $oldRecurringStatus = $token->getRecurringStatus();

        if ($payment->isRecurring() === true)
        {
            $this->updateTokenOnAuthorizedForRecurring($payment, $token, $data);
        }

        if (($token->isRecurring() === false) and
            ($token->getRecurringStatus() === null))
        {
            $token->setRecurringStatus(Token\RecurringStatus::NOT_APPLICABLE);
        }

        $this->repo->saveOrFail($token);

        //
        // This flow gets called for non recurring tokens also.
        //
        $this->eventTokenStatus($token, $oldRecurringStatus);
    }

    protected function updateTokenOnCreatedIfRequired($payment, $data)
    {
        if ($payment->isUpiRecurring() === true)
        {
            $token = $payment->getGlobalOrLocalTokenEntity();

            if (empty($data['data']['recurring_status']) === false)
            {
                $gatewayRecurringStatus = $data['data']['recurring_status'];

                $token->setRecurringStatus($gatewayRecurringStatus);

                $this->repo->saveOrFail($token);
            }
        }
    }

    protected function updateTokenOnAuthorizedForRecurring(
        Payment\Entity $payment, Token\Entity $token, array $data)
    {
        //
        // For subscriptions, we always create and set terminal in
        // gateway token, irrespective of whether the token is already
        // recurring or not.
        // If an existing recurring token is used for another subscription,
        // we create another gateway token, since these two subscriptions
        // can have different terminals.
        // In case of charge-at-will, we don't have any way to know whether
        // it's a different subscription that is being done with an existing
        // recurring token. We cannot use public_auth check since we can
        // get the request from private_auth also.
        //

        //
        // This is just in case. Payment recurring is anyway only
        // allowed on cards and emandate.
        //
        if (($payment->isCard() === false) and
            ($payment->isEmandate() === false) and
            ($payment->isUpiRecurring() === false))
        {
            return;
        }

        //
        // For emandate payments, we create a new token for every
        // single new first recurring payment.
        // For existing recurring emandate tokens, we do not update it.
        // TODO: Remove this when we allow using the same token again
        // for another recurring payment.
        //
        if (($payment->isEmandate() === true) and
            ($token->isRecurring() === true))
        {
            return;
        }

        if ($payment->isCard() === true)
        {
            $token->setRecurring(true);
            // TODO: Back fill the data for all the other recurring card tokens!
            $token->setRecurringStatus(Token\RecurringStatus::CONFIRMED);
        }
        else if ($payment->isEmandate() === true)
        {
            $this->updateTokenOnAuthorizedForEmandateRecurring($token, $data, $payment);
        }
        else if ($payment->isUpiRecurring() === true)
        {
            $this->updateTokenOnAuthorizedForUpiRecurring($token, $data, $payment);
        }

        // Not required as we only use terminals through
        // gateway_token, and not through token itself.
        // TODO: Remove this
        $token->terminal()->associate($payment->terminal);

        $this->createAndSetTerminalInGatewayToken($payment, $token);
    }

    /**
     * We update the token details and not gateway token details
     * because the merchant is exposed to only the token.
     * If we have two gateway tokens and a single token, which
     * gateway token's details do we return back?
     * On the other hand, if we have two gateway tokens for
     * the same token and we store the recurring details in the
     * token entity, we will end up overriding the recurring_status
     * and other details. So, we need to ensure that we don't reuse
     * the same token.
     * Anyway, currently, we don't reuse the same token for emandate.
     * The customer always gets a new token if they want to
     * subscribe to another subscription.
     * If we don't use the same token again, there's no issue
     * since there will always be only one terminal.
     * Gateway Tokens purpose was to handle multiple terminals
     * for same token only.
     *
     * @param Token\Entity      $token
     * @param array             $gatewayData
     * @param Payment\Entity    $payment
     */
    protected function updateTokenOnAuthorizedForEmandateRecurring(
        Token\Entity $token, array $gatewayData, Payment\Entity $payment)
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

        (new Token\Core)->updateTokenFromEmandateGatewayData($token, $gatewayData);
    }

    protected function createAndSetTerminalInGatewayToken(Payment\Entity $payment, Token\Entity $token)
    {
        $reference = $payment->getReferenceForGatewayToken();

        $gatewayTokens = $this->repo->gateway_token->findByTokenAndReference($token, $reference);

        $gateway = $payment->getGateway();

        $gatewayTokensToUpdate = $gatewayTokens->filter(
                                        function($gatewayToken) use ($gateway)
                                        {
                                            return ($gatewayToken->getGateway() === $gateway);
                                        });

        //
        // This is the case that the payment is a first recurring payment
        //
        if ($gatewayTokensToUpdate->count() === 0)
        {
            (new GatewayToken\Core)->create($payment, $token, $reference);
        }
        else
        {
            //
            // There will be only one for sure.
            // There can't be more than 1 because, the only time we create is
            // when there doesn't exist a single gateway_token of the gateway.
            // All other cases, we only update the existing one. Hence, there
            // can never be more than one gateway_token of a gateway.
            //

            if ($gatewayTokensToUpdate->count() > 1)
            {
                $this->trace->critical(
                    TraceCode::GATEWAY_TOKEN_TOO_MANY_PRESENT,
                    [
                        'count'         => $gatewayTokensToUpdate->count(),
                        'payment_id'    => $payment->getId(),
                        'token_id'      => $token->getId()
                    ]);

                //
                // This is unexpected behaviour and should never
                // happen and hence just returning back from here.
                //
                return;
            }

            if ($payment->isEmandate() === true)
            {
                //
                // We do not reuse the tokens in case of emandate.
                // Every new registration requires a new
                // token to be created.
                //
                throw new Exception\LogicException(
                    'Tokens cannot be reused in emandate payments',
                    null,
                    [
                        'payment'        => $payment->toArray(),
                        'gateway_tokens' => $gatewayTokens->toArray(),
                    ]);
            }

            $gatewayTokenToUpdate = $gatewayTokensToUpdate->first();

            $gatewayTokenToUpdate->terminal()->associate($payment->terminal);

            $this->repo->saveOrFail($gatewayTokenToUpdate);
        }
    }

    protected function handleOtpElfFailureWithSameGatewayRetry($e, $payment): bool
    {
        if ($e->getCode() !== ErrorCode::SERVER_ERROR_OTP_ELF_FAILED_FOR_RUPAY)
        {
            return false;
        }

        $gateway = $payment->getGateway();

        $this->headlessError = true;

        $retryableGateway = [Payment\Gateway::HDFC, Payment\Gateway::HITACHI, Payment\Gateway::PAYSECURE];

        $payment->setAuthType(Payment\AuthType::_3DS);

        if (in_array($gateway, $retryableGateway) === false)
        {
            return false;
        }

        $traceData = array(
            'payment_id'    => $payment->getId(),
            'gateway'       => $gateway,
            'terminal_id'   => $payment->terminal->getId()
        );

        $this->trace->info(TraceCode::PAYMENT_AUTH_RETRY_RUPAY_SAME_GATEWAY, $traceData);

        return true;
    }

    protected function migrateCardDataIfApplicable($payment)
    {
        try
        {
            if (($payment->isMethodCardOrEmi() === false) or
                ($payment->card->getVault() !== Card\Vault::RZP_ENCRYPTION))
            {
                return;
            }

            $input = [
                'payment_id' => $payment->getId(),
                'card_id'    => $payment->card->getId(),
                'token'      => $payment->card->getVaultToken(),
                'mode'       => $this->mode,
            ];

            $this->trace->info(
                TraceCode::VAULT_TOKEN_MIGRATION_REQUEST_INIT,
                [
                    'input' => $input,
                ]);

            Jobs\CardVaultMigrationJob::dispatch($input, $this->mode);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::VAULT_TOKEN_MIGRATION_DISPATCH_FAILED,
                ['payment_id' => $payment->getId()]
            );

            throw $e;
        }
    }

    protected function updateAndNotifyPaymentAuthorized(array $data = [], bool $wasFailed = false)
    {
        // Updates payment entity to authorized and adds a transaction.
        $updated = $this->updatePaymentAuthorized($data, $wasFailed);

        $this->migrateCardDataIfApplicable($this->payment);

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

        (new Payment\Metric)->pushAuthMetrics($this->payment);

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

        //
        // Needs to be before capture, since disount amount
        // is used to decide whether to capture or not
        //
        $this->postPaymentAuthorizeOfferProcessing($payment);

        // Auto capture payment, if applicable
        $this->autoCapturePaymentIfApplicable($payment);

        $this->postPaymentAuthorizeSubscriptionProcessing($payment);

        $this->postPaymentAuthorizePaymentLinkProcessing($payment);

        $this->postPaymentAuthorizeSubscriptionRegistrationProcessing($payment);

        return $this->processAuthorizeResponse($payment);
    }

    protected function postPaymentAuthorizeOfferProcessing(Payment\Entity $payment)
    {
        if ($payment->hasOrder() === false)
        {
            return;
        }

        $order = $payment->order;

        $this->offer = $payment->getOffer();

        if($payment->getOffer() === null)
        {
            return;
        }

        if($payment->getOffer()->getOfferType() !== Offer\Constants::INSTANT_OFFER)
        {
            return;
        }

        $discountAmount = $this->offer->getDiscountAmountForPayment($order->getAmount(), $payment);

        $discountInput = [
            Discount\Entity::AMOUNT => $discountAmount,
        ];

        (new Discount\Service)->create($discountInput, $payment, $this->offer);
    }

    /**
     * Post payment authorization we initiate auto capture and let payment link's core method take care of further
     * action to be taken - e.g. update it's own entities, refund payment if this comes out as extra payment etc.
     *
     * @param Payment\Entity $payment
     */
    protected function postPaymentAuthorizePaymentLinkProcessing(Payment\Entity $payment)
    {
        // We are moving this logic to apieventsubscriber after payment capture to update payment pge
        // details. Edge cases like late auth can be handled better there. Also moving the logic out of core payment module

        return;
    }

    protected function postPaymentAuthorizeSubscriptionRegistrationProcessing(Payment\Entity $payment)
    {
        if ($payment->hasInvoice() === false)
        {
            return;
        }

        $invoice = $payment->invoice;

        if ($invoice->getEntityType() !== Entity::SUBSCRIPTION_REGISTRATION)
        {
            return;
        }

        $subscriptionRegistration = $invoice->entity;

        $token = $payment->getGlobalOrLocalTokenEntity();

        (new SubscriptionRegistration\Core)->associateToken($subscriptionRegistration, $token);
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
            // We have lot of logic around when to auto-capture and when not to.
            // This is difficult to write in the current auto-capture function.
            // Based on whether to auto-capture or not, we also do auto-refund.
            // In the normal flow, we throw an exception if capture fails for
            // any reason. But, here, we catch the exception.
            //

            if (($this->subscription === null) or ($this->subscription->isExternal() === true))
            {
                return;
            }

            $subscription = $this->subscription;

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

        if ($payment->isRecurringTypeCardChange() === true)
        {
            $this->processCardChangeForSubscription($subscription, $payment);

            return;
        }

        $oldStatus = $subscription->getStatus();

        $this->captureSubscriptionPayment($subscription, $payment);

        $options = [
            Subscription\Event::PAYMENT => $payment,
        ];

        (new Subscription\Core)->triggerSubscriptionAlreadyAuthenticatedNotification(
                                                                            $subscription,
                                                                            $oldStatus,
                                                                            $options);
    }

    protected function processCardChangeForSubscription(
        Subscription\Entity $subscription,
        Payment\Entity $payment)
    {
        $this->updateSubscriptionToken($subscription, $payment);

        $core = (new Subscription\Core);

        //
        // We would have charged the last invoice also.
        // This becomes more or less the same as normal retry success.
        // The only difference is that we update the token.
        //
        if ($subscription->isPending() === true)
        {
            $this->captureSubscriptionPayment($subscription, $payment);

            $options = [
                Subscription\Event::PAYMENT         => $payment,
                Subscription\Event::INVOICE_CHARGED => true,
                Subscription\Event::REACTIVATED     => true,
            ];

            $core->triggerSubscriptionNotification($subscription, Subscription\Event::CARD_CHANGED, $options);

            return;
        }

        //
        // We need this to fire a webhook later.
        //
        $activated = false;

        $notifyOptions = [
            Subscription\Event::PAYMENT         => $payment,
            Subscription\Event::INVOICE_CHARGED => false,
        ];

        $oldStatus = $subscription->getStatus();

        if ($oldStatus !== Subscription\Status::ACTIVE)
        {
            $subscription->setStatus(Subscription\Status::ACTIVE);

            //
            // If old status is anything but active, that means card is being changed
            // on a failing subscription. Since payment has succeeded, the error fields
            // can now be reset. These fields would have been reset in another flow
            // if the charge had succeeded without card change anyway.
            //
            $subscription->resetErrorFields();

            $activated = true;

            $notifyOptions[Subscription\Event::REACTIVATED] = true;
        }

        $this->refundAuthorizedPayment($payment);

        $this->repo->saveOrFail($subscription);

        $core->triggerSubscriptionNotification($subscription, Subscription\Event::CARD_CHANGED, $notifyOptions);

        if ($activated === true)
        {
            $core->fireWebhookForStatusUpdate($subscription, Subscription\Status::ACTIVE, $payment);
        }
    }

    protected function captureSubscriptionPayment(Subscription\Entity $subscription, Payment\Entity $payment)
    {
        if ($this->shouldAutoCaptureAlreadyAuthenticatedSubscription($payment) === true)
        {
            $this->autoCapturePayment($payment);

            $invoice = $payment->invoice;

            //
            // Currently manual charge of pending invoices is not allowed, but it should be.
            // When we do allow that, handleCaptureSuccess will be used to set time fields.
            //
            // We don't set any time fields when the subscription is moved to pending.
            // We do that only when the subscription is moved to halted, and the cron
            // keeps making invoices and moving the subscription to the next period.
            //
            (new Subscription\Charge)->handleCaptureSuccess($subscription, $payment, $invoice);
        }
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

            $invoice = $payment->invoice;

            (new Subscription\Charge)->handleCaptureSuccess($subscription, $payment, $invoice, true);
        }

        $this->autoRefundAuthTransactionIfApplicable($payment, $subscription);

        (new Subscription\Core)->triggerSubscriptionAuthenticatedNotification($payment, $subscription);
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

        //
        // Used for subscription fetch via dashboard
        // Needed to rearchitect subscriptions as a separate service
        //
        $subscription->setCustomerEmail($paymentCustomer->getEmail());
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
     * @throws Exception\LogicException
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
        // Proxy auth means the payment is being made from dashboard,
        // typically for a test charge. In this case as well, we cannot
        // and should not add the signature to the response.
        //
        // In case of batch payments (emandate, recurring, etc), this
        // flow comes in via queue. In queue, we don't set the key. We
        // don't need signature and stuff when being run in queue anyway.
        //

        if (($this->app['basicauth']->isProxyOrPrivilegeAuth() === false) and
            ($this->app->runningInQueue() === false))
        {
            if ($payment->hasSubscription() === true)
            {
                $this->fillReturnDataWithSubscription($payment, $returnData);
            }
            else if ($payment->hasInvoice() === true)
            {
                $invoice = $payment->invoice;

                // No assert check if invoice is of subscription registration type.
                // For emandate auth links, the payment wont be captured immediately.
                if ($invoice->isTypeOfSubscriptionRegistration() === false)
                {
                    assertTrue($payment->hasBeenCaptured() === true);
                }

                $this->fillReturnDataWithInvoice($payment, $returnData);
            }
            else if ($payment->hasOrder() === true)
            {
                //
                // In case of async emandate registration payment, though
                // payment_capture would be set in the order, we wouldn't
                // have actually captured it if the registration is async.
                // We would capture it later once the token is confirmed as recurring.
                // In case of async emandate debit payment, the flow would
                // never reach here, since the payment would be in created
                // state and a different function is called for that.
                //
                if (($payment->order->getPaymentCapture() === true) and
                    ($payment->isFileBasedEmandateRegistrationPayment() === false))
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

        $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_RESPONSE_SENT, $payment);

        $returnData = $this->addEmandateDisplayDetailsIfApplicable($returnData, $payment);

        return $returnData;
    }

    protected function fillReturnDataWithSubscription(Payment\Entity $payment, array & $data)
    {
        if ($this->subscription === null)
        {
            $this->subscription = $this->app['module']
                                       ->subscription
                                       ->fetchSubscriptionInfo(
                                           [
                                                Payment\Entity::AMOUNT          => $payment->getAmount(),
                                                Payment\Entity::SUBSCRIPTION_ID => Subscription\Entity::getSignedId($payment->getSubscriptionId()),
                                            ],
                                            $payment->merchant,
                                            $callback = true);
        }

        $data['razorpay_subscription_id'] = $this->subscription->getPublicId();

        $this->fillReturnDataWithSignatureIfApplicable($data);
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

        if ($invoice->isTypeOfSubscriptionRegistration() === true)
        {
            $data['razorpay_order_id']        = $invoice->order->getPublicId();
        }
        else
        {
            $data['razorpay_invoice_id']      = $invoice->getPublicId();
            $data['razorpay_invoice_status']  = $invoice->getStatus();
            $data['razorpay_invoice_receipt'] = $invoice->getReceipt();
        }

        $this->fillReturnDataWithSignatureIfApplicable($data);
    }

    protected function fillReturnDataWithOrder(Payment\Entity $payment, array & $data)
    {
        $data['razorpay_order_id'] = $payment->order->getPublicId();

        $this->fillReturnDataWithSignatureIfApplicable($data);
    }

    protected function fillReturnDataWithSignatureIfApplicable(array & $data)
    {
        // If the accessed via keyless flow(public auth routes) and key doesn't exists, skips calculating signatures.
        // Otherwise, we calculate signature with secret from either API keys or OAuth client or partner's dummy client.
        if (($this->ba->isPublicAuth() === true) and
            ($this->ba->getKeyEntity() === null) and
            ($this->ba->getOAuthClientId() === null) and
            ($this->ba->isPartnerAuth() === false))
        {
            return;
        }

        $data['razorpay_signature'] = $this->getSignature($data);
    }

    /**
     * @param boolean $wasFailed If a payment is being converted from failed to authorized.
     */
    protected function notifyAuthorized(bool $wasFailed)
    {
        // Trigger notification events for authorization

        if ($this->payment->hasSubscription() === true)
        {
            return;
        }

        $event = Payment\Event::AUTHORIZED;

        if ($wasFailed === true)
        {
            $event = Payment\Event::FAILED_TO_AUTHORIZED;

            $currentTime = Carbon::now()->getTimestamp();

            // If a payment has been authorized 15 minutes after the creation, we do notsend a notification.
            if (($this->payment->getCreatedAt() - $currentTime) > self::FAILED_TO_AUTHORIZED_NOTIFY_DURATION)
            {
                return;
            }
        }

        if ($this->payment->hasInvoice() === true)
        {
            $event = Payment\Event::INVOICE_PAYMENT_AUTHORIZED;
        }

        (new Notify($this->payment))->trigger($event);
    }

    public function notifyMigratedCard($payment)
    {
        $this->payment = $payment;

        $this->notifyIfCardSaved();
    }

    protected function notifyIfCardSaved()
    {
        $payment = $this->payment;

        if (($payment->isMethod(Payment\Method::CARD)) and
            ($payment->getSave() === true) and
            ($payment->getGlobalTokenId() !== null) and
            ($payment->card->getVault() !== Card\Vault::RZP_ENCRYPTION))
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

    /**
     * Fire event when token status is confirmed or rejected
     *
     * @param Token\Entity $token
     * @param string|null  $oldRecurringStatus
     */
    public function eventTokenStatus(Token\Entity $token, string $oldRecurringStatus = null)
    {
        $currentRecurringStatus = $token->getRecurringStatus();

        //
        // Old recurring status can be the same as current
        // recurring status in cases like second recurring
        // payment. Here, we don't update anything at all
        // except the used count, terminals and stuff.
        //
        // This can also happen in case we do registration recon of
        // enach rbl again. This will ensure idempotency is maintained.
        //
        if (($oldRecurringStatus !== $currentRecurringStatus) and
            (Token\RecurringStatus::isWebhookStatus($currentRecurringStatus) === true))
        {
            $event = 'api.token.' . $currentRecurringStatus;

            $eventPayload = [
                ApiEventSubscriber::MAIN => $token,
            ];

            $this->app['events']->fire($event, $eventPayload);
        }
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

            (new TerminalAnalytics\Core)->create($log, $payment);

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

    protected function setAnalyticsLog(Payment\Entity $payment)
    {
        try
        {
            $paymentAnalytics = (new Analytics\Core)->create($payment);

            $payment->setMetadataKey('payment_analytics', $paymentAnalytics);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::WARNING,
                TraceCode::PAYMENT_ANALYTICS_SAVE_FAILED);
        }
    }

    protected function callGatewayAuthorize(Payment\Entity $payment, array $data)
    {
        $response = $this->callGatewayFunction(Action::AUTHORIZE, $data);

        return $response;
    }


    /**
     * Do we support the OTP flow for a given payment
     * and input combination
     *
     * @param  Payment\Entity $payment
     *
     * @return bool
     */
    protected function canRunOtpPaymentFlow(Payment\Entity $payment, array $gatewayInput = []): bool
    {
        // All the IVR terminal use Otp payment flow regardless of their method
        if ($payment->terminal->isIvr() === true)
        {
            return true;
        }

        // If the payment is card payment with headless browser flow then
        // we render the otp submission page to the user
        if (($payment->isMethodCardOrEmi() === true) and ($payment->isGooglePayCard() === false))
        {
            if ($payment->card->iinRelation !== null)
            {
                $authType = $payment->getAuthType();

                if (empty($gatewayInput['auth_type']) === false)
                {
                    if ($gatewayInput['auth_type'] === Payment\AuthType::OTP)
                    {
                        return true;
                    }

                    if ($payment->getAuthType() === Payment\AuthType::HEADLESS_OTP)
                    {
                        return true;
                    }

                    return false;
                }

                //
                // This check is specifically for Hitachi Axis Expresspay
                // Also, the order of the checks matter here since the second
                // condition covers a superset.
                //
                if (((Payment\Gateway::isOnlyAuthorizationGateway($payment->getGateway()) === true) or
                     ($payment->terminal->getCapability() === Terminal\Capability::AUTHORIZE)) and
                    ($this->isAuthTypeOtp($payment) === true))
                {
                    if ($this->canRunAxisExpressPay($payment) === true)
                    {
                        return true;
                    }

                    if ($this->canRunIvrFlow($payment) === true)
                    {
                        return true;
                    }
                }

                if (($payment->getGateway() === Payment\Gateway::BAJAJ) and
                    ($payment->isEmi() === true))
                {
                    return true;
                }

                if ($payment->getAuthType() === Payment\AuthType::HEADLESS_OTP)
                {
                    return true;
                }
            }

            return false;
        }

        $wallet = $payment->getWallet();

        //Paylater ICICI has otp flow enabled
        if (($payment->isPayLater() === true) and
            ($wallet === PayLater::ICICI))
        {
            return true;
        }

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

        //
        // We are doing this for Olamoney to maintain a smooth
        // transition from Olamoney Power wallet to Olamoney Postpaid
        //
        if ($wallet === Wallet::OLAMONEY)
        {
            if ($payment->terminal->isIvr() === false)
            {
                return false;
            }
        }

        // TODO: Figure out a way to do this for other power wallets

        return true;
    }

    protected function canRunAxisExpressPay(Payment\Entity $payment)
    {
        if (($payment->merchant->isAxisExpressPayEnabled() === true) and
            ($payment->card->iinRelation !== null) and
            ($payment->card->iinRelation->getIssuer() === IFSC::UTIB) and
            ($this->isAuthTypeOtp($payment) === true) and
            ($payment->card->iinRelation->supports(IIN\Flow::OTP) === true))
        {
            return true;
        }

        return false;
    }

    protected function  canRunIvrFlow(Payment\Entity $payment)
    {
        if (($payment->merchant->isFeatureEnabled(Feature\Constants::IVR) === true) and
            ($payment->card->iinRelation !== null) and
            ($this->isAuthTypeOtp($payment) === true) and
            ($payment->card->iinRelation->supports(IIN\Flow::IVR) === true))
        {
            return true;
        }

        return false;
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
            (Payment\Gateway::supportsAsync($payment->getGateway()) === true) and
            (($payment->getMetadata('flow') !== 'intent') or
             ($payment->getMetadata(Payment\Entity::UPI_PROVIDER, null) !== null)))
        {
            return true;
        }

        return false;
    }

    protected function canRunAsyncIntentPaymentFlow($payment)
    {
        if (($this->canRunAsyncIntentPaymentFlowUpi($payment) === true) or
            ($this->canRunAsyncPaymentFlowWallet($payment) === true))
        {
            return true;
        }

        return false;
    }

    protected function canRunAsyncIntentPaymentFlowUpi($payment)
    {
        if ((Payment\Method::supportsAsync($payment->getMethod()) === true) and
            (Payment\Gateway::supportsAsync($payment->getGateway()) === true) and
            ($payment->getMetadata('flow') === 'intent'))
        {
            return true;
        }

        return false;
    }

    protected function canRunAsyncPaymentFlowWallet($payment)
    {
        if (($payment->getMethod() === Payment\Method::WALLET) and
            ($payment->getGateway() === Payment\Gateway::WALLET_PHONEPE) and
            ($payment->getMetadata('flow') === 'intent'))
        {
            return true;
        }

        return false;
    }

    protected function canRunGooglePayCardPaymentFlow($payment)
    {
        if ($payment->isGooglePayCard() === true)
        {
            return true;
        }

        return false;
    }

    protected function runAutoDebitFlow(Payment\Entity $payment, array $gatewayInput)
    {
        $this->trace->info(
            TraceCode::PAYMENT_POWER_WALLET_INITIATED,
            [
                'payment_id'  => $payment->getId(),
                'gateway'     => $payment->getGateway(),
                'terminal_id' => $payment->getTerminalId(),
            ]);

        try
        {
            $gatewayInput['isAutoDebitFlow'] = true;

            $this->callGatewayFunction(Action::CHECK_BALANCE, $gatewayInput);
        }
        catch (Exception\GatewayErrorException $e)
        {
            $error = $e->getError();

            //
            // If the accessToken for the wallet is invalid, run the
            // otpGenerate flow for it.
            //
            if ($error->getInternalErrorCode() === ErrorCode::BAD_REQUEST_PAYMENT_WALLET_INVALID_GATEWAY_TOKEN)
            {
                return $this->callGatewayFunction(Action::OTP_GENERATE, $gatewayInput);
            }

            throw $e;
        }

        return $this->callGatewayFunction(Action::DEBIT, $gatewayInput);
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

    /**
     * Creates the card entity
     *
     * @param array $cardInput
     * @param bool $vault
     * @param Merchant\Entity $merchant
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    protected function createCardEntity(array $cardInput, bool $vault, Merchant\Entity $merchant, array $input = [])
    {
        $this->setRzpVaultForPayment($cardInput, $vault, $merchant, $input);

        $cardCore = new Card\Core;

        $recurring = (($this->payment->isRecurring()) or
                      ($this->isPreferredRecurring($input)));

        $cardData = $cardCore->createAndReturnWithSensitiveData($cardInput, $merchant, $recurring);

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

    protected function createVpaEntity($input)
    {
        $vpaCore = new PaymentsUpi\Vpa\Core;

        $vpa = $vpaCore->firstOrCreate($input);

        return $vpa->toArray();
    }

    protected function setRzpVaultForPayment(array &$cardInput, bool $vault, Merchant\Entity $merchant, array $input = [])
    {
        $merchantIds = [
            '8S0i1kWYyF2woQ', // swiggy
        ];

        if (in_array($merchant->getId(), $merchantIds, true) === true)
        {
            $vault = true;
        }

        //
        // Creates card entity. Card number is vaulted if vault is true
        //
        $cardInput[Card\Entity::VAULT] = Card\Vault::RZP_VAULT;

        if (isset($cardInput[Card\Entity::VAULT]) === true)
        {
            $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_CARDSAVING_INITIATED, $this->payment);
        }
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

        $cardNumber = (new Card\CardVault)->getCardNumber($card->getVaultToken());

        // Recurring terminals accept null cvv.
        $cvv = isset($input['card']['cvv']) ? $input['card']['cvv'] : null;

        $this->payment->card()->associate($card);

        $iin = $this->app['repo']->iin->find($card['iin']);

        return array_merge(
                $card->toArray(),
                [
                    'number' => $cardNumber,
                    'cvv' => $cvv,
                    'message_type' => $iin['message_type'],
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

        $cardNumber = (new Card\CardVault)->getCardNumber($card->getVaultToken());

        $cvv = isset($input['card']['cvv']) ? $input['card']['cvv'] : null;

        $savedCard = $token->card->toArray();
        $savedCard['number'] = $cardNumber;
        $savedCard['cvv'] = $cvv;

        // Create a card entity for merchant
        $cardCore = new Card\Core;

        $card = $cardCore->createDuplicateCard($savedCard, $this->merchant);

        $iin = $this->app['repo']->iin->find($card['iin']);

        $this->payment->card()->associate($card);

        return array_merge(
            $card->toArray(),
            [
                'number'       => $cardNumber,
                'cvv'          => $cvv,
                'message_type' => $iin['message_type'],
            ]);
    }

    protected function verifyBankEnabled(Payment\Entity $payment)
    {
        $merchant = $payment->merchant;

        $merchantMethods = (new Methods\Core)->getMethods($merchant);

        if (($merchantMethods === null) or
            ($merchantMethods->isNetbankingEnabled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_NETBANKING_NOT_ENABLED_FOR_MERCHANT
            );
        }

        $merchantBanks = ($merchantMethods === null) ? [] : $merchantMethods->getSupportedBanks();

        $paymentBank = $payment->getBank();

        if (in_array($paymentBank, $merchantBanks, true) === false)
        {
            $customProperties = [
                'merchant_banks' => $merchantBanks,
                'payment_bank' => $paymentBank,
            ];

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_NOT_ENABLED_FOR_MERCHANT,
                null,
                [
                    'custom_properties' => $customProperties,
                    'payment_id'        => $payment->getId(),
                ]);
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

    protected function verifyEmandateEnabled()
    {
        $merchantMethods = $this->methods;

        if (($merchantMethods === null) or
            ($merchantMethods->isEmandateEnabled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_EMANDATE_NOT_ENABLED_FOR_MERCHANT);
        }
    }

    protected function verifyNachEnabled()
    {
        $merchantMethods = $this->methods;

        if (($merchantMethods === null) or
            ($merchantMethods->isNachEnabled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_NACH_NOT_ENABLED_FOR_MERCHANT);
        }
    }

    protected function verifyCardlessEmiEnabled()
    {
        $merchantMethods = $this->methods;

        if (($merchantMethods === null) or
            ($merchantMethods->isCardlessEmiEnabled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARDLESS_EMI_NOT_ENABLED_FOR_MERCHANT);
        }
    }

    protected function verifyPayLaterEnabled()
    {
        /**
         * @var $merchantMethods \RZP\Models\Merchant\Methods\Entity
         */
        $merchantMethods = $this->methods;

        if (($merchantMethods === null) or
            ($merchantMethods->isPayLaterEnabled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARDLESS_EMI_NOT_ENABLED_FOR_MERCHANT);
        }
    }

    protected function verifyCardEnabledInLive(Payment\Entity $payment)
    {
        // Only check enabled or not on live mode
        if ($this->mode === Mode::TEST)
        {
            return;
        }

        $merchantMethods = $this->methods;

        if ($merchantMethods->isCardEnabled() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_NOT_ENABLED_FOR_MERCHANT);
        }

        if ($payment->isGooglePayCard() === true)
        {
            return;
        }

        $card = $payment->card;

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

        $this->checkAndValidateIfSubTypeDisabled($merchantMethods, $card);

        $this->checkAndValidateIfCardNetworkDisabled($merchantMethods, $card);
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

    protected function verifyRecurringEnabledForMerchant(Merchant\Entity $merchant)
    {
        if ($merchant->isRecurringEnabled() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_RECURRING_PAYMENTS_NOT_SUPPORTED);
        }
    }

    protected function validateInternationalRecurringPaymentsAllowed(Payment\Entity $payment)
    {
        if (($payment->isRecurring() === true) and
            ($payment->isInternational() === true))
        {
            //
            //  If feature is enabled, recurring international
            //  payments are to be disabled.
            //
            if ($payment->merchant->isFeatureEnabled(Feature\Constants::BLOCK_INTERNATIONAL_RECURRING) === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_INTERNATIONAL_RECURRING_NOT_ALLOWED_FOR_MERCHANT,
                    [
                        'merchant_id' => $payment->merchant->getId(),
                    ]);
            }
        }
    }

    protected function validateCardAndCvv(Payment\Entity $payment, array $input)
    {
        // if not recurring, validate that card data and cvv in card data is present
        if (($payment->isRecurring() === false) and
            ($payment->getTokenId() !== null) and
            ($payment->getMethod() === Method::CARD))
        {
            $payment->getValidator()->validateCardAndCvv($input);
        }
    }

    protected function validateOrderMethods(Payment\Entity $payment)
    {
        // list of irctc merchant_ids
        $irctcMerchantIds = ['8byazTDARv4Io0', '90xVmQJTCEJ6GH', '9m4CChGex4ENkR', 'B3AFCVPnT82ehc', 'AEPXwjSlJJhfUl',
            'AEsxERLbWiBuUG', '8YPFnW5UOM91H7', '8ST00QgEPT14cE'];

        array_push($irctcMerchantIds, '10000000000000'); //for testing

        $merchantId = $payment->getMerchantId();

        if (in_array($merchantId, $irctcMerchantIds) === false)
        {
            return;
        }

        $order = $payment->order;

        if(isset($order) === false)
        {
            return;
        }

        if (isset($order->getNotes()['Pay_Mode']) === false)
        {
            return;
        }

        $paymentMode = $order->getNotes()['Pay_Mode'];

        $method = $payment->getMethod();

        if ((($paymentMode === 'UPI') and ($method !== Payment\Method::UPI)) or
            ($paymentMode === 'NOUPI') and (($method == Payment\Method::UPI)))
        {
            $this->trace->info(
                TraceCode::PAYMENT_ORDER_METHOD_VALIDATION_FAILED,
                [
                    'paymentMode' => $paymentMode,
                    'method'      => $method
                ]);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_METHOD_NOT_ALLOWED_FOR_ORDER,
                null,
                [
                    'paymentMode' => $paymentMode,
                    'method'      => $method
                ]);
        }
    }

    protected function validateUpiPspIsAllowed(Payment\Entity $payment)
    {
        $disallowedPspJson = $this->cache->get(Upi\Core::EXCLUDED_PSPS, '[]');

        $disallowedPsps = json_decode($disallowedPspJson, true);

        $payment->getValidator()->validateUpiVpaPsp(
            $payment->getVpa(), $disallowedPsps);
    }

    protected function validateIfIntentEnabled(Payment\Entity $payment)
    {
        if ($payment->merchant->isFeatureEnabled(Feature\Constants::DISABLE_UPI_INTENT) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'UPI intent is not enabled for the merchant');
        }

        if (isset($payment['vpa']) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The vpa field is not required and not shouldn\'t be sent.');
        }
    }

    protected function validateIfOmnipayEnabled(Payment\Entity $payment)
    {
        $upiProvider = $payment->getMetadata(Payment\Entity::UPI_PROVIDER);

        $feature = Payment\UpiProvider::$upiProviderToFeatureMap[$upiProvider];

        if ($payment->merchant->isFeatureEnabled($feature) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $upiProvider . ' omnichannel is not enabled for the merchant');
        }
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

    protected function checkAndValidateIfCardNetworkDisabled($methods, $card)
    {
        $network = $card->getNetworkCode();

        if ($methods->isCardNetworkEnabled($network) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED,
                null,
                [
                    'network' => $network,
                    'iin'     => $card->getIin()
                ]);
        }
    }

    protected function checkAndValidateIfSubTypeDisabled($methods, $card)
    {
        $subtype = $card->getSubType();

        if (empty($subtype) === true)
        {
            return;
        }

        if ($methods->isSubTypeEnabled($subtype) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_SUBTYPE_NOT_SUPPORTED,
                null,
                [
                    'sub_type' => $subtype,
                    'iin'      => $card->getIin()
                ]);
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

            $payment->setNonVerifiable();

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

            $isProduction = $this->app->environment(Environment::PRODUCTION);

            $variant  = $this->app->razorx->getTreatment($payment->getId(), 'api_hitting_doppler_service', $this->mode);

            if (($isProduction === true) and
                (strtolower($variant) === 'on'))
            {
                //TODO: Remove this later
                try
                {
                    $this->app->doppler->sendFeedback($payment, Doppler::PAYMENT_AUTHORIZATION_SUCCESS_EVENT);
                }
                catch (\Throwable $e)
                {
                    $this->trace->info(
                        TraceCode::DOPPLER_SERVICE_SNS_PUBLISH_FAILED,
                        [
                            'payment'             => $payment->toArray(),
                            'error'               => $e->getMessage()
                        ]
                    );
                }
            }

            $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_AUTHORIZATION_PROCESSED, $payment);

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
                $payment->edit($data['acquirer'], 'edit_acquirer');
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
        //
        // No gateway for bank transfer or Bharat Qr or UPI Transfer, everything is internal
        //
        if (($payment->isBankTransfer() === true) or
            ($payment->isBharatQr() === true) or
            ($payment->isUpiTransfer() === true))
        {
            return false;
        }

        $gateway = $payment->getGateway();

        $cardId = $payment->getCardId();
        // We handle dual and null terminal mode as the default case
        // In the default case, we check if the card network supports
        // purchase or auth+capture. Example. FSS uses Auth and capture
        // for MC and VISA and purchases for RUPAY, DICL, and MAESTRO
        $networkCode = null;

        // If payment method is wallet or net banking.
        if ($cardId !== null)
        {
            $networkCode = $payment->card->getNetworkCode();
        }

        $terminalMode = $payment->terminal->getMode();

        if ($terminalMode === Terminal\Mode::AUTH_CAPTURE)
        {
            return true;
        }
        else if ($terminalMode === Terminal\Mode::PURCHASE)
        {
            return (Payment\Gateway::supportsPurchase($gateway, $networkCode) === false);
        }

        // Additional check for ICICI debit cards on First data terminal

        if (($cardId !== null) and
            ($gateway === Payment\Gateway::FIRST_DATA))
        {
            $card = $payment->card;

            $issuer = $card->getIssuer();

            $type = $card->getType();

            if (($issuer === Card\Issuer::ICIC) and
                ($type === Card\Type::DEBIT))
            {
                return false;
            }
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

    protected function getOtpSubmitUrlPrivate(): string
    {
        $params = [
            'id' => $this->payment->getPublicId()
        ];

        $otpSubmitUrl = $this->route->getUrl('payment_otp_submit_private', $params);

        return $otpSubmitUrl;
    }

    protected function getPaymentRedirectTo3dsUrl(): string
    {
        $params = [
            'id' => $this->payment->getPublicId()
        ];

        $otpFallbackUrl = $this->route->getUrlWithPublicAuth('payment_redirect_3ds', $params);

        return $otpFallbackUrl;
    }

    protected function getOtpResendUrl(): string
    {
        $params = [
            'id' => $this->payment->getPublicId()
        ];

        $otpResendUrl = $this->route->getUrl('payment_otp_resend_private', $params);

        return $otpResendUrl;
    }

    protected function getOtpResendUrlPrivate(): string
    {
        $params = [
            'id' => $this->payment->getPublicId()
        ];

        $otpResendUrl = $this->route->getUrlWithPublicAuth('payment_otp_resend', $params);

        return $otpResendUrl;
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

    protected function isMagicEnabled(Payment\Entity $payment)
    {
        if ($payment->isMethodCardOrEmi() === false)
        {
            return false;
        }

        try
        {
            $cache = Cache::getFacadeRoot();

            $magicDisabledGlobally = (bool) $cache->get(ConfigKey::DISABLE_MAGIC);
        }
        catch (\Throwable $e)
        {
            $magicDisabledGlobally = true;

            $this->trace->traceException($e);
        }

        if (($magicDisabledGlobally === false) and
            ($payment->card->isMagicEnabled() === true))
        {
            return true;
        }

        return false;
    }

    protected function isPreferredRecurring(array $input)
    {
        return ((empty($input[Payment\Entity::RECURRING]) === false) and
                ($input[Payment\Entity::RECURRING] === 'preferred'));
    }

    protected function validateAndSaveInputDetailsIfRequired($payment, $input, $gatewayInput, $ret)
    {
        $type = $this->getFallbackOrRedirectType($payment, $ret);

        if ($type === null)
        {
            return;
        }

        $input['payment']['id'] = $payment->getId();

        if ($type === 'fallback')
        {
            $key = $payment->getCacheInputKey();
            $ttl = static::CARD_CACHE_TTL;
        }
        else
        {
            $key = $payment->getCacheRedirectInputKey();
            $ttl = static::REDIRECT_CACHE_TTL;
            $input['headless_error'] = $this->headlessError;
        }

        if (($payment->isMethodCardOrEmi() === true) and
            (empty($input[Payment\Entity::TOKEN]) === true))
        {
            /*
             * In Maestro card sometimes cvv will be null and
             * persistCardDetailsTemporarily will fail if we use $input
             * Card\Entity::modifyMaestro will add dummy cvv and save it in
             * gatewayInput, so we are using gateway input to persist card details
             */
            $gatewayInput['payment']['id'] = $payment->getId();

            // storing card details for fallback/redirect purpose
            $this->persistCardDetailsTemporarily($gatewayInput);

            unset($input['card']['number']);
            unset($input['card']['cvv']);

            unset($gatewayInput['card']['number']);
            unset($gatewayInput['card']['cvv']);
        }

        $input['gateway_input'] = $gatewayInput;

        $this->cache->put($key, $input, $ttl);
    }

    protected function shouldRedirect(Payment\Entity $payment)
    {
        $routeName = $this->app['request.ctx']->getRoute();

        if (($this->app['basicauth']->isPrivateAuth() === false) or
            ($this->app['api.route']->isS2SJsonRoute($routeName) === true))
        {
            return false;
        }

        if (($payment->isEmandate() === true) and
            ($payment->isRecurringTypeInitial() === true))
        {
            return true;
        }

        if ($payment->isNach() === true)
        {
            return false;
        }

        if (($payment->isMethodCardOrEmi() === false) or
            ($payment->isRecurring() === true) or
            ($payment->isPushPaymentMethod() === true))
        {
            return false;
        }

        $authType = $payment->getAuthType();

        if (($authType !== null) and
            ($authType !== Payment\AuthType::_3DS))
        {
            return false;
        }

        if ($payment->isGooglePayCard() === true)
        {
            return false;
        }

        return true;
    }

    protected function shouldRedirectV2(Payment\Entity $payment, $gatewayInput)
    {
        $routeName = $this->app['request.ctx']->getRoute();
        $this->isS2SJsonRoute = $this->app['api.route']->isS2SJsonRoute($routeName);

        /*
         * We don't use the redirect flow for the following scenarios
         * 1. Request is not an s2s route
         * 2. Route is not a s2s json response route i.e /payments/create/json
         * 3. Payment recurring type is auto
         * 4. Payment is method is banktransfer, upi
         * 5. auth type is OTP or preferred auth contains OTP
         * 6. BharathQR payment
         * 7. Payment receiver is VPA
         */
        if (($this->app['basicauth']->isPrivateAuth() === false) or
            ($this->app['api.route']->isS2SJsonRoute($routeName) === false) or
            ($payment->isRecurringTypeAuto() === true) or
            ($payment->isBankTransfer() === true) or
            ($payment->isUpi() === true) or
            ($payment->isBharatQr() === true) or
            ($payment->isUpiTransfer() === true) or
            ($payment->isNach() === true))
        {
            return false;
        }

        // check only for headless need to figure out for IVR and Axis express pay
        if (($this->canRunHeadlessOtpFlow($payment, $gatewayInput) === true) and
            ($this->headlessError === false))
        {
            return false;
        }

        return true;
    }

    // function accepts, $terminalGatewayInput to check whether we can return a redirect response or not
    // since it has auth terminal selection data and if we can return a redirect response, we are using
    // $gatewayInput to add selected terminalIds node which will be used in the redirect flow
    protected function validateAndReturnRedirectResponseIfApplicable(Payment\Entity $payment, array $terminalGatewayInput, array & $gatewayInput)
    {
        try
        {
            $merchant = $payment->merchant;

            if (($this->shouldRedirect($payment) === false) and
                ($this->shouldRedirectV2($payment, $terminalGatewayInput) === false))
            {
                return null;
            }

            if ($payment->hasTerminal() === true)
            {
                $payment->disassociateTerminal();
            }

            $payload = [
                'merchant_id' => $payment->getMerchantId(),
                'payment_id' => $payment->getPublicId(),
                'mode'  => $this->mode,
                'public_key' => $this->app['basicauth']->getPublicKey(),
                'account_id' => $this->app['basicauth']->authCreds->creds['account_id'],
                'oauth_client_id' => $this->app['basicauth']->getOAuthClientId(),
            ];

            $gatewayInput['selected_terminals_ids'] = array_pluck($this->selectedTerminals,Terminal\Entity::ID);

            // encrypt with key
            $encryptedPayload = Crypt::encrypt($payload);

            $trackId = Base\UniqueIdEntity::generateUniqueId();

            $key = Payment\Entity::getRedirectToAuthorizeTrackIdKey($trackId);

            $this->cache->put($key, $encryptedPayload, self::REDIRECT_CACHE_TTL);

            $redirectUrl = $this->route->getUrl('payment_redirect_to_authorize_get', ['id' => $trackId]);

            $data['type'] = 'first';

            $data['payment_id'] = $payment->getPublicId();

            $data['redirect'] = true;

            $data['request'] = [
                'url'      => $redirectUrl,
                'method'   => 'redirect',
                'task_id'  => $this->request->getTaskId()
            ];

            $data['version'] = 1;

            $this->repo->saveOrFail($payment);

            $payload['track_id'] = $trackId;
            $payload['request'] = $data;

            $this->trace->info(
                TraceCode::PAYMENT_CREATED_IN_REDIRECT_TO_AUTHORIZE_FLOW,
                $payload
            );

            $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_CREATE_REDIRECT_RESPONSE_SENT , $payment);

            return $data;
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::PAYMENT_REDIRECT_TO_AUTHORIZE_VALIDATION_ERROR,
                ['payment_id' => $payment->getId()]
            );

            throw $ex;
        }

        return null;
    }

    public function processRedirectToAuthorize(Payment\Entity $payment, string $trackId)
    {
        $this->setPayment($payment);

        $this->checkForMerchantCallbackUrl($payment);

        $this->trace->info(
            TraceCode::PAYMENT_REDIRECT_TO_AUTHORIZE_PAYMENT,
            [
                'payment_id' => $payment->getId(),
                'track_id'   => $trackId,
            ]
        );

        $resource = $this->getCallbackMutexResource($payment);

        $response = $this->mutex->acquireAndRelease(
            $resource,
            function() use ($payment)
            {
                // Reload in case it's processed by another thread.
                $this->repo->reload($payment);

                if ($payment->hasBeenAuthorized() === true)
                {
                    return $this->processPaymentCallbackSecondTime($payment);
                }

                // if payment is already processed and failed we will throw an error
                if ($payment->isFailed() === true)
                {
                    return $this->rethrowFailedPaymentErrorException($payment);
                }

                $diff = Carbon::now(Timezone::IST)->getTimestamp() - $payment->getCreatedAt();

                if ($diff > self::PAYMENT_REDIRECT_TO_AUTHORIZE_TIME_DURATION)
                {
                    $this->segment->trackPayment($payment, ErrorCode::BAD_REQUEST_PAYMENT_CANNOT_REDIRECT_TO_AUTHORIZE);

                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PAYMENT_CANNOT_REDIRECT_TO_AUTHORIZE);
                }

                $key = $payment->getCacheRedirectInputKey();

                $inputDetails = $this->getInputDetails($payment, $key);

                $gatewayInput = $inputDetails['gateway_input'];

                $this->setAnalyticsLog($payment);

                /*
                 * In double redirect scenario terminal will be set
                 * we will use the same terminal and set auth type as null
                 * since in first request authtype might have set to
                 * headless_otp,otp,ivr
                 */
                if ($payment->hasTerminal() === true)
                {
                    $this->trace->info(
                        TraceCode::PAYMENT_SECOND_REDIRECT_TO_AUTHORIZE_REQUEST,
                        [
                            'payment_id'   => $payment->getId(),
                            'auth_type'    => $payment->getAuthType(),
                            'terminal_id'  => $payment->getTerminalId(),
                        ]);

                    $gatewayInput['selected_terminals_ids'] = [$payment->getTerminalId()];

                    $payment->setAuthType(null);
                }

                $this->repo->saveOrFail($payment);

                if ((empty($input['headless_error']) === true) or
                    ($input['headless_error'] === false))
                {
                    $this->setPreferredAuthIfApplicable($payment);
                }

                unset($inputDetails['gatewayInput']);

                $this->runFraudChecksIfApplicable($payment);

                return $this->gatewayRelatedProcessing($payment, $inputDetails, $gatewayInput);
            },
            120,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            20,
            1000,
            2000);

        return $response;
    }

    protected function getInputDetails($payment, $key = null)
    {
        if ($key === null)
        {
            $key = $payment->getCacheRedirectInputKey();
        }

        $inputDetails = $this->cache->get($key);

        if ($inputDetails === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED
            );
        }

        if (($payment->isMethodCardOrEmi() === true) and (empty($inputDetails[Payment\Entity::TOKEN]) === true))
        {
            $this->setCardNumberAndCvv($inputDetails);

            if(empty($inputDetails['gateway_input']) === false)
            {

                $gatewayInput = $inputDetails['gateway_input'];

                $this->setCardNumberAndCvv($gatewayInput);

                $inputDetails['gateway_input'] = $gatewayInput;
            }
        }

        return $inputDetails;
    }

    protected function getFallbackOrRedirectType($payment, $ret)
    {
        if ((empty($ret['request']['method']) === false) and
            ($ret['request']['method'] === 'redirect'))
        {
            return 'redirect';
        }

        if (($payment->isMethodCardOrEmi() === true) and
            (empty($ret['type']) === false) and
            ($ret['type'] === 'otp'))
        {
            return 'fallback';
        }

        return null;
    }

    // returns the emi plan which belongs to merchant, in case not present it returns the plan mapped to shared merchant
    protected function getMerchantEmiPlans($iinEntity, $emiDuration, $merchant)
    {

        //fetches the emi plans for merchant as well as shared merchant
        $emiPlans = $this->repo->emi_plan->fetchRelevantMerchantEmiPlan($iinEntity, $emiDuration, $merchant);

        if ($emiPlans->count() == 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_EMI_PLAN_NOT_EXIST
            );
        }

        foreach ($emiPlans as $plan)
        {
            if ($plan->getMerchantId() === $merchant->getId())
            {
                return $plan;
            }
        }

        return $emiPlans[0];
    }

    protected function addFeeIfApplicable(Payment\Entity $payment, array & $gatewayInput)
    {
        if (in_array($payment->getGateway(), Payment\Gateway::FEE_IN_AUTHORIZE_GATEWAYS, true) === false)
        {
            return;
        }

        list($fee, $tax, $feesSplit) = $this->repo->useSlave(function () use ($payment)
        {
            return (new Pricing\Fee)->calculateMerchantFees($payment);
        });

        $gatewayInput['payment_fee'] = $fee;
    }

    protected function modifyAccountNumberForSpecificBanks($payment, array & $gatewayInput)
    {
        $accountNumber = $gatewayInput['order']['account_number'];

        // prepend required zeroes in the account number based on bank
        switch ($payment->getBank())
        {
            case IFSC::SBIN:
                $accountNumber = str_pad($accountNumber, 17, '0', STR_PAD_LEFT );
                break;

            case IFSC::KKBK:
                $accountNumber = str_pad($accountNumber, 14, '0', STR_PAD_LEFT );
                break;

            case IFSC::CBIN:
                $accountNumber = str_pad($accountNumber, 10, '0', STR_PAD_LEFT );
                break;

            default:
                break;
        }

        $gatewayInput['order']['account_number'] = $accountNumber;
    }

    public function validateAndSaveBillingAddressIfApplicable(Payment\Entity $payment, array $input)
    {
        if (isset($input[Payment\Entity::BILLING_ADDRESS]) === false)
        {
            return;
        }

        $billingAddressFromInput = $input[Payment\Entity::BILLING_ADDRESS];

        $billingAddressFromInput['type'] = Address\Type::BILLING_ADDRESS;

        if (isset($billingAddressFromInput['postal_code']) === true)
        {
            // address entity stores zip code as "zipcode"
            // in input, we get zip code as "postal_code"
            $billingAddressFromInput['zipcode'] = $billingAddressFromInput['postal_code'];

            unset($billingAddressFromInput['postal_code']);
        }

        (new Address\Core)->create($payment, $payment->getEntity(), $billingAddressFromInput);
    }

    protected function shouldCreatePaysecurePayment(Payment\Entity $payment, array $input, $currentTerminal)
    {
        return (!is_null($currentTerminal) and
            ($currentTerminal[Terminal\Entity::GATEWAY] === Gateway::HITACHI) and
            ($payment->card['network_code'] === Network::RUPAY));
    }

    /**
     * Enach through NPCI has mandated that additional information has to be displayed
     * when rendering the response page to the user.
     * emandate_details contains this additional information. In this flow
     * we open a different view based on the requirements set by NPCI after callback
     *
     * @param array $returnData
     * @param array $gatewayData
     * @param Payment\Entity $payment
     * @return array
     */
    protected function addEmandateDisplayDetailsIfApplicable($returnData, Payment\Entity $payment): array
    {
        if ($payment->getGateway() !== Payment\Gateway::ENACH_NPCI_NETBANKING)
        {
            return $returnData;
        }

        $merchant = $payment->merchant;

        $token = $payment->getGlobalOrLocalTokenEntity();

        $terminal = $payment->terminal;

        $config = $this->app['config']->get('gateway.enach_npci_netbanking');

        $payment = $payment->toArray();

        $mode = $this->mode;

        $gatewayPayment = $this->repo->enach->findByPaymentIdAndActionOrFail($payment['id'], GatewayAction::AUTHORIZE);

        $returnData['emandate_details'] = enachNpciGateway::fetchEmandateDisplayDetails(
                                                                               $payment,
                                                                               $token,
                                                                               $terminal,
                                                                               $merchant,
                                                                               $config,
                                                                               $mode,
                                                                               $gatewayPayment
                                                                              );

        return $returnData;
    }
}
