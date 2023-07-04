<?php

namespace RZP\Models\Payment\Processor;

use App;
use Mail;
use Cache;
use Crypt;
use Config;
use Route;
use Request;
use Carbon\Carbon;
use Lib\PhoneBook;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\NetbankingConfig;

use RZP\Models\Ledger\ReverseShadow\Payments\Core as ReverseShadowPaymentsCore;
use RZP\Services\Shield;
use RZP\Constants\Procurer;
use RZP\Gateway\Base\Metric as BaseMetric;
use RZP\Jobs;
use RZP\Error;
use RZP\Exception;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Payment\Processor\PayLater;
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
use RZP\Models\VirtualAccount\Receiver;
use RZP\Trace\TraceCode;
use RZP\Trace\Tracer;
use RZP\Models\Invoice;
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
use RZP\Models\CardMandate;
use RZP\Models\Transaction;
use RZP\Models\PaymentLink;
use RZP\Constants\Timezone;
use RZP\Models\PaymentsUpi;
use RZP\Constants\Environment;
use RZP\Models\Card\Network;
use RZP\Http\CheckoutView;
use RZP\Jobs\RunShieldCheck;
use RZP\Models\EntityOrigin;
use RZP\Models\Payment\Action;
use RZP\Models\Payment\Status;
use RZP\Models\Payment\Method;
use RZP\Models\Customer\Token;
use RZP\Jobs\Order\OrderUpdate;
use RZP\Models\UpiMandate\Core;
use RZP\Services\KafkaProducer;
use RZP\Models\Payment\Gateway;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Merchant\Methods;
use RZP\Models\Plan\Subscription;
use RZP\Models\Order\ProductType;
use RZP\Models\Payment\Analytics;
use RZP\Models\Payment\UpiMetadata;
use Razorpay\Trace\Logger as Trace;
use RZP\Services\CardPaymentService;
use RZP\Models\Customer\Token\Metric;
use RZP\Models\Payment\TwoFactorAuth;
use RZP\Models\Payment\RecurringType;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Customer\GatewayToken;
use RZP\Models\SubscriptionRegistration;
use RZP\Models\Payment\TerminalAnalytics;
use RZP\Models\Locale\Core as LocaleCore;
use RZP\Gateway\Mozart\GetSimpl\Constants;
use Neves\Events\TransactionalClosureEvent;
use RZP\Models\Ledger\CaptureJournalEvents;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Reward\Entity as RewardEntity;
use RZP\Models\Payment\Processor\App as AppMethod;
use RZP\Jobs\Ledger\CreateLedgerJournal as LedgerEntryJob;
use RZP\Models\Payment\Processor\Constants as PaymentConstants;
use RZP\Gateway\Enach\Npci\Netbanking\Gateway as enachNpciGateway;
use RZP\Models\Merchant\ProductInternational\ProductInternationalMapper;
use RZP\Models\Order as Order;
use RZP\Models\Payment\PaymentMeta;
use RZP\Jobs\OneCCShopifyCreateOrder;
use RZP\Jobs\SavedCardTokenisationJob;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Currency\Currency as CurrencyCurrency;
use RZP\Models\Invoice\Service as InvoiceService;
use RZP\Models\Invoice\Entity as InvoiceEntity;
use RZP\Models\Invoice\Constants as InvoiceConstants;
use RZP\Models\Invoice\Type as InvoiceType;
use RZP\Models\Payment\Processor\App as PaymentApp;

trait Authorize
{
    /**
     * There are different ways of doing payment authorization.
     */
    protected $type;

    protected $headlessError = false;

    protected $isJsonRoute = false;

    protected $authenticationChannel = PaymentConstants::DEFAULT_AUTHENTICATION_CHANNEL;

    /**
     * @param Payment\Entity $payment
     * @param array          $input
     * @param array          $gatewayInput
     *
     * @return array
     */
    private function coreAuthorize(Payment\Entity $payment, array $input, array $gatewayInput = []): array
    {
        $this->verifyMerchantIsLiveForLiveRequest();

        $meta = [
            'metadata' => [
                'trackId' => $this->app['req.context']->getTrackId(),
                'payment' => [
                    'id'           => $payment->getPublicId(),
                    'amount'       => $payment->getAmount(),
                    'currency'     => $payment->getCurrency(),
                    'method'       => $payment->getMethod(),
                    'issuer'       => $payment->getIssuer(),
                    'type'         => $payment->getTransactionType(),
                    'gateway'      => $payment->getGateway()
                ]
            ],
            'read_key' => array('trackId'),
            'write_key' => 'payment.id'
        ];

        if($payment->isUpi() === true) {
            $meta['metadata']['payment'] += [
                'vpa' => $payment->getVpa()
            ];
        }

        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_INPUT_VALIDATIONS_PROCESSED, $payment, null, $meta);

        // $gatewayInput is being passed by reference.
        // Adds callback url, payment and card info to $gatewayInput
        $this->runPaymentMethodRelatedPreProcessing($payment, $input, $gatewayInput);

        $this->modifyAmountForDiscountedOfferIfApplicable($payment, $input);

        // this needs to be done after we have card entity as we need to know if
        // cards used in payment is international
        $this->processCurrencyConversions($payment, $input);

        $deviceId = null;

        if((isset($input['_']) === true) and
            (isset($input['_']['device_id']) === true))
        {
            $deviceId = $input['_']['device_id'];
        }

        $this->setAnalyticsLog($payment, $deviceId);

        $this->runPaymentInputValidations($payment, $input);

        $this->preProcessDCCInputs($input, $payment);

        $this->preProcessDCCForRecurringAutoOnDirect($input, $payment);

        $this->preProcessWalletCurrencyWrapper($input, $payment);

        $this->preProcessAppCurrencyWrapper($input, $payment);

        $this->storeRewards($payment, $input);

        $this->pushCardMetaDataEvent($input, $payment);

        $authPaymentData = $this->gatewayRelatedProcessing($payment, $input, $gatewayInput);

        // creating invoice entity for opgsp payment requires payment entity
        // to be present which happens at this stage.
        // all validation for opgsp payment happens inside `runPaymentInputValidations`
        $this->saveOpgspImportDataIfApplicable($payment);

        return $authPaymentData;
    }

    /**
     * Wrap core logic of `coreAuthorize` with tracing instrumentation
     *
     * @param Payment\Entity $payment
     * @param array          $input
     * @param array          $gatewayInput
     *
     * @return array
     */
    public function authorize(Payment\Entity $payment, array $input, array $gatewayInput = []): array
    {
        $attrs = [
            'payment_id'       =>  $payment->getPublicId(),
            'payment_method'   =>  $payment->getMethod(),
            'merchant_id'      =>  $payment->getMerchantId(),
            'task_id'          =>  $this->request->getTaskId()
        ];

        $response = Tracer::inSpan(['name' => 'payment.authorize', 'attributes' => $attrs],
            function() use ($payment, $input, $gatewayInput){
                return $this->coreAuthorize($payment, $input, $gatewayInput);
            });

        if($payment->isPos() === true)
        {
            $this->preProcessPaymentMeta($input, $payment);
        }

        return $response;
    }

    /**
     *  Called from :
     *  1. authorize - regular flow
     *  2. processRedirectToAuthorize - s2s redirect flow
     *  3. updateAndRedirectToAuthorize ->processRedirectToAuthorize - dcc redirect flow
     */
    public function gatewayRelatedProcessing(Payment\Entity $payment, array $input, array $gatewayInput = []): array
    {
        $ret = $this->hitGatewayIfRequired($payment, $input, $gatewayInput);

        $this->validateAndSaveInputDetailsIfRequired($payment, $input, $gatewayInput, $ret);

        $data = [];

        // For those payments which does auth in a single step, we need to store the acquirer data
        // If `request` is set from gateway response, we should not
        if (isset($ret['request']) === false)
        {
            if (isset($ret['acquirer']) === true)
            {
                $data['acquirer'] = $ret['acquirer'];
                unset($ret['acquirer']);

                // Emandate auto recurring in case of async gateways
                $data['additional_data'] = $ret['additional_data'] ?? null;
                unset($ret['additional_data']);

                // UPI Auto recurring will also send UPI block along with acquirer
                $data['upi'] = $ret['upi'] ?? null;
                unset($ret['upi']);
            }

            if (isset($ret['avs_result']) === true)
            {
                $data['avs_result'] = $ret['avs_result'];
                unset($ret['avs_result']);
            }

            if(isset($ret['is_3DS_valid'])=== true) {
                $data['is_3DS_valid'] = $ret['is_3DS_valid'];
                unset($ret['is_3DS_valid']);
            }

            // To set ret to null instead of keeping it as an empty array
            if (empty($ret) === true) {
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
        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_TERMINAL_SELECTION_INITIATED, $payment);

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
            else if (!(empty($this->selectedTerminals) === false and $payment['method'] === Payment\Method::CARD and $gatewayInput['card']['tokenised'] === true))
            {
                $chargeAccountMerchant = $gatewayInput[Payment\Entity::CHARGE_ACCOUNT_MERCHANT] ?? null;

                $this->selectedTerminals = (new TerminalProcessor)->getTerminalsForPayment($payment, $chargeAccountMerchant, null, $this->authenticationChannel);
            }

            $this->trace->info(
                TraceCode::SELECTED_TERMINAL_IDS,
                [
                    'selected_terminals_ids'  => array_pluck($this->selectedTerminals, Terminal\Entity::ID),
                ]);

            $this->app['diag']->trackPaymentEventV2(
                EventCode::PAYMENT_TERMINAL_SELECTION_PROCESSED,
                $payment,
                null,
                [
                    'metadata' => [
                        'payment' => [
                            'id'             => $payment->getPublicId(),
                            'terminal_count' => count($this->selectedTerminals)
                        ]
                    ],
                    'read_key'  => array('payment.id'),
                    'write_key' => 'payment.id'
                ],
                [
                    'terminal_count' => count($this->selectedTerminals)
                ]);
        }
        catch (\Throwable $ex)
        {
            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_TERMINAL_SELECTION_PROCESSED, $payment, $ex);

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

        (new TerminalProcessor)->setAuthenticationGateway($payment, $gatewayInput, $this->authenticationChannel);
    }

    protected function hitGatewayIfRequired(Payment\Entity $payment, array $input, array & $gatewayInput)
    {
        //
        // The instance variable selectedTerminals need to be set
        // even if the gateway doesn't need to be hit. This is needed
        // for s2s recurring payments, so that terminal can be set later
        // using this instance variable.
        //
        if ($this->payment->isCod() === true)
        {
            return;
        }
        // set authentication channel based on request body
        if(isset($input['authentication']) and isset($input['authentication'][PaymentConstants::AUTHENTICATION_CHANNEL]))
        {
            $this->authenticationChannel = $input['authentication'][PaymentConstants::AUTHENTICATION_CHANNEL];
        }

        $this->set3ds2AuthenticationParams($input, $gatewayInput, $payment);
        $this->setSelectedTerminals($payment, $gatewayInput);
        $this->performFraudCheckRaasInternational($payment, $input);
        $this->setSelectedTerminalsForApplicationMethodsIfApplicable($payment);

        //This try-catch block is temporary and will be removed by Optimizer team in 2-3 weeks
        try
        {

            $paymentEvent = clone $payment;

            //cloning payment entity and associating the terminal for terminal downtime detection on pg-availability
            if (is_null($paymentEvent->getTerminalId()) === true)
            {
                $currentTerminal = $this->selectedTerminals[0];

                $paymentEvent->associateTerminal($currentTerminal);
            }

            // we are doing this after terminal selection since we might reject payemnt if there are no terminals found
            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CREATION_PROCESSED, $paymentEvent);

        }
        catch (\Throwable $ex)
        {
            $this->trace->error(TraceCode::PAYMENT_STATUS_EVENT_FAILURE,
                [
                    'message' => $ex->getMessage(),
                    'stack_trace' => $ex->getTrace(),
                    'payment_event' => $paymentEvent,
                    'payment' => $payment,
                ]
            );
        }
        unset($paymentEvent);

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

            $isPushedToKafka = $this->pushPaymentToKafkaForVerify($this->payment);

            $payment->setIsPushedToKafka($isPushedToKafka);

            $this->repo->saveOrFail($payment);

            $this->eventPaymentCreated();

            $this->validateAndSaveBillingAddressIfApplicable($payment, $input);

            $this->validateAndSaveTokenBillingAddressIfApplicable($payment, $input);

            if ($payment->isCardMandateCreateApplicable() === true)
            {
                $this->processCardRecurringMandateInitialPaymentCreated($payment);

                $token = $payment->localToken;

                $cardMandateHub = (new CardMandate\MandateHubs\MandateHubSelector)->GetMandateHubForCardMandate($token->cardMandate);

                $redirectResponse = $cardMandateHub->getRedirectResponseIfApplicable($token->cardMandate, $payment);

                if ($redirectResponse !== null)
                {
                    return $redirectResponse;
                }
            }
            else {

                return null;
            }
        }

        if ($this->canAuthorizeViaCps($payment) === true)
        {
            $request =  $this->authorizeViaCps($payment, $input, $gatewayInput);

        }
        else
        {
            $request = $this->authorizeAcrossTerminals($payment, $input, $gatewayInput);

            $request = $this->callAuthenticationGatewayBasedOnApplicationIfApplicable($payment, $input, $request, $gatewayInput);
        }

        if (($request !== null) and
            (empty($request['redirect']) === false))
        {
            return $request;
        }

        // edge case in s2s json flow. Merchant requested for OTP but we couldn't process it via healdess and tried the payment
        // on 3ds. We return respawn corpoto instead of redirect URL.
        //
        if ((empty($this->isJsonRoute) === false) and
            ($this->isJsonRoute === true) and
            ($payment->isMethodCardOrEmi() === true) and
            ($payment->getAuthType() === Payment\AuthType::_3DS) and
            ($payment->getAuthenticationGateway() !== Gateway::VISA_SAFE_CLICK))
        {
            return $this->validateAndReturnRedirectResponseIfApplicable($payment, []);
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
            // In case request contains avs_result also,  we do not need corpoto here
            if (isset($request['acquirer']) === true || isset($request['avs_result']) === true)
            {
                return $request;
            }
            else
            {
                // For UPI recurring Gateway will send mandate and metadata information along with the just the
                // data block, This method will use and remove all this extra information and leave the data.
                $this->updateRecurringRequestForUpiIfApplicable($payment, $request);

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

    protected function set3ds2AuthenticationParams(array $input, array & $gatewayInput, Payment\Entity $payment)
    {
        if(!($payment->isCard() === true)){
            return;
        }
        if((isset($input['browser']) === true)){
            $gatewayInput['browser'] = $input['browser'];
        }

        if((isset($input['authentication']['authentication_channel']) === true)){
            $gatewayInput['authentication']['authentication_channel'] = $input['authentication']['authentication_channel'];
        }else{
            $gatewayInput['authentication']['authentication_channel'] = "browser";
        }

        if((isset($input['auth_step']) === true)){
            $gatewayInput['authentication']['auth_step'] = $input['auth_step'];
        }

        if((isset($input['ip']) === true)){
            $gatewayInput['ip'] = $input['ip'];
        }

        // URL for the second authenticate call of 3ds 2.0 payment
        $redirectUrl = $this->route->getUrl('payment_redirect_to_authenticate_get', ['id' => $payment->getId()]);
        $gatewayInput['notificationUrl'] = $redirectUrl;
    }

    protected function authorizeAcrossTerminals(Payment\Entity $payment, array $input, array & $gatewayInput)
    {
        $totalTerminals = count($this->selectedTerminals);

        $maxRetryAttempts = min($totalTerminals, self::MAX_RETRY_ATTEMPTS);

        $retryAttempts = 0;

        $request = [];

        $retry = false;

        $isHdfcSurchargeModified = false;

        $hdfcSurchargeOriginalValues = [];

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
            if($isHdfcSurchargeModified === true)
            {
                $isHdfcSurchargeModified = false;

                $payment = $this->revertModifyPaymentForHdfcVasSurchargeNonDS($payment, $hdfcSurchargeOriginalValues);
            }

            $currentTerminal = $this->selectedTerminals[$retryAttempts];

            // Using Hitachi terminals for paysecure until we create new ones for paysecure.
            if ($this->shouldCreatePaysecurePayment($payment, $input, $currentTerminal))
            {
                $currentTerminal[Terminal\Entity::GATEWAY] = Gateway::PAYSECURE;
            }
            // Uncomment this to test with Sharp or any other terminal locally.
            // $currentTerminal = Terminal\Entity::findOrFail('2czHdeTG32rFhB');
            $payment->associateTerminal($currentTerminal);

            if($payment->isHdfcNonDSSurcharge() === true)
            {
                list($payment, $hdfcSurchargeOriginalValues) = $this->modifyPaymentForHdfcVasSurchargeNonDS($payment);

                $isHdfcSurchargeModified = true;
            }

            if ($payment->getIsPushedToKafka() === null)
            {
                $isPushedToKafka = $this->pushPaymentToKafkaForVerify($this->payment);

                $payment->setIsPushedToKafka($isPushedToKafka);
            }

            // assigning $gatewayInput to $terminalGatewayInput because we need to
            // persist gateway input in redirection flow,in
            // runPostGatewaySelectionPreProcessing() other attributes and
            // payment analytics, gateway_tokens entities gets appended inside $terminalGatewayInput.
            $terminalGatewayInput = $gatewayInput;

            if ((($payment->isUpiRecurring() === true) and ($payment->isRecurringTypeInitial() === true)) and ((isset($input['_']['upiqr']) === true) and ($input['_']['upiqr'])))
            {
                $this->trace->info(
                    TraceCode::UPI_AUTOPAY_RECEIVER_TYPE_QRCODE_SAVE_LOG,
                    [
                        'isUpiRecurring'  => $payment->isUpiRecurring(),
                        'isUpiQr'         => $input['_']['upiqr']
                    ]);
                $terminalGatewayInput['upi_autopay_payment_type'] = Payment\UpiMetadata\Mode::UPI_QR;
            }

            $this->runPostGatewaySelectionPreProcessing($payment, $terminalGatewayInput);

            $return = $this->returnSpawnCoprotoIfContactRequired($payment, $input);

            if ($return !== null)
            {
                return $return;
            }

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

            $this->app['diag']->trackPaymentEventV2(
                EventCode::PAYMENT_AUTHENTICATION_INITIATED,
                $payment,
                null,
                [
                    'metadata' => [
                        'payment' => [
                            'id'                      => $payment->getPublicId(),
                            'gateway'                 => $payment->getGateway(),
                            'authentication_gateway'  => $payment->getAuthenticationGateway(),
                        ]
                    ],
                    'read_key'  => array('payment.id'),
                    'write_key' => 'payment.id'
                ],
                [
                    'attempt'     => $retryAttempts,
                    'terminal_id' => $payment->getTerminalId(),
                    'gateway'     => $payment->getGateway(),
                    'shared'      => $currentTerminal->isShared(),
                    'auth_type'   => $terminalGatewayInput['auth_type'] ?? null,
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

                if ((isset($request['status']) == true) and ($request['status'] == 'authenticated'))
                {
                    $this->updatePaymentAuthenticated($request);

                    return null;
                }

                $this->app['diag']->trackPaymentEventV2(
                    EventCode::PAYMENT_AUTHENTICATION_2FA_URL_SENT,
                    $payment,
                    null,
                    [
                        'metadata' => [
                            'payment' => [
                                'id' => $payment->getPublicId(),
                                '2FA_url' => $request['url'] ?? ''
                            ]
                        ],
                        'read_key' => array('payment.id'),
                        'write_key' => 'payment.id'
                    ],
                    [
                        'url' => $request['url'] ?? ''
                    ]);

                $retry = false;

                if (($this->headlessError === false) and
                    ($this->canRunHeadlessOtpFlow($payment, $terminalGatewayInput) === true) and
                    ($payment->getCpsRoute() !== Payment\Entity::CARD_PAYMENT_SERVICE))
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

                $error = $e->getError();

                $errorCode = $error->getPublicErrorCode();

                $internalErrorCode = $error->getInternalErrorCode();

                $error->setDetailedError($internalErrorCode, $payment->getMethod());

                $step = $error->getStep();

                $source = $error->getSource();

                $reason = $error->getReason();

                $internalErrorDetails = [
                    Error\Error::STEP                   => $step,
                    Error\Error::REASON                 => $reason,
                    Error\Error::SOURCE                 => $source,
                    Error\Error::INTERNAL_ERROR_CODE    => $internalErrorCode,
                ];

                try
                {
                    $this->app->doppler->sendFeedback($payment, Doppler::PAYMENT_AUTHORIZATION_FAILURE_EVENT,
                        $errorCode, $internalErrorDetails, $retryAttempts);
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

                // An error occurred on gateway due to user or gateway.
                // We need to record this and mark payment as failed.
                //
                $terminalData['exception'] = $e;

                $retryAttempts++;

                /* The below condition check is to handle softRetry for certain gateways.
                The changes will be removed once international is onboarded in the Rearch Flow as it would be handled by the method microservice.
                */
                if((in_array($payment->getGateway(), Payment\Gateway::$safeRetryGateways) === true) and
                   (in_array($internalErrorCode, Exception\GatewayErrorException::$safeRetryErrorCodes) === true))
                {
                    $e->markSafeRetryTrue();
                }

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

                // Setting this flag false to stop multiple feedback sent to doppler
                $this->sendDopplerFeedback = false;

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

    /**
     * @param $gatewayResponse
     * @throws Exception\BadRequestException
     */
    protected function validateAvsResponseAndRemoveBillingAddressIfRequired(Payment\Entity $payment, $gatewayResponse): void
    {
        // skipping avs check on 3ds enabled card
        // only when feature flag `mandatory_avs_check` is not enabled
        // and experiment is active
        // todo: remove the experiment condition once feature is stable
        $isAVSSupported = $payment->isAVSSupportedForPayment();

        if( ($isAVSSupported === true) and
            ($this->skipAVSon3DSExperiment($payment) === true) and
            ($this->merchant->IsAvsCheckMandatoryEnabled() === false) and
            (isset($gatewayResponse) === true) and
            (isset($gatewayResponse['is_3DS_valid']) === true) and
            (boolval($gatewayResponse['is_3DS_valid']) === true)) {
            $this->trace->info(TraceCode::SKIP_AVS_FOR_3DS, [
                'paymentId'     => $payment->getId(),
                'avs_result'    => isset($gatewayResponse['avs_result']) === true ? $gatewayResponse['avs_result'] : '' ,
                'is_3DS_valid'  => $gatewayResponse['is_3DS_valid']
            ]);
            return;
        }
        if ($isAVSSupported === true) {
            $failureAvsResponses = ["A", "N"];

            if (isset($gatewayResponse) === false ||
                isset($gatewayResponse['avs_result']) === false ||
                in_array($gatewayResponse['avs_result'], $failureAvsResponses) === false ||
                $payment->isRecurring() === true) {
                return;
            }

            $this->deleteBillingAddressIfExists($payment);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED_BY_AVS,
                null,
                [
                    'method' => Method::CARD
                ]);
        }
    }
    protected function separateMethodSpecificTerminalsForGooglePay()
    {
        $selectedTerminals = $this->selectedTerminals;
        $googlePayPaymentMethods = $this->payment->getGooglePayMethods();
        $terminals = [];

        foreach ($googlePayPaymentMethods as $method)
        {
            $terminals[$method] = [];

            foreach ($selectedTerminals as $currentTerminal)
            {
                if ($currentTerminal[$method] === true)
                {
                    array_push($terminals[$method], $currentTerminal);
                }
            }

            // unset Gpay method in case no terminal found for that method
            if (empty($terminals[$method]))
            {
                $this->payment->unsetGooglePayMethod($method);
            }
        }
        return $terminals;
    }

    /**
     * List of networks supported on Gpay
     * Fetches the result by intersection of
     * networks supported on a list of gateways with
     * merchant methods enabled
     * @param $terminals
     * @return array
     */
    protected function fetchCardNetworksSupportedForGooglePay($terminals)
    {
        $gatewaySupportedCardNetworks = $this->fetchGatewaySupportedCardNetworks($terminals);

        $merchantSupportedCardNetworks = $this->fetchMerchantSupportedCardNetworks();

        $googlePaySupportedCardNetworks = array_intersect($gatewaySupportedCardNetworks, $merchantSupportedCardNetworks);

        $this->trace->info(
            TraceCode::GOOGLE_PAY_SUPPORTED_CARD_NETWORKS,
            [
                'gateway_supported_card_networks'       => $gatewaySupportedCardNetworks,
                'merchant_supported_card_networks'      => $merchantSupportedCardNetworks,
                'google_pay_card_networks'              => $googlePaySupportedCardNetworks,
            ]);

        return $googlePaySupportedCardNetworks;
    }


    /** Fetches a unique list of networks supported on a list of gateways
     *
     * @param $terminals
     * @return array
     */
    protected function fetchGatewaySupportedCardNetworks($terminals)
    {
        $cardNetworksSupported = [];

        foreach ($terminals as $terminal)
        {
            $gateway = $terminal[Terminal\Entity::GATEWAY];
            if (array_key_exists($gateway, Payment\Gateway::$cardNetworkMap) === true)
            {
                $gatewayNetworksSupported = Payment\Gateway::$cardNetworkMap[$gateway];
                $cardNetworksSupported = array_unique(array_merge($gatewayNetworksSupported, $cardNetworksSupported));
            }
        }

        return $cardNetworksSupported;
    }

    /**
     * Fetches the list of card networks enabled on the merchant methods
     * @return array
     */
    protected function fetchMerchantSupportedCardNetworks()
    {
        $merchantMethods = $this->payment->merchant->methods;
        $merchantCardNetworksSupported = $merchantMethods->getCardNetworks();
        $merchantCardNwsEnabled = [];

        foreach ($merchantCardNetworksSupported as $key => $value)
        {
            if($value === 1)
            {
                array_push($merchantCardNwsEnabled, $key);
            }
        }

        return $merchantCardNwsEnabled;
    }

    protected function returnSpawnCoprotoIfContactRequired($payment, $input)
    {
        $coproto = null;

        $gateway = $payment->getGateway();

        if ((in_array($gateway, Payment\Gateway::$contactMandatoryGateways) === true) and
            ($payment->merchant->isPhoneOptional() === true) and
            ($payment->getContact() === Payment\Entity::DUMMY_PHONE))
        {
            $coproto = [
                'type'    => 'respawn',
                'request' => [
                    'url'     => $this->route->getUrlWithPublicAuthInQueryParam('payment_create'),
                    'method'  => 'POST',
                    'content' => array_assoc_flatten($input, '%s[%s]'),
                ],
                'method' => 'emi',
                'version' => '1',
                'provider' => 'hdfc',
            ];

            $coproto['missing'][] = 'contact';

            unset($coproto['request']['content']['contact']);
        }

        return $coproto;
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
        else if (($payment->getCpsRoute() === Payment\Entity::CARD_PAYMENT_SERVICE) or
            ($payment->getCpsRoute() === Payment\Entity::NB_PLUS_SERVICE))
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

        $this->addBackupMethodForRetry($this->payment, $this->merchant, $e);

        throw $e;
    }

    public function updatePaymentAuthenticationFailed(Exception\BaseException $e)
    {
        $this->updatePaymentFailed($e, TraceCode::PAYMENT_AUTH_FAILURE);

        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_AUTHENTICATION_PROCESSED, $this->payment, $e);
    }

    public function updatePaymentAuthFailed(Exception\BaseException $e)
    {
        $this->updatePaymentFailed($e, TraceCode::PAYMENT_AUTH_FAILURE);

        $event = $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_AUTHORIZATION_PROCESSED, $this->payment, $e);

        $this->app['shield.service']->enqueueShieldEvent($event);
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

        $data = ['razorpay_payment_id' => $payment->getPublicId()];

        if (($payment->hasOrder() === true) and
            ($this->app['basicauth']->isProxyOrPrivilegeAuth() === false) and
            ($this->app->runningInQueue() === false))
        {
            $this->fillReturnDataWithOrder($payment, $data);
        }

        return $data;
    }

    protected function processCardRecurringMandateInitialPaymentCreated(Payment\Entity $payment)
    {
        try
        {
            $cardMandate = (new CardMandate\Core)->create($payment);
        }
        catch (\Exception $e)
        {
            $this->failMandateCreationFailedCardInitialRecurringPayment($payment, $e);

            throw $e;
        }

        $token = $payment->localToken;

        $token->cardMandate()->associate($cardMandate);

        $token->saveOrFail();

    }

    protected function processCardRecurringMandateAutoPaymentCreated(Payment\Entity $payment)
    {
        if ($payment->isCardAutoRecurring() === true)
        {
            (new CardMandate\Core)->createPreDebitNotification($payment);

            $data = ['razorpay_payment_id' => $payment->getPublicId()];

            if (($payment->hasOrder() === true) and
                ($this->app['basicauth']->isProxyOrPrivilegeAuth() === false) and
                ($this->app->runningInQueue() === false))
            {
                $this->fillReturnDataWithOrder($payment, $data);
            }

            return $data;
        }

        throw new Exception\LogicException('Should not be called for any payment other than Card Auto Recurring');
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

        if ($this->shouldSkipAuthorizeOnRecurringForEmandate($payment, $data) === true)
        {
            return $this->processRecurringCreatedForEmandateAsyncGateway($payment, $data);
        }

        if ($payment->isNach() === true)
        {
            return $this->processNachPaymentCreated($payment);
        }

        if ($payment->isCoD() === true)
        {
            return $this->processPaymentPendingForCoD($payment);
        }

        if($payment->isInAppUPI() === true)
        {
            return $this->processInAppPaymentCreated($payment);
        }

        if ($this->shouldSkipAuthorizeOnRecurringForUpi($payment, $data) === true)
        {
            return $this->processRecurringCreatedForUpi($payment, $data);
        }

        if ($payment->isCardMandateNotificationCreateApplicable() === true)
        {
            return $this->processCardRecurringMandateAutoPaymentCreated($payment);
        }

        if ($payment->getStatus() === Payment\Status::AUTHENTICATED)
        {
            return $this->postPaymentAuthenticateProcessing($payment);
        }

        return $this->processAuth($payment, $data);
    }

    protected function processPaymentPendingForCoD($payment): array
    {
        $this->updateAndNotifyPaymentPending();

        $this->updateOrderStatusPending($payment);

        return $this->postPaymentAuthorizeProcessing($payment);
    }

    protected function processInAppPaymentCreated($payment): array {
        $response = [];

        $response['razorpay_payment_id'] = $payment->getPublicId();

        $next = [];

        $terminal = $payment->terminal;
        array_push($next,
            [
                "action"    => "intent",
                "payee_vpa" => $terminal->getVpa(),
            ],
            [
                "action" => "poll",
                "url"    => $this->route->getUrl('payment_fetch_by_id', ['id' => $payment->getPublicId()]),
            ]);

        $response['next'] = $next;

        return $response;
    }

    protected function updateOrderStatusPending($payment)
    {
        if ($payment->isCod() === false)
        {
            return;
        }

        $order = $this->payment->order;

        $order->setStatus(Order\Status::PLACED);

        $this->repo->order->saveOrFail($order);
    }

    protected function updateAndNotifyPaymentPending(array $data = []): bool
    {
        $payment = $this->payment;

        $status = $this->payment->getStatus();

        if ($payment->getStatus() !== Status::CREATED)
        {
            return false;
        }

        $payment->setErrorNull();

        $payment->setNonVerifiable();

        $payment->setStatus(Payment\Status::PENDING);

        $payment->setSettledBy('delivery_partner');

        $this->trace->info(
            TraceCode::PAYMENT_STATUS_PENDING,
            [
                'payment_id'        => $payment->getId(),
                'old_status'        => $status,
            ]);

        $customProperties = $payment->toArrayTraceRelevant();

        $this->segment->trackPayment($payment, TraceCode::PAYMENT_PENDING_SUCCESS, $customProperties);

        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_PENDING_PROCESSED, $payment);

        $this->repo->payment->saveOrFail($payment);

        $this->eventPaymentPending();

        $this->setCoDPaymentPendingReminder($payment);

        return true;
    }

    protected function setCoDPaymentPendingReminder($payment)
    {
        $namespace = 'cod_payment_pending';

        $url = sprintf('reminders/send/%s/payment/%s/%s', $this->mode, $namespace, $payment->getId());

        $merchantId = Merchant\Account::SHARED_ACCOUNT;

        $request = [
            'namespace'     => $namespace,
            'entity_id'     => $payment->getId(),
            'entity_type'   => 'payment',
            'reminder_data' => [
                'created_at' => $payment->getCreatedAt(),
            ],
            'callback_url'  => $url,
        ];

        try
        {
            $response = $this->app['reminders']->createReminder($request, $merchantId);

            $reminderId = array_get($response, 'id');

            $this->trace->info(TraceCode::COD_PAYMENT_PENDING_REMINDER_CREATED, [
                'id'          => $payment->getId(),
                'reminder_id' => $reminderId,
            ]);
        }
        catch (\Throwable $exception) {
            $this->trace->traceException($exception);
        }
    }

    protected function getOtpPaymentCreatedResponse($request, $payment)
    {
        $languageCode = App::getLocale() !== null ? App::getLocale() : LocaleCore::setLocale($request, $payment->merchant->getId());

        if ((isset($request['type']) === true) and
            ($request['type'] === 'respawn'))
        {
            return $request;
        }

        $payment->incrementOtpCount();

        $this->repo->save($payment);

        $merchant = $payment->merchant;

        $terminal = $payment->terminal;

        // In case of cred merchant, they will be using transacting merchant's terminal
        // so we need to display transacting merchant details instead of cred
        // TODO: Need to come up with a better approach
        if (($payment->getMerchantId() != $terminal->getMerchantId()) and
            ($terminal->isShared() == false) and
            ($merchant->isFeatureEnabled(Feature\Constants::CHARGE_ACCOUNT) === true))
        {
            $merchant = $terminal->merchant;
        }


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
            'merchant'              => $merchant->getBillingLabel(),
            'merchant_id'           => $merchant->getId(),
            'theme_color'           => $merchant->getBrandColorElseDefault(),
            'nobranding'            => $merchant->isFeatureEnabled(Feature\Constants::PAYMENT_NOBRANDING),
        ];

        // paylater lazypay supports native otp flows only for s2s merchants with jsonv2 route
        // For checkout merchants, payments happens via ACS page
        if ($merchant->isFeatureEnabled(Feature\Constants::JSON_V2) === true)
        {
            if($payment->isPayLater() === true and $payment->getWallet() === Paylater::LAZYPAY)
            {
                $metaData = [
                    'issuer'     => $payment->getWallet(),
                    'gateway'    => $payment->getGateway(),
                ];

                $response['metadata'] = $metaData;

                $next = ['otp_submit'];

                if (isset($request['content']['next']) === true)
                {
                    $next = $this->getNextOtpAction($request['content']['next']);

                    unset($request['content']['next']);
                }

                $templateData = [
                    'data'          => $response,
                    'cdn'           => $this->app['config']->get('url.cdn.production'),
                    'production'    => $this->app->environment() === Environment::PRODUCTION,
                    'language_code' => $languageCode
                ];

                $templateData += (new CheckoutView())->addOrgInformationInResponse($merchant);

                $content = $this->app['view']
                    ->make('gateway.gatewayOtpPostForm')
                    ->with('data', $templateData)
                    ->render();

                $otpResend = 'otp_resend';

                $resendUrl = null;
                $resendUrlPrivate = null;

                if (in_array($otpResend, $next, true) === true)
                {
                    $resendUrl        = $this->getOtpResendUrl();
                    $resendUrlPrivate = $this->getOtpResendUrlPrivate();
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
                ];

                $response['resend_url_json']    = $this->getOtpResendUrlJson();
                $response['submit_url_private'] = $this->getOtpSubmitUrlPrivate();
                $response['resend_url_private'] = $resendUrlPrivate;
            }
        }

        // This is a hack to return direct method for IVR payments
        if ($payment->isMethodCardOrEmi() === true)
        {
            $card = $payment->card;
            $redirectUrl = null;

            if (in_array($payment->getGateway(), Payment\Gateway::$otpPostFormSubmitGateways, true) === false)
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

           $library = (new Payment\Service)->getLibraryFromPayment($payment);

           if ($library !== Payment\Analytics\Metadata::S2S)
           {
               unset($metaData['iin']);
           }

            $token = $payment->getGlobalOrLocalTokenEntity();

            if ((empty($token) === false) &&
                (empty($token->card) === false))
            {
                $metaData['last4'] = $token->card->getLast4();
            }


            if ((empty($this->isJsonRoute) === false) and
                ($this->isJsonRoute === false))
            {
                $metadata['gateway'] = $payment->getGateway();
            }

            $response['metadata'] = $metaData;

            if ($payment->getGateway() === Payment\Gateway::HDFC_DEBIT_EMI)
            {
                $emiPlan = $payment->emiPlan()->first();

                $response = array_merge(
                    $response,
                    [
                        'terms' => [
                            'tnc'      => 'https://cdn.razorpay.com/static/assets/hdfc/debitemi/tnc.json',
                            'schedule' => 'https://cdn.razorpay.com/static/assets/hdfc/debitemi/schedule.json',
                        ],
                        'mode' => 'hdfc_debit_emi',
                        'emi_duration' => $emiPlan->getDuration(),
                        'emi_rate' => ($emiPlan->getRate() / 100),
                    ]
                );
            }

            //Process gateway specific parameters
            $response = $this->buildGatewayOtpResponse($response, $payment);

            $templateData = [
               'data'          => $response,
               'cdn'           => $this->app['config']->get('url.cdn.production'),
               'production'    => $this->app->environment() === Environment::PRODUCTION,
               'language_code' => $languageCode
            ];

            $templateData += (new CheckoutView())->addOrgInformationInResponse($merchant);

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
                $resendUrlPrivate = $this->getOtpResendUrlPrivate();
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

            $response['resend_url_json']    = $this->getOtpResendUrlJson();
            $response['submit_url_private'] = $this->getOtpSubmitUrlPrivate();
            $response['resend_url_private'] = $resendUrlPrivate;

            //Process gateway specific parameters
            $response = $this->buildGatewayOtpResponse($response, $payment);
        }

        $this->segment->trackPayment($payment, TraceCode::OTP_GENERATE, $response);

        return $response;
    }

    protected function buildGatewayOtpResponse(& $response, $payment)
    {
        //BEPG - Native OTP Page
        if (($payment->getGateway() === Payment\Gateway::PAYSECURE) and
            ($payment->getAuthType() === Payment\AuthType::OTP))
        {
            //Disable Go to Bank's Page
            unset($response['redirect']);

            $response['metadata'] = array_merge(
                $response['metadata'],
                [
                    'ip' => $this->app['request']->ip(),
                    'resend_timeout' => 30  //Seconds
                ]);
        }

        return $response;
    }

    protected function getGooglePayPaymentCreatedResponse($request, $payment)
    {
        $this->repo->saveOrFail($payment);

        $id = $payment->getPublicId();

        $request['url'] = $this->route->getUrlWithPublicAuthInQueryParam('payment_get_status', ['x_entity_id' => $id]);

        $response = [
            'version'               => 1,
            'type'                  => 'application',
            'application_name'      => 'google_pay',
            'payment_id'            => $payment->getPublicId(),
            'redirect'              => $this->checkIfRedirectRoute(),
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

    public function shouldGatewayCapturePayment(Payment\Entity $payment)
    {

        if (($payment->isGatewayCaptured() === false) and
            ($payment->getGateway() === Payment\Gateway::PAYSECURE))
        {
            return true;
        }

        if(($payment->isGatewayCaptured() === false) and
            ($payment->isMethodCardOrEmi() === true  and
                $payment->merchant->isRazorpayOrgId() === true and
                $payment->card->getNetwork() === Network::getFullName(Network::MC)))
        {
            $variant = $this->app->razorx->getTreatment($this->request->getTaskId(), Merchant\RazorxTreatment::PAYMENT_GATEWAY_CAPTURE_ASYNC_MC, $this->mode);

            $this->trace->info(TraceCode::GATEWAY_CAPTURE_RAZORX_VARIANT, [
                'payment_id'     => $payment->getId(),
                'merchant_id'    => $payment->getMerchantId(),
                'razorx_variant' => $variant,
            ]);

            if (strtolower($variant) === 'on')
            {
                return true;
            }

            return false;
        }

        if(($payment->isGatewayCaptured() === false) and
            ($payment->isMethodCardOrEmi() === true and
                $payment->merchant->isRazorpayOrgId() === true))
        {
            $variant = $this->app->razorx->getTreatment($this->request->getTaskId(), Merchant\RazorxTreatment::PAYMENT_GATEWAY_CAPTURE_ASYNC_OTHER_NETWORKS, $this->mode);

            $this->trace->info(TraceCode::GATEWAY_CAPTURE_RAZORX_VARIANT, [
                'payment_id'     => $payment->getId(),
                'merchant_id'    => $payment->getMerchantId(),
                'network'        => $payment->card->getNetwork(),
                'gateway'        => $payment->getGateway(),
                'razorx_variant' => $variant,
            ]);

            if (strtolower($variant) === 'on')
            {
                return true;
            }
        }


        // for non razorpay org
        if (($payment->isGatewayCaptured() === false) and
            ($payment->getGateway() === Payment\Gateway::FULCRUM))
        {
            $variant = $this->app->razorx->getTreatment($this->request->getTaskId(), Merchant\RazorxTreatment::PAYMENT_GATEWAY_CAPTURE_ASYNC_FULCRUM ,$this->mode);

            $this->trace->info(TraceCode::GATEWAY_CAPTURE_RAZORX_VARIANT, [
                'payment_id'     => $payment->getId(),
                'merchant_id'    => $payment->getMerchantId(),
                'network'        => $payment->card->getNetwork(),
                'gateway'        => $payment->getGateway(),
                'razorx_variant' => $variant,
            ]);

            if (strtolower($variant) === 'on')
            {
                return true;
            }
        }

        return false;
    }

    public function autoCapturePaymentIfApplicable(Payment\Entity $payment)
    {

        $response = $this->shouldAutoCapture($payment);

        // For Optimizer payments, additional check
        // Ref : https://docs.google.com/document/d/1FQEGHojgb74pyBtS0r7t_qWg05XsZ_636UyYYkUNKdE/edit#
        if($payment->isOptimizerCaptureSettingsEnabled() === true)
        {
            $response = $this->shouldAutoCaptureOptimizerExternalPgPayment($payment, $response);
        }

        if (isset($response) === false)
        {
            return;
        }

        $properties = $response ?? [];

        if ($response['should_auto_capture'] === true)
        {
            $this->trace->info(
                TraceCode::AUTO_CAPTURE_TRIGGERED_REASON,
                [
                    'reason'    => $response['reason'],
                ]);

            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_ELIGIBLE_FOR_AUTO_CAPTURE, $payment, null,[], $properties);

            // If payment_capture was sent as true in order,
            // then we capture it in this step only.
            $this->autoCapturePayment($payment);
        }
        elseif ($response['should_auto_capture'] === false)
        {
            $this->trace->info(
                TraceCode::AUTO_CAPTURE_NOT_TRIGGERED_REASON,
                [
                    'reason'    => $response['reason'],
                ]);

            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_NOT_ELIGIBLE_FOR_AUTO_CAPTURE, $payment, null,[], $properties);

            if ($this->shouldGatewayCapturePayment($payment) === true and
                $payment->hasSubscription() === false)
            {
                $this->gatewayCapturePaymentViaQueue($payment);
            }
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
        $payment->reload();

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

                $this->authorizeFailedPaymentOnApi($payment, $input);
            });

        });

        return $payment->toArrayAdmin();
    }

    protected function runPaymentInputValidations(Payment\Entity $payment, array $input)
    {
        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_INPUT_VALIDATIONS2_INITIATED, $payment);

        try
        {
            // this is a temporary fix for irctc case, where a validation has to be done on supported method level,
            // which will be stored in notes during order creation
            // Slack Ref - https://razorpay.slack.com/archives/C2ZL6H76U/p1578388168053200
            $this->validateOrderMethods($payment);

            $this->validateCardAndCvv($payment, $input);

            $this->validateLast4ForS2STokenisedEmiPayments($payment, $input);

            $this->validateLibraryForInternationalApps($payment, $input);

            $this->validateAddressIfPresentWithoutRedirect($payment, $input);

            $this->validateRecurringIfApplicable($payment, $input);

            $this->validateCardAuthenticationIfApplicable($payment, $input);

            $this->validateS2SIfApplicable($payment);

            $this->validateSubscriptionInputIfPresent($payment, $input);

            $this->verifyPaymentMethodEnabled($payment);

            $this->runInternationalChecks($payment);

            $this->runFraudChecksIfApplicable($payment, $input);

            $this->validateOfferIfApplicable($payment, $input);

            $this->validateCardlessEmiIfApplicable($payment, $input);

            $this->validatePayLaterIfApplicable($payment, $input);

            $this->validateApplicationIfApplicable($payment, $input);

            $this->validateProviderIfApplicable($payment, $input);

            $this->validateOpgspImportDataIfApplicable($payment);

            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_INPUT_VALIDATIONS2_PROCESSED, $payment);
        }
        catch (\Throwable $ex)
        {
            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_INPUT_VALIDATIONS2_PROCESSED, $payment, $ex);

            throw $ex;
        }

    }

    protected function validateCardlessEmiIfApplicable(Payment\Entity $payment, $input)
    {
        if ($payment->isCardlessEmi() === false)
        {
            return;
        }

        if (Payment\Gateway::isCardlessEmiProviderAndRedirectFlowProvider($input[Payment\Entity::PROVIDER]))
        {
            return;
        }

        //todo need to remove when zestmoney is migrated to new flow
        if (($input[Payment\Entity::PROVIDER] === CardlessEmi::ZESTMONEY) and
            ($payment->getCpsRoute() === Payment\Entity::NB_PLUS_SERVICE))
        {
            return;
        }

        if (($input[Payment\Entity::PROVIDER] === CardlessEmi::ZESTMONEY) and
            ($this->mode == Mode::TEST) )
        {
            return false;
        }

        if(($input[Payment\Entity::PROVIDER] === CardlessEmi::EARLYSALARY) and
            ($payment->merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::REDIRECT_TO_EARLYSALARY)))
        {
            return;
        }

        $this->validateContactAndProviderFromToken($payment, $input);
    }

    protected function validatePayLaterIfApplicableForRedirect(Payment\Entity $payment, $input)
    {
        if((isset($input['provider']) === true) and ($input['provider'] === Payment\Gateway::GETSIMPL))
        {
            $this->validatePayLaterIfApplicable($payment,$input);
        }
    }

    protected function validatePayLaterIfApplicable(Payment\Entity $payment, $input)
    {
        if ($payment->isPayLater() === false)
        {
            return;
        }

        if ((in_array(PayLater::getProviderForBank($input['provider']), Gateway::$redirectFlowProvider) === true))
        {
            return;
        }

        $this->validateContactAndProviderFromToken($payment, $input);
    }


    protected function shouldSkipContactAndProviderValidation($input)
    {
        if (($input['provider'] === PayLater::ICICI and $input['method'] === Gateway::PAYLATER) or ($input['provider'] === Paylater::LAZYPAY and $input['method'] === Gateway::PAYLATER) or ($input['ott'] === Constants::GETSIMPLTOKEN))
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
                    if ($payment->getCurrency() !== Currency\Currency::INR)
                    {
                        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED);
                    }
                    break;

                case 'visasafeclick':
                    if ($payment->merchant->isFeatureEnabled(Feature\Constants::VISA_SAFE_CLICK) === false)
                    {
                        throw new Exception\BadRequestValidationFailureException(
                            'VisaSafeClick not enabled for merchant.');
                    }
            }
        }
    }


    protected function validateProviderIfApplicable(Payment\Entity $payment, $input)
    {
        if (isset($input['provider']) === true)
        {
            switch($input['provider'])
            {
                case 'google_pay':
                    if ($payment->isGooglePay() and $payment->merchant->isGooglePayEnabled() === false)
                    {
                        throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_GOOGLE_PAY_NOT_ENABLED);
                    }
                    break;
            }
        }
    }

    private function validateContactAndProviderFromToken(Payment\Entity $payment, $input)
    {
        $terminals = (new TerminalProcessor)->getTerminalsForPayment($payment);

        if(($terminals[0]['gateway'] === Payment\Gateway::SHARP and $input['provider'] === PayLater::GETSIMPL))
        {
            $input['ott'] = Constants::GETSIMPLTOKEN;
        }

        if ($this->shouldSkipContactAndProviderValidation($input) === true)
        {
            return;
        }

        $key = Payment\Entity::getCardlessEmiOnetimeTokenCacheKey($input['ott']);

        $cardlessEmiData = $this->app['cache']->get($key);

        //Invalidating the ott once it is verified
        if ($input['provider'] === PayLater::GETSIMPL)
        {
                $this->app['cache']->delete($key);
        }

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
                    'input_payment_id'      => $input['payment_id'] ?? null,
                    'cached_payment_id'     => $cardlessEmiData['payment_id'] ?? null,
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
                    'method'            => $payment->getMethod(),
                    'subscription_id'   => $payment->getSubscriptionId(),
                ]);
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

        if ($payment->isBharatQr() === true)
        {
            return;
        }

        if ($payment->isPos() === true)
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
        else if ($payment->isRazorpaywalletPayment() === true)
        {
            $this->verifyFeatureForMerchant($merchant, Feature\Constants::RAZORPAY_WALLET);
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
                    'payment_id' => $payment->getId(),
                    'method'     => $payment->getMethod(),

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
                        'The pin authentication type is not applicable on the given card',
                    null,
                        [
                            'payment_id' => $payment->getPublicId(),
                            'method'     => $payment->getMethod(),
                        ]);
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
                     (($payment->merchant->isIvrEnabled() === false) or
                      ($payment->card->iinRelation->supports(IIN\Flow::IVR) === false))))
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'The otp authentication type is not applicable on the given card',null,
                        [
                            'payment_id' => $payment->getPublicId(),
                            'method'     => $payment->getMethod(),
                        ]);
                }

                break;

            case Payment\AuthType::SKIP:
                // Skip auth flow is supported only for Master Card, Visa, Rupay and Amex.
                if (Payment\Gateway::isDirectDebitSupported($payment->card->getNetworkCode()) === false)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'The skip authentication type is not applicable on the given card',null,
                        [
                            'payment_id' => $payment->getPublicId(),
                            'method'     => $payment->getMethod(),
                        ]);
                }

                // For Amex moto payments, only tokenized cards to be allowed.
                if (
                    $payment->isCard()
                    && $payment->card->getNetwork() === Network::getFullName(Network::AMEX)
                )
                {
                    $token = $payment->getGlobalOrLocalTokenEntity();

                    if ($token === null)
                    {
                        throw new Exception\BadRequestValidationFailureException(
                            'Only tokenized cards are allowed for amex moto payments',
                            null,
                            [
                                'payment_id' => $payment->getPublicId(),
                                'method'     => $payment->getMethod(),
                            ]
                        );
                    }
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

        if ($token === null and $this->isAutoRecurringCardPayment($payment->getMethod(), $input) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TOKEN_ABSENT_FOR_RECURRING_PAYMENT,
                null,
                [
                    'payment_id'    => $payment->getId(),
                    'method'        => $payment->getMethod()
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

    protected function isAutoRecurringCardPayment($method, array $input)
    {
        if ($method !== Payment\Method::CARD)
        {
            return false;
        }

        if ((isset($input['recurring']) === true) and
            ($input['recurring'] === Payment\RecurringType::AUTO))
        {
            if ($this->merchant->isFeatureEnabled(Feature\Constants::RECURRING_AUTO) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_RECURRING_PAYMENTS_NOT_SUPPORTED,
                    null,
                    [
                        'payment_id'    => $this->payment->getId(),
                        'method'        => $this->payment->getMethod()
                    ]);
            }

            return true;
        }

        return false;
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

                if(($payment->isUpiIntentRecurring()) and
                   ($authType === BasicAuth\Type::DIRECT_AUTH))
                {
                    $this->verifyRecurringEnabledForMerchant($merchant);

                    $this->trace->info(
                        TraceCode::MISC_TRACE_CODE,
                        [
                            'msg'     => 'Direct Auth payment for UPI Autopay Promotional Intent',
                            'payment' => $payment->getId(),
                            'merchant'=> $merchant->getId(),
                            'type'    => $authType
                        ]);

                    break;
                }
                else
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_URL_NOT_FOUND,
                        null,
                        [
                            'payment_id' => $payment->getId(),
                            'method'     => $payment->getMethod(),
                            'auth_type' => $authType
                        ]);
                }
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

    protected function validateRecurringForCard(Payment\Entity $payment, $token)
    {
        $isInitialOrCardChange = ($payment->isRecurringTypeInitial() || $payment->isRecurringTypeCardChange());

        // replacing "$payment->card" with "$payment->localToken->card" because in the case of tokenisation
        // new card entity gets associated with the payment which has different card number
        $card = $payment->card;

        if((empty($payment->localToken) === false) and
           (empty($payment->localToken->card) === false) and
           ($payment->localToken->card->isRzpSavedCard() === false))
        {
            $card = $payment->localToken->card;
        }

        if ($card->isRecurringSupportedOnTokenIINIfApplicable($isInitialOrCardChange, $payment->hasSubscription()) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_RECURRING_NOT_SUPPORTED);
        }

        if (is_null($token) === false)
        {
            $this->validateTokenExpiredAt($token);

            $this->validateTokenRecurringStatus($token, $payment);

            $this->validateTokenStatus($token, $payment);

            if ($token->hasCardMandate() === true and $payment->isRecurringTypeAuto() === true)
            {
                (new CardMandate\Core)->validateAutoPaymentCreation($token->cardMandate, $payment);
            }
        }

        if ($payment->isRecurringTypeAuto() === true and
            $payment->cardMandateNotification !== null)
        {
            (new CardMandate\CardMandateNotification\Core)->verifyNotification($payment);
        }
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
        $directDebitFlow = false;

        if (($payment->getAmount() > 0) and
            ((Payment\Gateway::isDirectDebitEmandateBank($payment->getBank())) === true))
        {
            $directDebitFlow = true;
        }

        if ((Payment\Gateway::isZeroRupeeFlowSupported($payment->getBank()) === true) and
            ($payment->getAmount() !== 0) and
            ($directDebitFlow === false))
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

        $supportedBanks = Payment\Gateway::getAvailableEmandateBanksForAuthType($authType);

        if ($authType === "netbanking")
        {
            if (in_array($bank, Gateway::removeNetbankingEmandateRegistrationDisabledBanks($supportedBanks), true) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_BANK_RECURRING_NOT_SUPPORTED,
                    Payment\Entity::BANK,
                    [
                        'payment' => $payment->toArray(),
                    ]);
            }
        }

        if (in_array($bank, Gateway::removeEmandateRegistrationDisabledBanks($supportedBanks), true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_RECURRING_NOT_SUPPORTED,
                Payment\Entity::BANK,
                [
                    'payment' => $payment->toArray(),
                ]);
        }

        if (empty($input[Payment\Entity::TOKEN]) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_EMANDATE_TOKEN_PASSED_IN_FIRST_RECURRING,
                Payment\Entity::BANK,
                [
                    'payment' => $payment->toArray(),
                    'token'   => $input[Payment\Entity::TOKEN],
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

        $tokenRegistration = $order->getTokenRegistration();

        if (empty($input[Payment\Entity::NACH]) === false)
        {
            (new SubscriptionRegistration\Core)->uploadNachFormIfApplicableForPayment(
                $input[Payment\Entity::NACH],
                $order);

            if (($tokenRegistration !== null) and
                ($tokenRegistration->paperMandate !== null))
            {
                $tokenRegistration->paperMandate = $this->repo->reload($tokenRegistration->paperMandate);
            }
        }

        if ($tokenRegistration !== null)
        {
            $tokenRegistration->getValidator()->validatePaymentCreation();
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

    protected function validateTokenStatus(Token\Entity $token, Payment\Entity $payment)
    {
        $card = $this->repo->card->fetchForToken($token);

        if (($card->isRzpSavedCard() === false) and
            ($token->getStatus() !== 'active') and
            ($payment->isSecondRecurring() === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_NON_ACTIVATED_TOKEN_PASSED_IN_RECURRING,
                Payment\Entity::BANK,
                [
                    'token_status'  => $token->getStatus(),
                    'payment'       => $payment->toArray(),
                    'token'         => $token->toArray(),
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
                         'payment' => $payment->getId(),
                         'token'   => $token->getId(),
                    ]);
        }

        // If non-recurring token is passed in subsequent recurring payment create api then we are considering
        // initial payment flow and hence returning the html response but merchant is expecting json response,
        // due to this they are not able to consume the response.
        // Currently, this issue is faced in card recurring only, hence making changes specific to card method only.
        if (($token->getMethod() === 'card') and
            ($token->isRecurring() === false) and
            (strpos(Request::getUri(), "/payments/create/recurring") !== false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_UNCONFIRMED_TOKEN_PASSED_IN_SECOND_RECURRING,
                Payment\Entity::BANK,
                [
                    'payment' => $payment->getId(),
                    'token'   => $token->getId(),
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
        if (($payment->getMethod() === Method::EMANDATE) and
            ($payment->isRecurringTypeInitial() === true) and
            ($payment->getAmount() > 0) and
            (Payment\Gateway::isDirectDebitEmandateBank($payment->getBank()) === true))
        {
            return;
        }

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

    protected function isTpvPreProcessingApplicable($payment)
    {
        if($payment->merchant->isTPVRequired() === false)
        {
            return false;
        }
        if($payment->isUpiTransfer() === true)
        {
            return false;
        }
        if(($payment->isNetbanking() === false) and ($payment->getMethod() !== Method::UPI))
        {
            return false;
        }
        return true;
    }

    protected function runPostGatewaySelectionPreProcessing(Payment\Entity $payment, array & $gatewayInput)
    {
        // Fees validation can only happen after international validation has gone through
        // otherwise can cause issues with international pricing rule being not available when
        // international is not enabled.
        $this->verifyFeesLessThanAmount($payment);

        // Must be done post terminal selection as the feature flag is gateway specific
        $this->verifyCheckoutDotComRecurring($payment);

        // We are doing it in post processing because terminal id is required for
        // fetching the wallet token as they are terminal specific
        $this->associateWalletTokenIfApplicable($payment);

        $this->setAuthAndAuthenticationGateway($payment, $gatewayInput);

        $this->setPaymentRoutedThroughCpsIfApplicable($payment, $gatewayInput);

        $this->preProcessHdfcVasSurcharge($payment);

        $this->repo->saveOrFail($payment);

        if (empty($payment->getGooglePayMethods()) === true)
        {
            $this->eventPaymentCreated();

            $this->tracePaymentInfo(TraceCode::PAYMENT_CREATED, Trace::DEBUG);

            $this->segment->trackPayment($payment, TraceCode::PAYMENT_CREATED);
        }

        //
        // Call gateway input
        //
        $gatewayInput['payment'] = $payment->toArrayGateway();
        $gatewayInput['callbackUrl'] = $this->getCallbackUrl();
        $gatewayInput['otpSubmitUrl'] = $this->getOtpSubmitUrl();
        $gatewayInput['payment_analytics'] = $payment->getMetadata('payment_analytics');

        if(isset($gatewayInput['billing_address']) and
            isset($payment['billing_address']) === false)
        {
            $gatewayInput['payment']['billing_address'] = $gatewayInput['billing_address'];
        }

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

            $this->updateOrderMetaIfApplicable($payment, $gatewayInput);
        }

        // modify account number in gateway input for some banks
        // to be called only in case of upi tpv transactions
        if ($this->isTpvPreProcessingApplicable($payment) === true)
        {
            $this->modifyAccountNumberForSpecificBanks($payment, $gatewayInput);
        }

        if ($payment->isAppCred() === true)
        {
            $this->addCredParams($payment, $gatewayInput);
        }

        /*
         * This is required to set the expiry for the emandate tokens this cannot
         * be done in token build as token does not have context on gateway and hence
         * doing it here.
         */
        $this->updateTokenForEmandate($payment);

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

    /**
     * Method to set orderMeta in gateway input.
     *
     * @param Payment\Entity $payment
     * @param array          $gatewayInput
     */
    protected function updateOrderMetaIfApplicable(Payment\Entity $payment, array &$gatewayInput)
    {
        /*
         * Currently, there are no merchants live for this use-case.
         * We are putting this check to avoid Database calls in If conditions.
        */
        if ($this->app->environment() === Environment::PRODUCTION)
        {
            return;
        }

        if ($payment->order->hasOrderMeta() === false)
        {
            return;
        }

        $formattedOrderMeta = (new Order\Core)->getFormattedOrderMeta($payment->order);

        foreach ($formattedOrderMeta as $key => $value)
        {
            $gatewayInput['order'] = array_merge($gatewayInput['order'], [$key => $value]);
        }
    }

    protected function updateTokenForEmandate(Payment\Entity $payment)
    {
        if (($payment->isEmandate() === false) or ($payment->isRecurringTypeInitial() === false))
        {
            return;
        }

        $token = $payment->getGlobalOrLocalTokenEntity();

        if ((Gateway::isSupportedEmandateDirectIntegrationGateway($payment->getGateway()) === true) and
            ($token->getExpiredAt() === null))
        {
            $expiredAt = Carbon::createFromTimestamp($payment->getCreatedAtAttribute())
                                ->addYears(Token\Entity::DEFAULT_EXPIRY_YEARS)
                                ->getTimestamp();

            $token->setExpiredAt($expiredAt);

            $token->saveOrFail();
        }
    }

    protected function addCredParams($payment, array & $gatewayInput)
    {
        $gatewayInput['cred'][Payment\Entity::APP_PRESENT] = $payment->getMetadata(Payment\Entity::APP_PRESENT) ?? false;

        $paymentAnalytics = $gatewayInput['payment_analytics'];

        if ($paymentAnalytics === null)
        {
            return;
        }

        $gatewayInput['cred']['os'] = $paymentAnalytics->getOs() ?? null;
        $gatewayInput['cred']['platform'] = $paymentAnalytics->getPlatform() ?? null;
        $gatewayInput['cred']['device'] = $paymentAnalytics->getDevice() ?? null;
        $gatewayInput['cred']['session_id'] = $paymentAnalytics->getCheckoutId() ?? null;

        if ($payment->hasOrder() === true) {
            $order = $payment->order;
            $gatewayInput['cred']['app_offer'] = $order->getAppOffer();
        }
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

        if (($gatewayInput['auth_type'] === Payment\AuthType::OTP) === true)
        {
            $payment->setAuthType(Payment\AuthType::OTP);
            return;
        }

        $otpAuth = [
            Payment\AuthType::IVR,
            Payment\AuthType::HEADLESS_OTP
        ];

        if (($authType !== null) and
            ($authType === Payment\AuthType::OTP) and
            (in_array($gatewayInput['auth_type'], $otpAuth, true) === true))
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
     * runPaymentMethodRelatedPreProcessing creates cards which is not used at all. To avoid this
     * we are passing dummy_payment flag in the input. There are no writes happen to DB in the function so,
     * we can run all select queries in replica instead of master.
     *
     * @param $payment
     * @param $input
     */
    protected function dummyPrePaymentAuthorizeProcessing($payment, $input)
    {
        $this->repo->useSlave(
            function() use ($payment, &$input)
            {
                $gatewayInput = [];

                $input['dummy_payment'] = true;

                unset($input['save']);

                $payment->setSave(false);

                $this->preProcessForUpiIfApplicable($input);

                $this->validateAndSetReceiverIfApplicable($payment, $input);

                $this->runPaymentMethodRelatedPreProcessing($payment, $input, $gatewayInput);

                $this->processCurrencyConversions($payment, $input);

                $this->attachEntityOrigin($payment);
            });

        $data = [];
        if(isset($input['mcc_request_id']))
        {
            $data['mcc_request_id'] = $input['mcc_request_id'];
        }

        return $data;
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

    /**
     * Used for applying dcc while calculating fee in customer/dynamic fee bearer model. The reason
     * for not using existing functions is that they create and save a payment meta entity while calculating
     * dcc which is later used for fetching gateway amount. Since the payment entity created while calculating
     * fee is dummy, payment meta must also not be saved
     *
     * @param Payment\Entity $payment
     * @param $input
     */
    public function dummyApplyDcc($payment, &$input)
    {
        /*
         * As of today DCC is only supported on the following with their respective percentages
         * 1. International Cards
         * 2. Alternate Payment Apps (Trustly, Poli etc)
         * 3. Paypal
         */

        if(($payment->merchant->isDCCEnabledInternationalMerchant() === false))
        {
            return;
        }

        switch ($payment->getMethod())
        {
            case Method::CARD :
                if (($payment->isCard() === false) or
                    ($payment->card === null) or
                    ((new Payment\Service)->isDccEnabledIIN($payment->card->iinRelation, $payment->merchant) === false))
                {
                    return;
                }

                $dccMarkupPerc = $payment->merchant->getDccMarkupPercentage();
                break;

            case Method::APP :
                if(($input['method'] !== Method::APP) or
                    (Gateway::isDCCRequiredApp($input['provider']) !== true))
                {
                    return;
                }

                $dccMarkupPerc = $payment->merchant->getDccMarkupPercentageForApps();
                break;

            case Method::WALLET :
                if($payment->getWallet() !== Wallet::PAYPAL)
                {
                    return;
                }

                $dccMarkupPerc = Merchant\Entity::DEFAULT_DCC_MARKUP_PERCENTAGE_FOR_PAYPAL;
                break;

            default :
                return;
        }

        if ((isset($input['dcc_currency']) === true) and
            (isset($input['currency_request_id']) === true)) {
            $dccCurrency = $input['dcc_currency'];
            $dccCurrencyRequestId = $input['currency_request_id'];

            $dccItems = ['amount', 'fee', 'tax'];

            foreach ($dccItems as $item) {
                $requestedCurrencyData = (new Currency\DCC\Service)->getRequestedCurrencyDetails($payment->getCurrency(), $input[$item],
                    $dccCurrency, $dccCurrencyRequestId, $dccMarkupPerc);

                if (empty($requestedCurrencyData) === true) {
                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_DCC_INVALID_REQUEST_ID, 'currency_request_id',
                        [
                            'currency_request_id' => $dccCurrencyRequestId,
                            'dcc_currency' => $dccCurrency,
                        ], 'Invalid currency_request_id');
                }

                $input['dcc_' . $item] = (int)$requestedCurrencyData['amount'];
            }
            $input['dcc_applied'] = true;
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

    protected function validateOpgspImportDataIfApplicable(Payment\Entity $payment)
    {
        if($payment->merchant->isOpgspImportEnabled() === true)
        {
            $library = (new Payment\Service)->getLibraryFromPayment($payment);

            if(in_array($library, Analytics\Metadata::OPGSP_SUPPORTED_LIBRARIES) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_LIBRARY,
                    [
                        'merchant_id' => $payment->merchant->getId(),
                    ]);
            }

            /*
             * Validate payment methods.
             * OPGSP import flow supports only Cards and NB.
             */
            if (in_array($payment->getMethod(), Method::OPGSP_IMPORT_SUPPORTED_METHODS) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_PAYMENT_METHOD,
                    [
                        'merchant_id' => $payment->merchant->getId(),
                    ]);
            }

            //Validate if invoice number is present in notes
            if (empty($payment->getNotes()))
            {
                $this->trace->error(
                    TraceCode::INVALID_NOTES_FOR_OPGSP_IMPORT,
                    ['payment_id' => $payment->getId()]
                );
                throw new Exception\BadRequestValidationFailureException(
                    'Notes field is required with invoice_number.', 'notes');
            }

            $paymentNotes = $payment->getNotes()->toArray();

            // Validate invoice number is present in notes
            if (empty($paymentNotes[InvoiceConstants::OPGSP_INVOICE_NUMBER]))
            {
                $this->trace->error(
                    TraceCode::INVALID_INVOICE_FOR_OPGSP_IMPORT, [
                        'payment_id' => $payment->getId(),
                        'message' => 'invoice number is missing'
                    ]
                );
                throw new Exception\BadRequestValidationFailureException(
                    'Invoice number field is required with in the notes.', 'notes');
            }

            // Validate length of invoice number
            if (strlen($paymentNotes[InvoiceConstants::OPGSP_INVOICE_NUMBER]) > InvoiceConstants::INVOICE_NUMBER_LENGTH)
            {
                $this->trace->error(
                    TraceCode::INVALID_INVOICE_FOR_OPGSP_IMPORT, [
                        'payment_id' => $payment->getId(),
                        'message' => 'Length of invoice number is greater than expected'
                    ]
                );
                throw new Exception\BadRequestValidationFailureException(
                    'Invoice number should be less than or equal to ' . InvoiceConstants::INVOICE_NUMBER_LENGTH . ' characters.', 'notes');
            }

            $invoiceNumber = $paymentNotes[InvoiceConstants::OPGSP_INVOICE_NUMBER];

            // Validate uniqueness of invoice number
            $invoice = (new InvoiceService())
                ->findByMerchantIdDocumentTypeDocumentNumber($payment->getMerchantId(), InvoiceType::OPGSP_INVOICE, $invoiceNumber);
            if (isset($invoice) === false) return;

            $existingPayment = $this->repo->payment->findOrFail($invoice->getEntityId());
            if (isset($existingPayment) and $existingPayment->getStatus() !== Status::FAILED)
            {
                $this->trace->error(
                    TraceCode::INVALID_INVOICE_FOR_OPGSP_IMPORT, [
                        'payment_id' => $payment->getId(),
                        'message' => 'Payment already exist with same invoice number'
                    ]
                );
                throw new Exception\BadRequestValidationFailureException(
                    'Payment already exist with same invoice number.', 'notes');
            }
        }
    }

    protected function runFraudChecksIfApplicable(Payment\Entity $payment, $input = [])
    {
        // We need to disable fraud checks for redirection payments before redirection hence this check. This will
        // be later handled within payment service
        if (($this->shouldRedirect($payment) === true) or
            ($this->shouldRedirectV2($payment, ["fraud_check" => true]) === true) or
            ($this->shouldRedirectDCC($payment) === true) or
            ($this->shouldRedirectRaasInternational($payment) === true) or
            ($this->shouldRedirectForAddressCollection($payment) === true))
        {
            return;
        }

        try
        {
              $this->runFraudChecks($payment, $input);
        }
        catch (\Throwable $ex)
        {
            throw $ex;
        }
    }

    /**
     * @param Payment\Entity $payment
     * @param $input
     * @return mixed
     * @throws Exception\BadRequestException
     * @throws \Throwable
     */
    protected function runFraudChecks(Payment\Entity $payment, $input)
    {

        try
        {
            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_RISKCHECK_INITIATED, $payment);

            $riskSource = Risk\Source::INTERNAL;

            // for now use api only for bin based blocking until shield is not live 100%
            $this->validateBlockedCard($payment);

             // Shield is 100% live. In case shield is down, solution implemented as discussed in Jira ticket: CARD-593
             // https://docs.google.com/document/d/1RdfWJbyunG1E0RzwqrcEyVA-U8I8_-nIfUN6alphGwI

            $shieldFailure = false;
            $shieldOnValue = 'shield_on';

            $razorxConfig = $this->app['config']->get('applications.razorx');

            $isLiveEnvironment = (($this->app['env'] === Environment::PRODUCTION) and ($this->mode === Mode::LIVE)
                and ($razorxConfig['mock'] !== true));

            if ($isLiveEnvironment === true)
            {
                // As shield is 100% live therefore we are not calling the razorx on live.
                $razorxResult = $shieldOnValue;
            }
            else
            {
                // For non-live env we still need to call the razorx mock service for existing test to pass.
                $razorxResult = $this->app->razorx->getTreatment($payment->getId(), 'shield_risk_evaluation', $this->mode);
            }

            $shouldRunFraudDetectionV2 = (
                ($razorxResult === $shieldOnValue) and
                ($payment->shouldRunShieldChecks() === true)
            );

            if ($shouldRunFraudDetectionV2 === true)
            {
                $riskSource = Risk\Source::SHIELD;

                try
                {
                    $this->validateFraudDetectionV2($payment, $this->merchant, $input);
                }
                catch (Exception\IntegrationException $exception)
                {
                    $shieldFailure = true;
                }
                catch (\WpOrg\Requests\Exception $exception)
                {
                    $shieldFailure = true;
                }
                finally
                {
                    $payment->setMetadataKey('shield_risk_execution', 'shield_on');
                }
            }

            if ($shieldFailure === true)
            {
                $riskSource = Risk\Source::MANUAL;

                if ($payment->hasCard() and $payment->card->isInternational() === true)
                {

                    $data = [
                        'payment_id' => $payment->getPublicId(),
                        'method'     => $payment->getMethod(),
                    ];

                    $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD;
                    $e = new Exception\BadRequestException($errorCode, null, $data);

                    //for RaaS International Payments we skip this as this will be performed inside the performFraudCheckforInternational
                    if ($this->shouldRedirectRaasInternational($payment) === false)
                    {
                        $this->updatePaymentAuthFailed($e);
                    }
                    throw $e;
                }
                else
                {
                    $this->trace->info(
                        TraceCode::FRAUD_DETECTION_SKIPPED,
                        [
                            'payment_id'  => $payment->getPublicId(),
                            'environment' => $this->app['env'],
                            'mode'        => $this->mode,
                        ]
                    );

                    $this->trace->count(Payment\Metric::SHIELD_FRAUD_DETECTION_SKIPPED);
                }
            }

            $this->app['diag']->trackPaymentEventV2(
                EventCode::PAYMENT_RISKCHECK_PROCESSED,
                $payment,
                null,
                [
                    'metadata' => [
                        'payment' => [
                            'id'            => $payment->getPublicId(),
                            'risk_source'   => $riskSource
                        ]
                    ],
                    'read_key'  => array('payment.id'),
                    'write_key' => 'payment.id'
                ],
                [
                    'risk_source'   => $riskSource
                ]);
        }
        catch (\Throwable $ex)
        {
            $this->app['diag']->trackPaymentEventV2(
                EventCode::PAYMENT_RISKCHECK_PROCESSED,
                $payment,
                $ex,
                [
                    'metadata' => [
                        'payment' => [
                            'id'            => $payment->getPublicId(),
                            'risk_source'   => $riskSource
                        ]
                    ],
                    'read_key'  => array('payment.id'),
                    'write_key' => 'payment.id'
                ],
                [
                    'riskSource' => $riskSource
                ]);

            $this->addBackupMethodForRetry($this->payment, $this->merchant, $ex);

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

        $productType = $payment->order ? $payment->order->getProductType() : null;

        $orderId = $payment->order ? $payment->order->getId() : null;

        if ($merchant->isInternational() === false)
        {
            $data = [
                'payment_id' => $payment->getPublicId(),
                'method'     => $payment->getMethod()
            ];

            if ($orderId !== null)
            {
                $data['order_id'] = Order\Entity::getIdPrefix().$orderId;
            }

            $e = new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_INTERNATIONAL_NOT_ALLOWED, null,
                $data);

            $this->updatePaymentAuthFailedAndThrowException($e);
        }

        switch ($productType)
        {
            case ProductType::PAYMENT_LINK:
            case ProductType::PAYMENT_LINK_V2:
                $paymentProduct = ProductInternationalMapper::PAYMENT_LINKS;
                break;
            case ProductType::INVOICE:
                $paymentProduct = ProductInternationalMapper::INVOICES;
                break;
            case ProductType::PAYMENT_PAGE:
                $paymentProduct = ProductInternationalMapper::PAYMENT_PAGES;
                break;
            default :
                //If product type is null ,it is treated as payment gateway
                $paymentProduct = $productType ? $productType : ProductInternationalMapper::PAYMENT_GATEWAY;
        }

        if ($merchant->isInternationalEnabledForProduct($paymentProduct) === false)
        {
            $errorCode = ProductInternationalMapper::PRODUCT_ERROR_CODE[$paymentProduct];

            $data = [
                'payment_id' => $payment->getPublicId(),
                'method'     => $payment->getMethod(),
                'product'    => $paymentProduct
            ];

            if ($orderId !== null)
            {
                $data['order_id'] = Order\Entity::getIdPrefix().$orderId;
            }

            $e = new Exception\BadRequestException($errorCode, null,
                $data);

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
                'method'     => $payment->getMethod(),
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

        if ($payment->isUpi() === true)
        {
            $this->modifyGatewayInputForUpi($payment, $data);
        }

        $response = $this->callGatewayFunction(Action::AUTHORIZE_FAILED, $data);

        if ($payment->isAppCred() === true)
        {
            $this->addDiscountToCred($payment, $response);
        }

        if ($payment->isCardlessEmiWalnut369() === true)
        {
            $this->addDiscountToWalnut369($payment, $response);
        }

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

        // The gateways which allow amount difference in authorized will send
        // amount_authorized field explicitly in response
        if ((isset($response[Payment\Entity::AMOUNT_AUTHORIZED]) === true) and
            (isset($response[Payment\Entity::CURRENCY]) === true))
        {
            $currency           = $response[Payment\Entity::CURRENCY];
            $amountAuthorized   = $response[Payment\Entity::AMOUNT_AUTHORIZED];

            if ($this->processGatewayAmountAuthorized($payment, $currency, $amountAuthorized) === false)
            {
                // We are still throwing the same exception for amount mismatch cases which is being
                // thrown by the Base\Gateway, it will be caught later as BaseException
                throw new Exception\RuntimeException(
                    'Payment amount verification failed.',
                    [
                        'payment_id' => $payment->getId(),
                        'gateway'    => $payment->getGateway(),
                    ]);
            }
        }

        $payment->setVerified(true);

        $isLateAuth = true;

        // Handle special case where first gateway success happened using verify flow.
        // This is for those gateways where callback flow is not implemented,
        // and we are not aware of the gateway status until hitting their Inquiry API.
        // In such cases, we want to avoid setting lateAuth flag,
        // since these are not true lateAuth cases
        // - https://razorpay.slack.com/archives/CNP473LRF/p1648449676603789
        // - https://razorpay.slack.com/archives/CNXC0JHQF/p1648817541865879
        if (Payment\Gateway::isTransactionPendingGateway($payment->getMethod(),
                                                         $payment->getGateway(),
                                                         $payment->getInternalErrorCode()))
        {
            $isLateAuth = false;
        }

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

            $payment->setLateAuthorized($isLateAuth);
        }
        else
        {
            // The first argument marks the payment as converted from failed
            // to authorized
            $this->updateAndNotifyPaymentAuthorized($response, $isLateAuth);

            if (($payment->isUpiRecurring() === true) and
                ($this->shouldHitDebitOnRecurringForUpi($payment) === true))
            {
                $this->processRecurringDebitForUpi($payment);
            }
        }

        $this->repo->saveOrFail($payment);

        try
        {
            $this->postPaymentAuthorizeOfferProcessing($payment);

            $this->autoCapturePaymentIfApplicable($payment);
        }
        catch (Exception\BaseException $e)
        {
            $this->trace->info(
                TraceCode::PAYMENT_AUTO_CAPTURE_FAILED,
                [
                    'payment_id' => $payment->getPublicId(),
                ]);
        }

        $this->setPayment($payment);
    }

    /**
     * @throws Exception\BadRequestException
     */
    protected function preProcessDCCInputs(array $input, Payment\Entity $payment)
    {
        if (($payment->isCard() === false) or ($payment->merchant->isDCCEnabledInternationalMerchant() === false))
        {
            return;
        }

        if($payment->card === null or (new Payment\Service)->isDccEnabledIIN($payment->card->iinRelation, $payment->merchant) === false)
        {
            return;
        }

        if ((isset($input['dcc_currency']) === true) and
            (isset($input['currency_request_id']) === true))
        {
            $dccCurrency = $input['dcc_currency'];

            $dccCurrencyRequestId = $input['currency_request_id'];

            $requestedCurrencyData = (new Currency\DCC\Service)->getRequestedCurrencyDetails($payment->getCurrency(), $payment->getAmount(),
                $dccCurrency, $dccCurrencyRequestId, $payment->merchant->getDccMarkupPercentage());

            if (empty($requestedCurrencyData) === true)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_DCC_INVALID_REQUEST_ID, 'currency_request_id',
                    [
                        'currency_request_id' => $dccCurrencyRequestId,
                        'dcc_currency'        => $dccCurrency,
                    ], 'Invalid currency_request_id');
            }

            $paymentMetaInput = [
                'gateway_amount'            => $requestedCurrencyData['amount'],
                'gateway_currency'          => $requestedCurrencyData['currency'],
                'forex_rate'                => $requestedCurrencyData['forex_rate'],
                'dcc_offered'               => true,
                'payment_id'                => $payment->getId(),
                'dcc_mark_up_percent'       => $requestedCurrencyData['dcc_mark_up_percent']
            ];


            $paymentMeta = (new PaymentMeta\Repository())->findByPaymentId($payment->getId());


            if(empty($paymentMeta))
            {
                $paymentMetaEntity = (new Payment\PaymentMeta\Core)->create($paymentMetaInput);

                $paymentMetaEntity->payment()->associate($payment);
            }
            else
            {
                $paymentMetaEntity = (new Payment\PaymentMeta\Core)->updateDccInfo($paymentMeta, $paymentMetaInput);
            }

            $this->trace->info(TraceCode::PAYMENT_DCC_PROCESSED, $paymentMetaInput);
        }
    }

    protected function preProcessDCCForRecurringAutoOnDirect(array $input, Payment\Entity $payment)
    {
        if ($this->checkDCCForRecurringAutoOnLibraryDirect($input,$payment) === false)
        {
            return;
        }

        $cardCountry = $payment->card->getCountry();

        $dccCurrency = Currency\Currency::getCurrencyForCountry($cardCountry);

        /* Edge Cases
            1. If Payment Currency == Card Holder Currency (No DCC to be applied)
            2. Currency not Supported by us for that given country
        */

        if($dccCurrency === null || $payment->getCurrency() === $dccCurrency)
        {
            return;
        }

        // Skip DCC for 3 Decimal Currencies
        if(in_array($dccCurrency, Currency\Currency::THREE_DECIMAL_CURRENCIES, true) === true)
        {
            return;
        }

        if($this->evalExperimentDCCRecurringAutoOnLibraryDirect($payment) !== true)
        {
            return;
        }

        $dccInfo = (new Payment\Service)->getDCCInfo($payment->getAmount(), $payment->getCurrency(), $payment->merchant->getDccRecurringMarkupPercentage());

        $requestedCurrencyData = $dccInfo['all_currencies'][$dccCurrency];

        if (empty($requestedCurrencyData) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_DCC_INVALID_REQUEST_ID,
                [
                    'dcc_currency'        => $dccCurrency,
                    'payment_id'          => $payment->getId(),
                ]);
        }

        $paymentMetaInput = [
            'gateway_amount'            => $requestedCurrencyData['amount'],
            'gateway_currency'          => $dccCurrency,
            'forex_rate'                => $requestedCurrencyData['forex_rate'],
            'dcc_offered'               => true,
            'payment_id'                => $payment->getId(),
            'dcc_mark_up_percent'       => $requestedCurrencyData['conversion_percentage']
        ];

        $paymentMeta = (new PaymentMeta\Repository())->findByPaymentId($payment->getId());

        if(empty($paymentMeta))
        {
            $paymentMetaEntity = (new Payment\PaymentMeta\Core)->create($paymentMetaInput);

            $paymentMetaEntity->payment()->associate($payment);
        }
        else
        {
            $paymentMetaEntity = (new Payment\PaymentMeta\Core)->updateDccInfo($paymentMeta, $paymentMetaInput);
        }

        $this->trace->info(TraceCode::PAYMENT_DCC_PROCESSED, $paymentMetaInput);
    }

    public function checkDccMetaRecord($payment): bool
    {
        $paymentMeta = (new PaymentMeta\Repository())->findByPaymentId($payment->getId());

        $response = empty($paymentMeta);

        if($response === false)
        {
            $library = (new Payment\Service)->getLibraryFromPayment($payment);

            if(($library === Analytics\Metadata::DIRECT and $paymentMeta['dcc_offered'] === false) ||
                ($paymentMeta['mcc_applied'] === true and $paymentMeta['dcc_offered'] === false) )
            {
                $response = true;
            }
        }

        return $response;
    }

    protected function preProcessHdfcVasSurcharge(Payment\Entity $payment)
    {
        if( $payment->isHdfcVasDSCustomerFeeBearerSurcharge() === false )
        {
            return;
        }

        $surchargeDetails = $payment->getFee();

        $tax = $payment->getTax();

        $gatewayAmount = $payment->getBaseAmount() - $surchargeDetails;

        $paymentMetaInput = [
            'payment_id'                => $payment->getId(),
            'gateway_amount'            => $gatewayAmount,
        ];

        $paymentMeta = (new PaymentMeta\Repository())->findByPaymentId($payment->getId());

        if(empty($paymentMeta))
        {
            $paymentMetaEntity = (new Payment\PaymentMeta\Core)->create($paymentMetaInput);

            $paymentMetaEntity->payment()->associate($payment);
        }
        else
        {
            $paymentMetaEntity = (new Payment\PaymentMeta\Core)->edit($paymentMeta, $paymentMetaInput);
        }

        // payment meta relation ship will be called during the terminal selection and the
        // in memory object is set to null, hence reloading the inmemory object by calling load
        $payment->load('paymentMeta');

        $this->trace->debug(
            TraceCode::HDFC_VAS_SURCHARGE_GATEWAY_AMOUNT_MODIFIED,
            [
                'network'           => $payment->card->getNetwork(),
                'feeBearerCustomer' => $payment->isFeeBearerCustomer(),
                'iDirectSettlement' => $payment->isDirectSettlement(),
                'surchargeDetails'  => $surchargeDetails,
                'tax'               => $tax,
                'baseAmount'        => $payment->getBaseAmount(),
                'newGatewayAmount'  => $gatewayAmount,
            ]
        );
    }

    protected function modifyPaymentForHdfcVasSurchargeNonDS(Payment\Entity $payment)
    {
        if( $payment->isHdfcNonDSSurcharge() === false )
        {
            return;
        }

        $originalValues = [];

        $surchargeDetails = $payment->getFee();

        $tax = $payment->getTax();

        $gatewayAmount = $payment->getBaseAmount() - $surchargeDetails;

        $expectedFee = 0;

        $originalValues[Payment\Entity::AMOUNT] = $payment->getAmount();

        $payment->setAmount( $gatewayAmount);

        $originalValues[Payment\Entity::BASE_AMOUNT] = $payment->getBaseAmount();

        $payment->setBaseAmount($gatewayAmount);

        $originalValues[Payment\Entity::FEE] = $payment->getFee();

        $payment->setFee($expectedFee);

        $originalValues[Payment\Entity::TAX] = $payment->getTax();

        $payment->setTax($expectedFee);

        $originalValues[Payment\Entity::MDR] = $payment->getAttribute(Payment\Entity::MDR);

        $payment->setMdr($expectedFee);

        $this->trace->debug(
            TraceCode::HDFC_VAS_SURCHARGE_2_PAYMENT_MODIFIED,
            [
                'network'           => $payment->card->getNetwork(),
                'feeBearerCustomer' => $payment->isFeeBearerCustomer(),
                'iDirectSettlement' => $payment->isDirectSettlement(),
                'surchargeDetails'  => $surchargeDetails,
                'tax'               => $tax,
                'baseAmount'        => $payment->getBaseAmount(),
                'newGatewayAmount'  => $gatewayAmount,
            ]
        );

        return [$payment, $originalValues];
    }

    protected function revertModifyPaymentForHdfcVasSurchargeNonDS(Payment\Entity $payment, array $originalValues)
    {
        $surchargeDetails = $payment->getFee();

        $tax = $payment->getTax();

        $baseAmount = $payment->getBaseAmount();

        $payment->modifyAttribute(Payment\Entity::AMOUNT, $originalValues[Payment\Entity::AMOUNT]);

        $payment->modifyAttribute(Payment\Entity::BASE_AMOUNT, $originalValues[Payment\Entity::BASE_AMOUNT]);

        $payment->modifyAttribute(Payment\Entity::FEE, $originalValues[Payment\Entity::FEE]);

        $payment->modifyAttribute(Payment\Entity::TAX, $originalValues[Payment\Entity::TAX]);

        $payment->modifyAttribute(Payment\Entity::MDR, $originalValues[Payment\Entity::MDR]);

        $this->trace->debug(
            TraceCode::HDFC_VAS_SURCHARGE_2_PAYMENT_MODIFIED_REVERT,
            [
                'network'           => $payment->card->getNetwork(),
                'feeBearerCustomer' => $payment->isFeeBearerCustomer(),
                'iDirectSettlement' => $payment->isDirectSettlement(),
                'surchargeDetails'  => $surchargeDetails,
                'tax'               => $tax,
                'baseAmount'        => $baseAmount,
                'originalValues'    => $originalValues,
            ]
        );

        return $payment;
    }

    protected function verifyCheckoutDotComRecurring(Payment\Entity $payment)
    {
        if ($payment->isRecurring() and $payment->isInternational() and
            $payment->isGateway(Payment\Gateway::CHECKOUT_DOT_COM) and
            $payment->merchant->isFeatureEnabled(Features::RECURRING_CHECKOUT_DOT_COM) === false)
        {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_INTERNATIONAL_RECURRING_NOT_ALLOWED_FOR_MERCHANT,
                    null,
                    [
                        'merchant_id' => $payment->merchant->getId(),
                        'gateway' => $payment->getGateway()
                    ]);
        }
    }

    protected function preProcessWalletCurrencyWrapper(array $input, Payment\Entity $payment)
    {
        if (($input['method'] !== Method::WALLET) or ($input['wallet'] !== Wallet::PAYPAL))
        {
            return;
        }

        if ((isset($input['dcc_currency']) === true) and
            (isset($input['currency_request_id']) === true) and
            ($input['currency'] !== $input['dcc_currency']))
        {
            $dccCurrency = $input['dcc_currency'];

            if (in_array($dccCurrency, Gateway\Constants::PAYPAL_SUPPORTED_CURRENCIES) === false) {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED,
                    null,
                    [
                        'payment_id' => $payment->getId(),
                        'dccCurrency' => $dccCurrency,
                        'wallet' => Wallet::PAYPAL,
                        'currency' => $input['currency'],
                    ]
                );
            }

            $dccCurrencyRequestId = $input['currency_request_id'];

            // markup of 5 is hardcoded at org-level
            $requestedCurrencyData = (new Currency\DCC\Service)->getRequestedCurrencyDetails($payment->getCurrency(), $payment->getAmount(),
                $dccCurrency, $dccCurrencyRequestId, Merchant\Entity::DEFAULT_DCC_MARKUP_PERCENTAGE_FOR_PAYPAL);

            if (empty($requestedCurrencyData) === true)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_DCC_INVALID_REQUEST_ID, 'currency_request_id',
                    [
                        'currency_request_id' => $dccCurrencyRequestId,
                        'dcc_currency'        => $dccCurrency,
                    ], 'Invalid currency_request_id');
            }

            $paymentMetaInput = [
                'gateway_amount'            => $requestedCurrencyData['amount'],
                'gateway_currency'          => $requestedCurrencyData['currency'],
                'forex_rate'                => $requestedCurrencyData['forex_rate'],
                'dcc_offered'               => true,
                'payment_id'                => $payment->getId(),
                'dcc_mark_up_percent'       => $requestedCurrencyData['dcc_mark_up_percent']
            ];

            $paymentMeta = (new PaymentMeta\Repository())->findByPaymentId($payment->getId());

            if(empty($paymentMeta))
            {
                $paymentMetaEntity = (new Payment\PaymentMeta\Core)->create($paymentMetaInput);

                $paymentMetaEntity->payment()->associate($payment);
            }
            else
            {
                $paymentMetaEntity = (new Payment\PaymentMeta\Core)->updateDccInfo($paymentMeta, $paymentMetaInput);
            }

            $this->trace->info(TraceCode::PAYMENT_DCC_PROCESSED, $paymentMetaInput);
        }

        if (isset($input['dcc_currency']) === false) {

            $currency = $input['currency'];

            if (in_array($currency, Gateway\Constants::PAYPAL_SUPPORTED_CURRENCIES) === false) {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED,
                    null,
                    [
                        'payment_id' => $payment->getId(),
                        'dccCurrency' => 'Not Applicable',
                        'wallet' => Wallet::PAYPAL,
                        'currency' => $input['currency'],
                    ]
                );
            }
        }
    }

    protected function preProcessAppCurrencyWrapperForRedirectFlow(array $input, Payment\Entity $payment) {
        if($payment->getMethod()!==Method::APP or $payment->merchant->isDCCEnabledInternationalMerchant() === false) {
            return;
        }
        $input['method'] = $payment->getMethod();
        $input['provider'] = $payment->getWallet();
        $input['currency'] = $payment->getCurrency();

        $this->preProcessAppCurrencyWrapper($input,$payment);
    }

    protected function preProcessAppCurrencyWrapper(array $input, Payment\Entity $payment)
    {
        if (($input['method'] !== Method::APP) or
            (Gateway::isDCCRequiredApp($input['provider']) !== true) or
            ($payment->merchant->isDCCEnabledInternationalMerchant() === false))
        {
            return;
        }

        if ((isset($input['dcc_currency']) === true) and
            (isset($input['currency_request_id']) === true) and
            ($input['currency'] !== $input['dcc_currency']))
        {
            $dccCurrency = $input['dcc_currency'];

            $dccCurrencyRequestId = $input['currency_request_id'];

            $requestedCurrencyData = (new Currency\DCC\Service)->getRequestedCurrencyDetails($payment->getCurrency(), $payment->getAmount(),
                $dccCurrency, $dccCurrencyRequestId, $payment->merchant->getDccMarkupPercentageForApps());

            if (empty($requestedCurrencyData) === true)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_DCC_INVALID_REQUEST_ID, 'currency_request_id',
                    [
                        'currency_request_id' => $dccCurrencyRequestId,
                        'dcc_currency'        => $dccCurrency,
                    ], 'Invalid currency_request_id');
            }

            $paymentMetaInput = [
                'gateway_amount'            => $requestedCurrencyData['amount'],
                'gateway_currency'          => $requestedCurrencyData['currency'],
                'forex_rate'                => $requestedCurrencyData['forex_rate'],
                'dcc_offered'               => true,
                'payment_id'                => $payment->getId(),
                'dcc_mark_up_percent'       => $requestedCurrencyData['dcc_mark_up_percent']
            ];

            $paymentMeta = (new PaymentMeta\Repository())->findByPaymentId($payment->getId());

            if(empty($paymentMeta))
            {
                $paymentMetaEntity = (new Payment\PaymentMeta\Core)->create($paymentMetaInput);

                $paymentMetaEntity->payment()->associate($payment);
            }
            else
            {
                $paymentMetaEntity = (new Payment\PaymentMeta\Core)->updateDccInfo($paymentMeta, $paymentMetaInput);
            }

            $this->trace->info(TraceCode::PAYMENT_DCC_PROCESSED, $paymentMetaInput);
        }
    }

    protected function processCurrencyConversions(Payment\Entity $payment, &$input = null)
    {
        $currency = $payment->getCurrency();

        $merchant = $payment->merchant;

        // For card and App method payments, check all conditions
        // and for rest payment methods check only if currency != INR
        if (($currency !== $merchant->getCurrency() && !$merchant->isCustomerFeeBearerAllowedOnInternational()) &&
            ((($payment->getMethod() != Method::CARD) && ($payment->getMethod() != Method::APP)) ||
             ($merchant->isDCCEnabledInternationalMerchant() === false ||
              $payment->isInternational() === false)))
        {
            // mcc is supported only for merchants where this flag is set to true or false
            // or merchant is not fee bearer
            if (($merchant->convertOnApi() === null) or ($merchant->isFeeBearerCustomerOrDynamic()))
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
            // else api should do currency conversion and use INR terminals
            $convertCurrency = $merchant->convertOnApi();

            if ($payment->isInternational() === false)
            {
                $convertCurrency = true;
            }

            $payment->setConvertCurrency($convertCurrency);

        }

        $amount = $payment->getAmount();

        /**
         * Allow MCC on international merchants on CFB only if the feature flag is enabled
         * And remove fee for the calculation of base amount, as fee calculation is done on base :-)
         */

        if ($payment->isInternational() and
            $currency !== $merchant->getCurrency() and
            $merchant->isFeeBearerCustomerOrDynamic())
        {
            if($merchant->isCustomerFeeBearerAllowedOnInternational())
            {
                $amount = $amount - $payment->getFee();
            }
            else
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED,
                    null,
                    [
                        'fee_bearer_customer'   => $merchant->isFeeBearerCustomerOrDynamic(),
                        'payment_id'            => $payment->getId(),
                        'currency'              => $currency,
                    ]);
            }
        }

        $baseAmount = (new Currency\Core)->getBaseAmount($amount, $currency, $merchant->getCurrency(), $input);

        // if gateway is doing currency conversions, actual rate used by gateway
        // will use lower than current rates hence we also use merchant / default
        // level percentage for lower values in base_amount for settlement.
        if ($payment->getConvertCurrency() === false ||
            ($currency !== $merchant->getCurrency() && $payment->getConvertCurrency() === null))
        {
            $input['mcc_mark_down_percent'] = $merchant->getMccMarkdownMarkdownPercentage();
            $mccMarkdownPercentage = 1 - $input['mcc_mark_down_percent'] / 100;
            $baseAmount = (int) ceil($baseAmount * $mccMarkdownPercentage);
        }

        /**
         * Correct the base amount by adding back the removed fee in case of MCC.
         * Strange workarounds eh? Things you have to do for NR (Ask your product manager about it)
         */

        if ($payment->isInternational() and
            $currency !== $merchant->getCurrency() and
            $merchant->isFeeBearerCustomerOrDynamic())
        {
            if($merchant->isCustomerFeeBearerAllowedOnInternational())
            {
                $baseFee = (new Currency\Core)->getBaseAmount($payment->getFee(), $currency, $merchant->getCurrency(), $input);
                $baseAmount = $baseAmount + $baseFee;
            }
            else
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED,
                    null,
                    [
                        'fee_bearer_customer'   => $merchant->isFeeBearerCustomerOrDynamic(),
                        'payment_id'            => $payment->getId(),
                        'currency'              => $currency,
                    ]);
            }
        }

        $dummyProcessing = $input['dummy_payment'] ?? false ;

        if(isset($input['mcc_mark_down_percent']) && isset($input['mcc_forex_rate']) && isset($input['mcc_applied']) && !($dummyProcessing))
        {
            $paymentMetaInput = [
                'mcc_applied'           => $input['mcc_applied'],
                'mcc_mark_down_percent' => $input['mcc_mark_down_percent'],
                'mcc_forex_rate'        => $input['mcc_forex_rate'],
                'payment_id'            => $payment->getId(),
            ];

            $paymentMeta = (new PaymentMeta\Repository())->findByPaymentId($payment->getId());

            if(empty($paymentMeta))
            {
                $paymentMetaEntity = (new Payment\PaymentMeta\Core)->create($paymentMetaInput);

                $paymentMetaEntity->payment()->associate($payment);
            }
            else
            {
                $paymentMetaEntity = (new Payment\PaymentMeta\Core)->updateMccInfo($paymentMeta, $paymentMetaInput);
            }
        }
        unset($input['mcc_mark_down_percent'], $input['mcc_forex_rate'], $input['mcc_applied']);

        $payment->setBaseAmount($baseAmount);
    }

    protected function isAllowedWithoutCustomerForSubscription(Payment\Entity $payment)
    {
        return ($payment->hasSubscription() === true) and
            (($payment->isCardRecurring() === true) or
                ($payment->isUpiRecurring() === true) or
                ($payment->isEmandate() === true));
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
                            Payment\Entity::METHOD          => $input['method'],
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

        if ((empty($input[Payment\Entity::CUSTOMER_ID]) === true) and
            (empty($input[Payment\Entity::RECURRING_TOKEN]) === false) and
            (empty($input[Payment\Entity::RECURRING_TOKEN][Payment\Entity::NOTIFICATION_ID]) === false) and
            ($payment->cardMandateNotification !== null))
        {
            $cardMandate = $payment->cardMandateNotification->cardMandate;
            $input[Payment\Entity::CUSTOMER_ID] = $cardMandate->token->customer->getPublicId();
        }

        $merchant = $this->merchant;
        //
        // Appends dummy cvv if auth type of payment is skip. Validate merchant later
        // for moto feature else decline the payment.
        //
        if ($payment->getAuthType() === Payment\AuthType::SKIP)
        {
            $input['card']['cvv'] = Card\Entity::DUMMY_CVV;

            if (isset($input['card']['number']) === true)
            {
                $variant = $this->app->razorx->getTreatment(
                    $merchant->getId(),
                    Merchant\RazorxTreatment::USE_DETECT_NETWORK_FOR_DUMMY_CVV,
                    $this->mode
                );

                if (strtolower($variant) === 'on')
                {
                    $cardNumber = $input['card']['number'];
                    $iin        = substr($cardNumber ?? null, 0, 6);
                    $network    = Card\Network::detectNetwork($iin);
                    $input['card']['cvv'] = Card\Entity::getDummyCvv($network);
                }
            }
        }

        // fetching customer id from partner merchant since customer belong to partner merchant
        if ($this->usePartnerMerchantForTokenInteroperabilityIfApplicable($payment, $input) === true )
        {
            $merchant = $merchant->getFullManagedPartnerWithTokenInteroperabilityFeatureIfApplicable($merchant);
        }

        // First fetch the relevant customer (global or local)
        list($customer, $customerApp) = (new Customer\Core)->getCustomerAndApp(
                                                                $input, $merchant, $followGlobal);

        // need to enable this check before enabling the token_interoperability
       // $this->isTokenInteroperabilityAllowed($customer, $payment, $input);

        if (($customer === null) and
            ($payment->hasSubscription() === true) and
            ($this->isAllowedWithoutCustomerForSubscription($payment) === true))
        {
            $this->preProcessPaymentForSubscriptionWithoutCustomer($payment, $input, $gatewayInput);
        }
        else if (($customer === null) and
                 ($payment->isRazorpaywalletPayment() === true))
        {
            if (!isset($input['wallet_user_id']) and isset($input['contact'])){
                $payment->setContact($input['contact']);
            }
            else
            {
                $payment->setReference14($input['wallet_user_id']);
            }

        }
        else if ($customer === null)
        {
            $this->preProcessPaymentWithoutCustomer($payment, $input, $gatewayInput);
        }
        else if ($customer->isLocal() === true)
        {
            $this->createUpiMandateForSubscriptionIfApplicable($customer, $input, $payment);

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

            if((($payment->getMethod() === PaymentConstants::UPI) or
                    (($payment->getMethod() === PaymentConstants::CARD) and
                        (($payment->isRecurringTypeInitial() === true) or
                            ($payment->isRecurringTypeCardChange() === true))) or
                                ($payment->getMethod() === PaymentConstants::EMANDATE))
                and ($this->subscription !== null))
            {
                $this->createUpiMandateForSubscriptionIfApplicable($localCustomer, $input, $payment);

                $this->preProcessPaymentForLocalCustomer($localCustomer, $payment, $input, $gatewayInput);
            }
            else
            {
                $this->preProcessPaymentForGlobalCustomer($customer, $localCustomer, $customerApp, $payment, $input, $gatewayInput);

            }
        }

        if ($payment->isEmi() === true)
        {
            $cardNumber = $gatewayInput['card']['number'];

            $emiDuration = $input['emi_duration'];

            // Using array to pass card number due to https://razorpay.atlassian.net/browse/CARD-760
            $cardNumberArray = [
                'number' => $cardNumber,
            ];

            $gatewayInput['emi_plan'] = $this->setBankAndEmiPlanDetails($payment, $cardNumberArray, $emiDuration);
        }

        if ($payment->isCardlessEmi() === true)
        {
            $input = Customer\Validator::validateAndParseContactInInput($input);

            if (Payment\Gateway::isCardlessEmiPlanValidationApplicable($input, $payment,$this->mode) === true)
            {
                $gatewayInput['gateway'] = [
                    'emi_duration' => $input['emi_duration']
                ];

                $merchantId = $payment->getMerchantId();

                $contact = $input['contact'];

                if (isset($input['payment_id']) === true)
                {
                    $paymentIdString = '_' . $input['payment_id'];
                }
                else
                {
                    $paymentIdString = '';
                }

                $provider = $input[Payment\Entity::PROVIDER];

                if (in_array($provider, CardlessEmi::getCardlessEmiDirectAquirers()) === false)
                {
                    $provider = CardlessEmi::getProviderForBank($provider);
                }

                $cacheKey = strtoupper($provider) . '_' . $contact . '_' . $merchantId . $paymentIdString;

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
        }

        // in case of google_pay, payment will be methodless
        // so adding this condition to run this block,
        // if upi method supported on Gpay
        if ($payment->isUpi() === true or ($payment->isGooglePayMethodSupported(Method::UPI)))
        {
            $this->setGatewayInputForUpi($input, $gatewayInput);

            // Set the upi expiry time if it is set, will be persisted in the upi metadata.
            if (isset($gatewayInput[Payment\Method::UPI][UpiMetadata\Entity::EXPIRY_TIME]) === true)
            {
                $input[Method::UPI][UpiMetadata\Entity::EXPIRY_TIME] = $gatewayInput[Method::UPI][UpiMetadata\Entity::EXPIRY_TIME];
            }

            if ($this->isFlowIntent($gatewayInput) === false)
            {
                if (empty($payment->getVpa()) === true)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'The vpa field is required when method is upi.');
                }

                $this->validateUpiPspIsAllowed($payment);
            }
            else if ($this->isInApp($input) === true and $merchant->getMethods()->isInAppEnabled() !== true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Merchant is not authorized to UPI InApp payments');
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

            // Setting the upi metadata here, as it is required for settings multiple
            // payment params later
            $this->setUpiMetadataIfApplicable($payment, $input);

            // For certain MCCs, NPCI Has different restriction like
            // collect disabled, limited amount payment etc.
            $this->validateUpiMerchantCategory($payment, $input);
        }

        if ($payment->isWallet() === true)
        {
            $gatewayInput['wallet']['flow'] = $input['_']['flow'] ?? null;
        }

        if ($payment->isAeps() === true)
        {
            $this->setGatewayInputForAeps($input, $gatewayInput);
        }

        if ($payment->isMethodCardOrEmi() === true)
        {
            $gatewayInput['iin'] = $this->getIinDetails($payment);

            if ($payment->isVisaSafeClickPayment() === true)
            {
                $gatewayInput['application'] = $input['application'];
                $gatewayInput['authentication'] = $input['authentication'];
            }
        }

        $this->validateRecurringAndPreferredRecurring($payment, $input);

        $payment->setInternational();

        if (($this->subscription === null) or ($this->subscription->isExternal() === false))
        {
            $this->setRecurringType($payment, $input, $gatewayInput);
        }

        // select token's terminal id only for upi autopay subsequent debits
        $this->setSelectedTerminalsIdsForAutoDebit($payment, $payment->getGlobalOrLocalTokenEntity(), $gatewayInput);

        $this->setPreferredAuthIfApplicable($payment);

        // this needs to be done after we have card entity as we need to know if card is debit or credit
        $this->validateForMaxAmount($input, $payment);

        $this->setChargeAccountMerchantIfApplicable($input, $gatewayInput);

        // Not doing inside above mentioned UPI condition because Recurring data is set after that logic
        $this->modifyRecurringForUpiIfApplicable($payment, $input, $gatewayInput);
    }

    protected function isTokenInteroperabilityAllowed($customer , $payment , $input)
    {
        $partnerMerchantId = $this->app['basicauth']->getPartnerMerchantId();
        if ((($customer !== null) and
                ($partnerMerchantId !== null) and
                ($customer->getMerchantId() === $partnerMerchantId)) and
            (($payment->isMethodCardOrEmi() === true and
                    isset($input['recurring']) === true) or
                $payment->isMethodCardOrEmi() === false ))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Token Interoperability is not supported on this merchant.');
        }
    }

    protected function usePartnerMerchantForTokenInteroperabilityIfApplicable(Payment\Entity $payment, $input ):bool
    {
        $partnerMerchantId = $this->app['basicauth']->getPartnerMerchantId();

        if (($partnerMerchantId !== null) and
            ($payment->isMethodCardOrEmi() === true) and
            (isset($input['recurring']) === false) and
            (isset($input[Payment\Entity::CUSTOMER_ID]) === true))
        {
          return true;
        }
        return false;
    }

    protected function setChargeAccountMerchantIfApplicable($input, & $gatewayInput)
    {
        if (empty($input[Payment\Entity::CHARGE_ACCOUNT]) === true)
        {
            return;
        }

        $terminal = $this->repo->terminal->
                        findMerchantIdByGatewayMerchantID($input[Payment\Entity::CHARGE_ACCOUNT]);

        if ($terminal !== null)
        {
            $input[Payment\Entity::CHARGE_ACCOUNT] = $terminal->getMerchantId();
        }

        $merchant = $this->repo->merchant->find($input[Payment\Entity::CHARGE_ACCOUNT]);

        if ($merchant === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_CHARGE_ACCOUNT,
                null,
                [
                    'charge_account'   => $input[Payment\Entity::CHARGE_ACCOUNT],
                ]);
        }

        $gatewayInput[Payment\Entity::CHARGE_ACCOUNT_MERCHANT] = $merchant;
    }

    protected function createUpiMandateForSubscriptionIfApplicable(Customer\Entity $customer,$input ,$payment)
    {

        if(($input['method'] === PaymentConstants::UPI) and ($this->subscription!==null) and ($this->subscription->status === PaymentConstants::CREATED))
        {
            $input[Payment\Entity::CUSTOMER_ID] = $customer->getPublicId();

            $upitoken = [
                'max_amount'        => $this->subscription->getCurrentInvoiceAmount(),
                'frequency'         => $this->subscription->schedule['period'],
                'recurring_type'    => 'before',
                'recurring_value'   => $this->subscription->schedule['anchor'],
                'start_time'        => Carbon::now()->addMinute(1)->getTimestamp(),
                'end_time'          => $this->subscription->getEndAt(),
            ];

            $this->trace->info(
                TraceCode::UPI_MANDATE_SUBSCRIPTION_CUSTOMER_MAP,
                [
                    'customer_id'    => $customer->getPublicId(),
                    'subscriptionId' => $this->subscription->getId(),
                ]);


                $this->upiMandate->setCustomerId($customer->getId());

                $this->repo->saveOrFail($this->upiMandate);
        }
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

        if (($this->merchant->isFeatureEnabled(Feature\Constants::OTP_AUTH_DEFAULT) === true) or
            ((($this->merchant->isHeadlessEnabled() === true) or
            ($this->merchant->isIvrEnabled() === true)) and
            ($this->app['basicauth']->isDirectAuth() === true)))
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

            // replacing "$payment->card" with "$payment->localToken->card" because in the case of tokenisation
            // new card entity gets associated with the payment which has different card number
            $card = $payment->card;

            if((empty($payment->localToken) === false) and
               (empty($payment->localToken->card) === false) and
               ($payment->localToken->card->isRzpSavedCard() === false))
            {
                $card = $payment->localToken->card;
            }

            if (($payment->isCard() === true) and
                ($payment->hasCard() === true) and
                ($card->isRecurringSupportedOnTokenIINIfApplicable(true, $payment->hasSubscription()) === true))
            {
                $recurring = true;
            }

            $payment->setRecurring($recurring);
        }
        else if ($this->isAutoRecurring($input) === true)
        {
            // replacing "$payment->card" with "$payment->localToken->card" because in the case of tokenisation
            // new card entity gets associated with the payment which has different card number
            $card = $payment->card;

            if((empty($payment->localToken) === false) and
               (empty($payment->localToken->card) === false) and
               ($payment->localToken->card->isRzpSavedCard() === false))
            {
                $card = $payment->localToken->card;
            }

            if (($payment->isCard() === true) and
                ($payment->hasCard() === true) and
                ($card->isRecurringSupportedOnTokenIINIfApplicable($payment->isRecurringTypeInitial(), $payment->hasSubscription()) === true))
            {
                $payment->setRecurring(true);
            }
            else
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_AUTO_RECURRING_NOT_SUPPORTED_ON_IIN);
            }
        }
    }

    protected function getRecurringTypeFromToken(Payment\Entity $payment, Token\Entity $token, array $input)
    {
        if ($payment->isRecurring() === true)
        {
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

            if (($token === null) and
                ($input['recurring'] === Payment\RecurringType::AUTO))
            {
                $type = Payment\RecurringType::AUTO;
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

        return $type;
    }

    protected function setRecurringType(Payment\Entity $payment, array $input, & $gatewayInput)
    {
        $type = null;
        $token = null;

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

            if (($token === null) and
                ($input['recurring'] === Payment\RecurringType::AUTO))
            {
                $type = Payment\RecurringType::AUTO;
            }

            if ($payment->hasCardMandateNotification() === true)
            {
                $type = Payment\RecurringType::AUTO;
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
        $createdAt = $payment->getCreatedAt();

        $minAutoRefundTime = $createdAt + Merchant\Entity::MIN_AUTO_REFUND_DELAY;

        $merchantAutoRefundTime = $createdAt + $payment->merchant->getAutoRefundDelay();

        $reason = sprintf(PaymentConstants::MERCHANT_AUTO_REFUND_DELAY,$payment->merchant->getAutoRefundDelay());

        $merchantAutoRefundTime = max($minAutoRefundTime, $merchantAutoRefundTime);

        if ($payment->isEmandate() === true)
        {
            $emandateAutoRefundTime = $createdAt + Merchant\Entity::AUTO_REFUND_DELAY_FOR_EMANDATE;

            $merchantAutoRefundTime = $emandateAutoRefundTime;

            $reason = sprintf(PaymentConstants::REFUND_AT_FOR_EMANDATE_PAYMENT,Merchant\Entity::AUTO_REFUND_DELAY_FOR_EMANDATE);
        }
        else if ($payment->isNach() === true)
        {
            $merchantAutoRefundTime = $createdAt + Merchant\Entity::AUTO_REFUND_DELAY_FOR_NACH;

            $reason = sprintf(PaymentConstants::REFUND_AT_FOR_NACH_PAYMENT,Merchant\Entity::AUTO_REFUND_DELAY_FOR_NACH);
        }
        else if ($payment->isEzetap() === true)
        {
            $merchantAutoRefundTime = null;

            $reason = "Auto refunds has been disabled for ezetap payments";
        }
        else if ($payment->isUpiOtm() === true)
        {
            $upiMetadata = $payment->getUpiMetadata();

            // In case, the payment is OTM, we need to set the refund_at on based of OTM end_time.
            // So we fetch upi_metadata , and add necessary/default delay to the end_time after
            // which payment can be refunded.
            if (($upiMetadata instanceof UpiMetadata\Entity) and
                ($upiMetadata->isOtm() === true))
            {
                $merchantAutoRefundTime = $upiMetadata->getEndTime() + $payment->merchant->getAutoRefundDelay();

                $reason = sprintf(PaymentConstants::MERCHANT_AUTO_REFUND_DELAY,$payment->merchant->getAutoRefundDelay());
            }
        }
        else if($payment->isCorporateMakerCheckerNetbanking() === true and
            $payment->merchant->isFeatureEnabled(Feature\Constants::NETBANKING_CORPORATE_DELAY_REFUND) === true )
        {
//            This block is only executed only in this 2 conditions met
//                  1. If the transaction is a NETBANKING_CORPORATE Maker-Checker flow transaction = true
//                  2. If the merchant has enabled the feature 'nb_corporate_delay_refund'
//
//             The feature is only enable for the banks have corporate maker checker flow.
//             Else it shouldn't be applicable.

            // $autoRefundDelay is in minutes
            $autoRefundDelay = $this->getNetBankingAutoRefundDelay($payment->getMerchantId());

            if ($autoRefundDelay !== -1)
            {
                $merchantAutoRefundTime = $createdAt + $autoRefundDelay * 60;
            }
            else
            {
                $merchantAutoRefundTime = $createdAt + Merchant\Entity::AUTO_REFUND_DELAY_FOR_NETBANKING_CORPORATE;
            }

            $reason = sprintf(PaymentConstants::MERCHANT_AUTO_REFUND_DELAY_FOR_NETBANKING_CORPORATE,Merchant\Entity::AUTO_REFUND_DELAY_FOR_NETBANKING_CORPORATE);
        }

        $payment->setRefundAt($merchantAutoRefundTime);

        $properties = [
            "auto_refund_epoch" => $merchantAutoRefundTime,
            "reason" => $reason,
        ];

        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_AUTO_REFUND_DATE_SET, $payment, null, [], $properties);
    }

    protected function getNetBankingAutoRefundDelay($merchantId)
    {
        try
        {
            $res = (new NetbankingConfig\Service())->fetchNetbankingConfigs([NetbankingConfig\Constants::MERCHANT_ID => $merchantId]);

            if ((isset($res[NetbankingConfig\Constants::AUTO_REFUND_OFFSET]) === true) and
                ($res[NetbankingConfig\Constants::AUTO_REFUND_OFFSET] !== 0))
            {
                return $res[NetbankingConfig\Constants::AUTO_REFUND_OFFSET];
            }
            else
            {
                return -1;
            }

        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex, null, TraceCode::NETBANKING_CONFIG_FETCH_ERROR, [$merchantId]);

            return -1;
        }

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
                    (empty($input[Payment\Entity::APP_TOKEN]) === true) and
                    ($input[Payment\Entity::METHOD] !== 'upi'))
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
        $gatewayInput['upi']['flow'] = $this->getUpiFlow($input) ?? null;

        // For Gpay UPI flow will behave like intent
        if ($this->payment->isGooglePay())
        {
            $gatewayInput['upi']['flow'] = Payment\Flow::INTENT;
        }

        if ($this->isFlowIntent($gatewayInput) === false)
        {
            $gatewayInput['upi']['expiry_time'] = $this->getUpiExpiryTime($input) ?? Processor::UPI_COLLECT_EXPIRY;
        }

        $gatewayInput['upi'][UpiMetadata\Entity::TYPE] = $this->getUpiType($input);

        if ($this->isOtmPayment($input) === true)
        {
            $gatewayInput['upi'][UpiMetadata\Entity::START_TIME] = $this->getUpiStartTime($input);
            $gatewayInput['upi'][UpiMetadata\Entity::END_TIME]   = $this->getUpiEndTime($input);
        }
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

        if (($this->app['rzp.mode'] === 'test') and
            (empty($input[Payment\Entity::TOKEN]) === false) and
            ($this->app['basicauth']->isPrivateAuth() === true) and
            ($payment->merchant->isFeatureEnabled(Feature\Constants::NETWORK_TOKENIZATION) === true))
        {
            $token = $this->repo->token->getByPublicIdAndMerchant($input[Payment\Entity::TOKEN], $this->merchant);

            if ($token != null)
            {
                $payment->localToken()->associate($token);

                $gatewayInput['card'] = $this->associateAndGetCardArrayForSavedToken($token, $input, $payment);

                return;
            }
        }

        // No card saving, normal simple flow
        if ($payment->isMethodCardOrEmi() and
            ($payment->isGooglePayCard() === false))
        {
            $payment->setRecurring(false);

            $vault = $payment->shouldSaveCard();

            $gatewayInput['card'] = $this->createCardEntity($input['card'], $vault, $this->merchant, $input);

            $payment->setSave(false);

        }
    }

    // process the s2s saved card payment without customer_id
    protected function preProcessPaymentWithoutCustomer($payment, array & $input, array & $gatewayInput)
    {
        $saveMethod = $payment->getSave();

        if (($payment->isMethodCardOrEmi() === false) or
            ($this->app['basicauth']->isPrivateAuth() === false) or
            ((empty($input[Payment\Entity::TOKEN]) === true) && ($saveMethod === false)))
        {
            $this->preProcessPaymentWithoutSaving($payment, $input, $gatewayInput);
            return;
        }

        if (empty($input[Payment\Entity::TOKEN]) === true)
        {
            $this->preProcessPaymentFromUserDataLocal($payment, $input, $gatewayInput, null);
        }
        else
        {
            $this->preProcessPaymentFromSavedMethodLocal( $payment, $input, $gatewayInput, null);
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
            $this->preProcessPaymentFromUserDataLocal($payment, $input, $gatewayInput, $customer);
        }
        else
        {
            $this->preProcessPaymentFromSavedMethodLocal($payment, $input, $gatewayInput, $customer);
        }
    }

    protected function preProcessPaymentForSubscriptionWithoutCustomer(Payment\Entity $payment,
                                                         array & $input,
                                                         array & $gatewayInput)
    {
        //
        // if token is set, payment is either from a saved card or is second recurring
        // else, the card needs to be saved or need to mark the payment as recurring (first recurring)
        //
        if (empty($input[Payment\Entity::TOKEN]) === true)
        {
            $token = null;

            // create local saved card and link to payment
            if (($payment->isCardRecurring() === true) and ($payment->isGooglePayCard() === false))
            {
                $gatewayInput['card'] = $this->createCardEntity($input['card'], true, $payment->merchant, $input);

                $savedLocalCard = $payment->card;

                // save local saved card for local customer
                $token = $this->savePaymentMethodForSubscription($payment, $savedLocalCard->getId(), $input);
            }
            else if ($payment->isUpiRecurring() === true)
            {
                $token = $this->savePaymentMethodForSubscription($payment, null, $input);

                // If payment is upi recurring, we will update the mandate entity with the token id. We have already
                // validated that for upi recurring, the order has upi mandate entity linked.
                if ($token !== null)
                {
                    $upiMandate = $this->updateUpiMandateEntity($token, $payment);

                    $gatewayInput['upi_mandate'] = $upiMandate->toArray();
                }
            }
            else if ($payment->isEmandate() === true)
            {
                $token = $this->savePaymentMethodForSubscription($payment, null, $input);
            }

            if ($token !== null)
            {
                $this->payment->localToken()->associate($token);
            }
        }
        else
        {
            $tokenId = $input[Payment\Entity::TOKEN];

            $token = (new Token\Core)->getByTokenIdAndSubscriptionId($tokenId, $payment->getSubscriptionId());

            if ($payment->isCardRecurring() === true)
            {
                $payment->localToken()->associate($token);

                $gatewayInput['card'] = $this->associateAndGetCardArrayForSavedToken($token, $input, $payment);
            }
            else if ($payment->isUpiRecurring() === true)
            {
                $payment->localToken()->associate($token);

                $vpa = $token->vpa;

                $payment->setVpa($vpa->getAddress());
            }
            else if ($payment->isEmandate() === true)
            {
                $payment->localToken()->associate($token);
            }
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
            // Add new global saved card flow
            // Does processing like creating card entity, saving card if passed in the input, etc..
            $this->preProcessPaymentFromUserDataGlobal($customer, $payment, $input, $gatewayInput);
        }
        else
        {
            /**
             * Existing global saved card flow
             * 1. This flow is reached for global token
             * 2. and Dual vault token(Global customer local token)  as well
             */
            $this->preProcessPaymentFromSavedMethodGlobal($customer, $payment, $input, $gatewayInput);
        }
    }

    protected function preProcessPaymentFromSavedMethodLocal(Payment\Entity $payment,
                                                             array & $input,
                                                             array & $gatewayInput,
                                                             Customer\Entity $customer = null)
    {
        $this->trace->info(
            TraceCode::PAYMENT_PROCESS_FROM_SAVED_LOCAL,
            [
                'token_id' => $input[Payment\Entity::TOKEN]
            ]);

        $tokenId = $input[Payment\Entity::TOKEN];

        if ($customer !== null)
        {
            $token = (new Token\Core)->getByTokenIdAndCustomer($tokenId, $customer);
        }
        else
        {
            $token = (new Token\Core)->getByTokenIdAndMerchant($tokenId, $payment->merchant);
        }

        if ($payment->isMethodCardOrEmi() === true)
        {
            if (($payment->isRequiredToCreateNewTokenAlways($token, $this->isPreferredRecurring($input)) === true) and
                ($this->getRecurringTypeFromToken($payment, $token, $input) === Payment\RecurringType::INITIAL))
            {
                if (isset($token) && isset($token->card) &&
                    $token->card->isRuPay())
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'Mandate registrations through tokenised card is not allowed for Rupay. Please register using the full card number.');
                }

                $token = (new Token\Core)->cloneToken($token);
            }

            $payment->localToken()->associate($token);

            $gatewayInput['card'] = $this->associateAndGetCardArrayForSavedToken($token, $input, $payment);
        }
        else if ($payment->isEmandate() === true)
        {
            $payment->setBank($token->getBank());

            $payment->localToken()->associate($token);
        }
        else if ($payment->isUpiRecurring() === true)
        {
            $payment->localToken()->associate($token);

            $vpa = $token->vpa;

            $payment->setVpa($vpa->getAddress());
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

    protected function setSelectedTerminalsIdsForAutoDebit(Payment\Entity $payment,
                                                           ?Token\Entity $token,
                                                           & $gatewayInput)
    {
        if((($payment->isUpiAutoRecurring() === true) or
                (($payment->isCardAutoRecurring() === true) and
                    ($payment->card->isRuPay() === true))) and
                ($token !== null))
        {
            $this->trace->info(
                TraceCode::RECURRING_SET_TERMINAL_FROM_TOKEN,
                [
                    'token_id'      => $token->getId(),
                    'terminal_id'   => $token->getTerminalId(),
                    'payment_id'    => $payment->getId(),
                ]);

            $gatewayInput['selected_terminals_ids'] = [$token->getTerminalId()];
        }
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
            if ((!empty($input[Processor::USER_CONSENT_FOR_TOKENISATION])) && $token->isGlobal())
            {
                // As per RBI guidelines use of global card tokens is not allowed.
                // Payment flow should newer reach here.
                $this->trace->warning(TraceCode::GLOBAL_CARD_TOKEN_PAYMENT_SHOULD_NOT_BE_ALLOWED, [
                    'token_id' => $token->getId(),
                    'payment_id' => $payment->getId(),
                ]);

                $token = (new Token\Core())->createLocalTokenFromGlobalToken($token, $this->merchant,$payment->getGateway());
            }

            // For global customer local token payments
            if ($token->isLocal())
            {
                $payment->localToken()->associate($token);

                $gatewayInput['card'] = $this->associateAndGetCardArrayForSavedToken($token, $input, $payment);

                return;
            }

            // As per RBI guidelines use of global card tokens is not allowed.
            // Payment flow should newer reach here.
            $this->trace->warning(TraceCode::GLOBAL_CARD_TOKEN_PAYMENT_SHOULD_NOT_BE_ALLOWED, [
                'token_id' => $token->getId(),
                'payment_id' => $payment->getId(),
            ]);

            $gatewayInput['card'] = $this->createCardEntityFromSavedToken($token, $input, $payment);

            $payment->globalToken()->associate($token);

            $payment->card->globalCard()->associate($token->card);

            $this->repo->saveOrFail($payment->card);

            return;
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

            $vpa = $token->vpa;

            $payment->setVpa($vpa->getAddress());
        }
        else if ($this->shouldSaveVpaForUpiPayments() === true)
        {
            $payment->globalToken()->associate($token);

            $vpa = $token->vpa;

            $payment->setVpa($vpa->getAddress());
        }
    }

    protected function preProcessPaymentFromUserDataLocal(Payment\Entity $payment,
                                                          array $input,
                                                          array & $gatewayInput,
                                                          Customer\Entity $customer = null)

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
            $this->savePaymentMethodLocal($payment, $input, $gatewayInput, $customer);
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

    protected function savePaymentMethodLocal(Payment\Entity $payment,
                                              array $input,
                                              array & $gatewayInput,
                                              Customer\Entity $customer = null)
    {
        $token = null;

        // create local saved card and link to payment
        if (($payment->isMethodCardOrEmi() === true) and ($payment->isGooglePayCard() === false))
        {
            $merchant = ($customer === null) ? $payment->merchant : $customer->merchant;

            // card id should belong to submerchant
            if ($this->usePartnerMerchantforTokenInteroperabilityIfApplicable($payment, $input) === true)
            {
                $merchant = $payment->merchant;
            }

            $gatewayInput['card'] = $this->createCardEntity($input['card'], true, $merchant, $input);

            $savedLocalCard = $payment->card;

            // save local saved card for local customer
            $token = $this->savePaymentMethod($payment, $customer, $savedLocalCard->getId(), $input);
        }
        else if ($payment->isEmandate() === true)
        {
            // save emandate bank locally for local customer
            $token = $this->savePaymentMethod($payment, $customer, null, $input);
        }
        else if (($payment->isUpiRecurring() === true) or ($this->shouldSaveVpaForUpiPayments() === true))
        {
            $token = $this->savePaymentMethod($payment, $customer, null, $input);

            // If payment is upi recurring, we will update the mandate entity with the token id. We have already
            // validated that for upi recurring, the order has upi mandate entity linked.
            if (($payment->isUpiRecurring() === true) and ($token !== null))
            {
                $upiMandate = $this->updateUpiMandateEntity($token, $payment);

                $gatewayInput['upi_mandate'] = $upiMandate->toArray();
            }
        }
        else if ($payment->isNach() === true)
        {
            $token = $this->savePaymentMethod($payment, $customer, null, $input);
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
            if ($customer->isGlobal())
            {
                /**
                 * create token with global customer, local merchant and associate token to payment
                 */
                $gatewayInput['card'] = $this->createCardEntity($input['card'], true, $this->merchant, $input);

                $savedLocalCard = $payment->card;

                /**
                 * changing the global customer's merchant to payment->merchant in memory temporarily,
                 * since we want to create token on global customer, payment->merchant
                 */
                $customer->merchant()->associate($payment->merchant);

                $token = $this->savePaymentMethod($payment, $customer, $savedLocalCard->getId(), $input);

                $customer->merchant()->associate($this->repo->merchant->getSharedAccount());

                $this->payment->localToken()->associate($token);

                return;
            }

            // create global saved card and link to payment
            $gatewayInput['card'] = $this->createCardEntity($input['card'], true, $customer->merchant, $input);

            $savedGlobalCard = $payment->card;

            // create merchant local card entity and link to payment
            $gatewayInput['card'] = $this->createCardEntity($input['card'], false, $this->merchant, $input);

            // link local card to global card entity
            $payment->card->globalCard()->associate($savedGlobalCard);

            $this->repo->saveOrFail($payment->card);

            // save global saved card for global customer
            $token = $this->savePaymentMethod($payment, $customer, $savedGlobalCard->getId(), $input);
        }
        else if ($payment->isEmandate() === true)
        {
            // save emandate bank token globally for global customer
            $token = $this->savePaymentMethod($payment, $customer, null, $input);
        }
        else if (($payment->isUpiRecurring() === true) or ($this->shouldSaveVpaForUpiPayments() === true))
        {
            $token = $this->savePaymentMethod($payment, $customer, null, $input);


            // If payment is upi recurring, we will update the mandate entity with the token id. We have already
            // validated that for upi recurring, the order has upi mandate entity linked.
            if (($payment->isUpiRecurring() === true) and ($token !== null))
            {
                $upiMandate = $this->updateUpiMandateEntity($token, $payment);

                $gatewayInput['upi_mandate'] = $upiMandate->toArray();
            }
        }
        if ($token !== null)
        {
            $this->payment->globalToken()->associate($token);
        }
    }

    protected function savePaymentMethod(
        Payment\Entity $payment, Customer\Entity $customer = null, $savedCardId = null, array $input = [])
    {
        if ($payment->isMethodCardOrEmi() === true)
        {
            // Check whether token exists in card entity
            $card = $payment->card;

            if (($card != null) and
                (empty($card->getVaultToken()) === true))
            {
                return null;
            }

        }

        $dummyProcessing = $input['dummy_payment'] ?? false;

        if ($dummyProcessing === true)
        {
            return null;
        }

        $customerId = null;
        $customerLocal = null;

        if ($customer !== null)
        {
            $customerId = $customer->getId();
            $customerLocal = $customer->isLocal();
        }

        $this->trace->info(
            TraceCode::PAYMENT_SAVE_METHOD,
            [
                'method'            => $payment->getMethod(),
                'payment_id'        => $payment->getId(),
                'merchant_id'       => $payment->merchant->getId(),
                'customer_id'       => $customerId,
                'local'             => $customerLocal,
                'card_id'           => $savedCardId,
                'auth_type'         => $payment->getAuthType(),
                'account_type'      => $input[Payment\Entity::BANK_ACCOUNT][Token\Entity::ACCOUNT_TYPE] ?? null,
                'ifsc'              => $input[Payment\Entity::BANK_ACCOUNT][Payment\Entity::IFSC] ?? null,
                'max_amount'        => $input[Payment\Entity::RECURRING_TOKEN][Payment\Entity::MAX_AMOUNT] ?? null,
                'expire_by'         => $input[Payment\Entity::RECURRING_TOKEN][Payment\Entity::EXPIRE_BY] ?? null
            ]);

        $saveMethodInput = [
            Token\Entity::METHOD => $payment->getMethod()
        ];

        $validateExisting = true;

        if ($payment->isMethodCardOrEmi())
        {
            $saveMethodInput[Token\Entity::METHOD] = Payment\Method::CARD;

            $saveMethodInput[Token\Entity::CARD_ID] = $savedCardId;

            $order = $payment->order;

            if ($order !== null)
            {
                $tokenRegistration = $order->getTokenRegistration();

                if ($tokenRegistration !== null)
                {
                    $inn = $payment->card->iinRelation;

                    $maxAmount = $tokenRegistration->getMaxAmount();

                    if ($maxAmount === null)
                    {
                        if (($inn !== null) and
                            (IIN\IIN::isDomesticBin($inn->getCountry(), $payment->merchant->getCountry())))
                        {
                            $maxAmount =  SubscriptionRegistration\Entity::CARD_MANDATE_DEFAULT_MAX_AMOUNT;
                        }
                        else
                        {
                            $maxAmount =  SubscriptionRegistration\Entity::DEFAULT_MAX_AMOUNT;
                        }
                    }

                    $saveMethodInput[Token\Entity::MAX_AMOUNT] = $maxAmount;

                    $saveMethodInput[Token\Entity::EXPIRED_AT] = $tokenRegistration->getExpireAt();

                    $saveMethodInput[Token\Entity::FREQUENCY] = $tokenRegistration->getFrequency() ??
                        SubscriptionRegistration\Entity::AS_PRESENTED;
                }
            }

            if ($payment->isRequiredToCreateNewTokenAlways(null, $this->isPreferredRecurring($input)) === true)
            {
                $validateExisting = false;
            }
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
        else if ($payment->isUpi() === true)
        {
            $saveMethodInput[Token\Entity::METHOD] = Payment\Method::UPI;

            if ($payment->isUpiIntentRecurring() === false)
            {
                $vpa = $this->createVpaEntity($input);
                $saveMethodInput[Token\Entity::VPA_ID] = $vpa[PaymentsUpi\Vpa\Entity::ID];
            }

            // These fields will be set for upi recurring payments. We dont need to have a check for recurring because
            // we are checking if the fields exist. If not values for these in token will be null.
            if ($this->upiMandate !== null)
            {
                $saveMethodInput[Token\Entity::MAX_AMOUNT] = $this->upiMandate->getMaxAmount() ?? null;
                $saveMethodInput[Token\Entity::EXPIRED_AT] = $this->upiMandate->getEndTime() ?? null;
                $saveMethodInput[Token\Entity::START_TIME] = $this->upiMandate->getStartTime() ?? null;
            }

            if ($payment->isUpiRecurring() and
               ($this->merchant->isTPVRequired() === true))
            {
                $order = $payment->getOrderAttribute();

                $saveMethodInput[Token\Entity::ACCOUNT_NUMBER] = $order->bankAccount->getAccountNumber() ?? null;

                $saveMethodInput[Token\Entity::IFSC] = $order->bankAccount->getIfscCode() ?? null;
            }
        }

        $token = null;

        // @codingStandardsIgnoreStart
        try
        {
            if ($customer !== null)
            {
                $token = (new Token\Core)->create($customer, $saveMethodInput, null, $validateExisting);
            }
            else
            {
                $token = (new Token\Core)->createWithoutCustomer($saveMethodInput, $payment->merchant, $card , $validateExisting);
            }

        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);
        }
        // @codingStandardsIgnoreEnd

        $meta =  [
            'metadata' => [
                'payment' => [
                    'id'    => $payment->getId(),
                    'local' => $token->isLocal()
                ]
            ],
            'read_key'  => array('payment.id'),
            'write_key' => 'payment.id'
        ];

        if($payment->hasCard() === true)
        {
            $card = $payment->card;

            $meta['metadata']['payment'] += [
                'card_iin_headless' => $card->isHeadLessOtp(),
                'card_network'      => $card->getNetwork(),
                'card_type'         => $card->getType(),
                'card_country'      => $card->getCountry(),
                'international'     => $payment->isInternational()
            ];
        }

        if ($payment->hasSubscription() === true)
        {
            $token->setSubscriptionId($payment->getSubscriptionId());

            $token->saveOrFail();
        }

        $this->app['diag']->trackPaymentEventV2(
            EventCode::PAYMENT_CARDSAVING_PROCESSED,
            $payment,
            null,
            $meta,
            [
                'local' => $token->isLocal()
            ]);

        return $token;
    }

    protected function savePaymentMethodForSubscription(
        Payment\Entity $payment, $savedCardId = null, array $input = []): Token\Entity
    {
        $this->trace->info(
            TraceCode::PAYMENT_SAVE_METHOD,
            [
                'method'            => $payment->getMethod(),
                'payment_id'        => $payment->getId(),
                'merchant_id'       => $payment->merchant->getId(),
                'card_id'           => $savedCardId,
                'auth_type'         => $payment->getAuthType(),
                'account_type'      => $input[Payment\Entity::BANK_ACCOUNT][Token\Entity::ACCOUNT_TYPE] ?? null,
                'ifsc'              => $input[Payment\Entity::BANK_ACCOUNT][Payment\Entity::IFSC] ?? null,
                'max_amount'        => $input[Payment\Entity::RECURRING_TOKEN][Payment\Entity::MAX_AMOUNT] ?? null,
                'expire_by'         => $input[Payment\Entity::RECURRING_TOKEN][Payment\Entity::EXPIRE_BY] ?? null
            ]);

        $saveMethodInput = [
            Token\Entity::METHOD => $payment->getMethod()
        ];

        if ($payment->isMethod(Method::CARD))
        {
            $saveMethodInput[Token\Entity::METHOD] = Payment\Method::CARD;

            $saveMethodInput[Token\Entity::CARD_ID] = $savedCardId;
        }
        else if ($payment->isUpi() === true)
        {
            $saveMethodInput[Token\Entity::METHOD] = Payment\Method::UPI;

            if ($payment->isUpiIntentRecurring() === false)
            {
                $vpa = $this->createVpaEntity($input);
                $saveMethodInput[Token\Entity::VPA_ID] = $vpa[PaymentsUpi\Vpa\Entity::ID];
            }

            // These fields will be set for upi recurring payments. We dont need to have a check for recurring because
            // we are checking if the fields exist. If not values for these in token will be null.
            if ($this->upiMandate !== null)
            {
                $saveMethodInput[Token\Entity::MAX_AMOUNT] = $this->upiMandate->getMaxAmount() ?? null;
                $saveMethodInput[Token\Entity::EXPIRED_AT] = $this->upiMandate->getEndTime() ?? null;
                $saveMethodInput[Token\Entity::START_TIME] = $this->upiMandate->getStartTime() ?? null;
            }
        }
        else if ($payment->isEmandate() === true)
        {
            $saveMethodInput[Token\Entity::METHOD] = Payment\Method::EMANDATE;

            $saveMethodInput[Token\Entity::BANK] = $payment->getBank();

            $saveMethodInput[Token\Entity::AUTH_TYPE] = $payment->getAuthType();

            $saveMethodInput[Token\Entity::MAX_AMOUNT] =
                $input[Payment\Entity::RECURRING_TOKEN][Payment\Entity::MAX_AMOUNT];

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
                $input[Payment\Entity::RECURRING_TOKEN][Payment\Entity::EXPIRE_BY];
        }

        $token = (new Token\Core)->createForSubscription(
            $saveMethodInput,
            $payment->getSubscriptionId(),
            $payment->merchant
        );

        $meta =  [
            'metadata' => [
                'payment' => [
                    'id'    => $payment->getId(),
                    'local' => $token->isLocal(),
                ]
            ],
            'read_key'  => array('payment.id'),
            'write_key' => 'payment.id'
        ];

        if($payment->hasCard() === true)
        {
            $card = $payment->card;

            $meta['metadata']['payment'] += [
                'card_iin_headless' => $card->isHeadLessOtp(),
                'card_network'      => $card->getNetwork(),
                'card_type'         => $card->getType(),
                'card_country'      => $card->getCountry(),
                'international'     => $payment->isInternational()
            ];
        }

        $this->app['diag']->trackPaymentEventV2(
            EventCode::PAYMENT_CARDSAVING_PROCESSED,
            $payment,
            null,
            $meta,
            [
                'local' => $token->isLocal()
            ]);

        return $token;
    }

    protected function verifyPaymentMethodEnabled(Payment\Entity $payment)
    {
        $paymentMethods = $payment->fetchPaymentMethods();

        foreach ($paymentMethods as $paymentMethod)
        {
            $this->coreVerifyPaymentMethodEnabled($paymentMethod, $payment);
        }

        $this->validateGooglePayMethods($payment);
    }

    private function validateGooglePayMethods(Payment\Entity $payment)
    {
        if($payment->isGooglePay())
        {
            $googlePayMethods = $payment->getGooglePayMethods();

            $this->trace->info(
                TraceCode::GOOGLE_PAY_SUPPORTED_METHODS,
                [
                    'payment'          => $payment->getId(),
                    'googlePayMethods' => $googlePayMethods
                ]);

            if(empty($googlePayMethods))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_GOOGLE_PAY_METHODS_NOT_ENABLED_FOR_MERCHANT);
            }
        }
    }

    protected function verifyMethodInTestModeByMerchantAndTerminal($gateway, string $mid ):bool
    {
        if($this->mode == Mode::TEST)
        {
            $gateways = (new Methods\Core)->gatewayTerminalValidation;

            if(in_array($gateway, $gateways) === true)
            {
                $params = [
                    Merchant\Entity::MERCHANT_ID => $mid,
                    'gateway' => $gateway,
                    'status'  => 'activated',
                    'enabled' => 1,
                ];

                $terminals = $this->repo->terminal->getByParams($params);

                if($terminals->count() !== 0)
                {
                    return true;
                }
            }
        }
        return false;
    }

    protected function setBankAndEmiPlanDetails(Payment\Entity $payment, $cardNumberArray, int $emiDuration)
    {
        $iinEntity = $payment->card->iinRelation;

        // On custom checkouts, sometimes users are entering random cards for
        // which iin entity doesn't exist
        if ($iinEntity === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_EMI_NOT_AVAILABLE_ON_CARD);
        }

        IIN\IIN::validateEmiAvailableForCard($iinEntity, $cardNumberArray);

        $payment->setBank($iinEntity->getIssuer());

        $planType = $iinEntity->getType();

        // Set emi plan id
        $emiPlan = $this->getMerchantEmiPlans($iinEntity, $emiDuration, $payment->merchant, $planType);

        $payment->setEmiSubvention(Emi\Subvention::CUSTOMER);

        $payment->emiPlan()->associate($emiPlan);

        return $emiPlan->toArray();
    }

    protected function getIinDetails(Payment\Entity $payment)
    {
        if (isset($payment->card) === true)
        {
            $iinEntity = $payment->card->iinRelation;

            if (is_null($iinEntity) === false)
            {
                return $iinEntity->toArray();
            }
        }
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
            case $this->canRunAsyncPaymentFlow($payment, $request):

                return $this->getAsyncPaymentCreatedResponse($request, $payment);

            case $this->canRunAsyncIntentPaymentFlow($payment, $request):

                return $this->getIntentPaymentCreatedResponse($request, $payment);

            case $this->canRunOtpPaymentFlow($payment) or Payment\Gateway::canRunOtpFlowViaNbPlus($payment):

                return $this->getOtpPaymentCreatedResponse($request, $payment);

            case $this->canRunGooglePayPaymentFlow($payment):

                return $this->getGooglePayPaymentCreatedResponse($request, $payment);

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
            'method'        => $payment->getMethod(),
            'provider'      => $payment->getWallet(),
            'version'       => 1,
            'payment_id'    => $id,
            'gateway'       => $this->getEncryptedGatewayText($payment->getGateway()),
            'data'          => is_array($request) === true ? $request['data'] : $request,
            'request'       => [
                'url'    => $this->route->getUrlWithPublicAuthInQueryParam('payment_get_status', ['x_entity_id' => $id]),
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
            'method'        => $payment->getMethod(),
            'provider'      => $payment->getWallet(),
            'version'       => 1,
            'payment_id'    => $id,
            'gateway'       => $this->getEncryptedGatewayText($payment->getGateway()),
            'data'          => $request['data'],
            'request'       => [
                'url'    => $this->route->getUrlWithPublicAuthInQueryParam('payment_get_status', ['x_entity_id' => $id]),
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

    protected function updateTokenOnRecurringTokenisationFailure(Payment\Entity $payment, array $data)
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
                'data'            => $data,
            ]);

        $token->setRecurring(true);

        $createdAt = $payment->getCreatedAt();

        $token->setUsedAt($createdAt);

        $token->incrementUsedCount();

        $oldRecurringStatus = $token->getRecurringStatus();

        $token->setRecurringStatus(Token\RecurringStatus::REJECTED);

        $token->setRecurringFailureReason($data[Token\Entity::RECURRING_FAILURE_REASON]);

        $this->repo->saveOrFail($token);

        $this->eventTokenStatus($token, $oldRecurringStatus);
    }

    // Not Used
    protected function updateTokenOnCreatedIfRequired($payment, $response)
    {
        if ($payment->isUpiRecurring() === true)
        {
            $token = $payment->getGlobalOrLocalTokenEntity();

            if (empty($response['data']['token']['recurring_status']) === false)
            {
                $gatewayRecurringStatus = $response['data']['token']['recurring_status'];

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
            $this->setGatewayToken2IfApplicable($payment, $token, $data);

        }
        else if ($payment->isEmandate() === true)
        {
            $this->updateTokenOnAuthorizedForEmandateRecurring($token, $data, $payment);
        }
        else if($payment->isUpi() === true)
        {
            $token->setRecurring(true);
            $token->setRecurringStatus(Token\RecurringStatus::CONFIRMED);

            if (($payment->isFlowIntent() === true) and
                (is_null($token->getVpaId()) === true) and
                (empty($data[Entity::UPI][Entity::VPA]) === false))
            {
                $vpa = $this->createVpaEntity([
                    Entity::VPA => $data[Entity::UPI][Entity::VPA]
                ]);

                $token->setVpaId($vpa[PaymentsUpi\Vpa\Entity::ID]);
            }
        }

        // Not required as we only use terminals through
        // gateway_token, and not through token itself.
        // TODO: Remove this
        $token->terminal()->associate($payment->terminal);

        $this->createAndSetTerminalInGatewayToken($payment, $token);
    }


    /**
     * Sets gateway_token2 column of the token entity for international initial recurring payments routed through
     * checkout_dot_com gateway. The gateway_token2 value is required during payment of 2nd billing cycle onwards
     * @param Payment\Entity $payment
     * @param Token\Entity $token
     * @param array $data
     * @return void
     */
    public function setGatewayToken2IfApplicable(Payment\Entity $payment, Token\Entity $token, array $data)
    {
        if($payment->merchant->isFeatureEnabled(Features::RECURRING_CHECKOUT_DOT_COM) and
            $payment->isInternational() and $payment->isRecurringTypeInitial() === true and
            Gateway::isCPSGatewayToken2Required($payment->getGateway()) === true)
        {
            if(empty($data) === false and isset($data["gateway_token2"]) === true)
            {
                $token->setGatewayToken2($data["gateway_token2"]);
            }
        }
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
            if (($payment->isMethodCardOrEmi() === false) or ($payment->card->getVault() !== Card\Vault::RZP_ENCRYPTION))
            {
                return;
            }

            $input = [
                'payment_id' => $payment->getId(),
                'card_id'    => $payment->card->getId(),
                'token'      => $payment->card->getVaultToken(),
                'mode'       => $this->mode,
                'gateway'    => $payment->getGateway()
            ];

            $cardVault = (new Card\CardVault);

            $this->trace->info(
                TraceCode::VAULT_TOKEN_MIGRATION_REQUEST_INIT,
                [
                    'input' => $input
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

    protected function updateAndNotifyPaymentAuthenticated(array $data = [])
    {
        // Marking payments as authenticated for payments whose callback is split into authentication
        // and authorization leg.
        $updated = $this->updatePaymentAuthenticated($data);

        $this->migrateCardDataIfApplicable($this->payment);

        if ($updated === false)
        {
            return;
        }

        (new Payment\Metric)->pushAuthenticationMetrics($this->payment);
    }

    public function updatePaymentTokenDetails(Payment\Entity $payment, array $nrErrorCode)
    {
        try
        {
            if((isset($nrErrorCode["temporary_error_code"]) === true and $nrErrorCode["temporary_error_code"] !== null)
                or (isset($nrErrorCode["permanent_error_code"]) === true and $nrErrorCode["permanent_error_code"] !== null))
            {
                $this->updateEmandateToken($payment, $nrErrorCode);
            }
        }
        catch(\Throwable $ex)
        {
            $this->trace->traceException($ex, null, TraceCode::EMANDATE_NR_TOKEN_UPDATE_ERROR, [
                "merchant_id" => $payment->getMerchantId(),
                "payment_id"  => $payment->getId()
            ]);
        }
    }

    protected function updateAndNotifyPaymentAuthorized(array $data = [], bool $wasFailed = false)
    {
        // For UPI Initial Recurring payment, we will get two callbacks
        // The first callback will trigger the debit function on gateway
        // in that case, we can not mark the payment authorized.
        // Only when we receive the callback for debit call, we will authorize.
        if ($this->shouldSkipAuthorizeOnRecurringForUpi($this->payment, $data) === true)
        {
            // We need to update the mandate status if applicable
            $this->updateRecurringEntitiesForUpiIfApplicable($this->payment, $data, $wasFailed);
            return;
        }

        // For emandate registration payments, token confirmation may come
        // through webhooks. In such cases, if payment is already authorized
        // then we just need to update token recurring attributes.
        if ($this->shouldSkipAuthorizeOnRecurringForEmandate($this->payment, $data) === true)
        {
            $this->updateRecurringEntitiesForEmandateIfApplicable($this->payment, $data, $wasFailed);
            return;
        }

        try {
            $this->validateAvsResponseAndRemoveBillingAddressIfRequired($this->payment, $data);
        }
        catch (Exception\BadRequestException $e) {

            if(in_array($this->payment->getGateway(), Gateway::$internationalAVSVoidSupported,true))
            {
                // Updates payment entity to authorized and adds a transaction.
                $updated = $this->updatePaymentAuthorized($data, $wasFailed);

                $this->refundAuthorizedPayment($this->payment,[]);
                $this->payment->setError(ErrorCode::BAD_REQUEST_ERROR,Error\PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED_BY_AVS,ErrorCode::BAD_REQUEST_PAYMENT_FAILED_BY_AVS);
                $this->payment->saveOrFail();
                throw $e;
            }
            else{
                $this->updatePaymentOnExceptionAndThrow($e);
            }
        }

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

        $this->publishMessageToSqsBarricade($this->payment);

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

            if ($order->isExternal() === true)
            {
                $input = [Order\Entity::AUTHORIZED => true];

                $this->app['pg_router']->updateInternalOrder($input, $order->getId(),$order->getMerchantId(), true);
            }
            else
            {
                $this->repo->saveOrFail($order);
            }
        }
    }

    /**
     * This function is just meant for preparing the return value
     * after payment authorize processing and auto capturing, if applicable.
     *
     * @param Payment\Entity $payment
     * @param null $callbackData
     * @return array
     * @throws Exception\LogicException
     */
    protected function postPaymentAuthorizeProcessing(Payment\Entity $payment, $callbackData = null): array
    {
        $this->updateLateAuthFlag($payment);

        /**
         * Storing saved card consent
         * Doing this so that consent provided cards can be migrated to tokenised format
         * once integration with networks is completed.
         * This code will not be used after Dec 31st 2021, so needs to be removed then
         */
        $this->storeSavedCardConsentIfPresent($payment);

        //
        // Needs to be before capture, since disount amount
        // is used to decide whether to capture or not
        //
        $this->postPaymentAuthorizeOfferProcessing($payment);

        // Auto capture payment, if applicable
        $this->autoCapturePaymentIfApplicable($payment);

        $this->dispatchOrderFor1ccShopify($payment);

        $this->postPaymentAuthorizeSubscriptionProcessing($payment);

        $this->postPaymentAuthorizePaymentLinkProcessing($payment);

        $this->postPaymentAuthorizeSubscriptionRegistrationProcessing($payment);

        $this->migrateTokenIfApplicable($payment, $callbackData);

        $this->postTokenisationRecurringPaymentProcessingIfApplicable($payment);

        (new Payment\Core())->pushPaymentToKafkaForDeRegistrations($payment, microtime(true));

        return $this->processAuthorizeResponse($payment);
    }

    /**
     * Migrates a Razorpay saved card attached to the token to a network saved card.
     *
     * NOTE: Recurring payments should not go via sync flow because in recurring
     * payment flow if tokenisation fails we do not proceed with payment capture
     * and display the failure msg right away in the checkout.
     *
     * @param Payment\Entity $payment
     * @param array          $callbackData
     */
    protected function migrateTokenIfApplicable($payment, $callbackData): void
    {
        $startTime = microtime(true);

        $core = (new Token\Core());

        try
        {
            $token = $payment->getGlobalOrLocalTokenEntity();

            if ((empty($token) === true) or
                ($payment->isMethodCardOrEmi() === false))
            {
                $this->trace->info(TraceCode::TRACE_TOKEN_MIGRATION_FAILURE, [
                    'method'     => $payment->isMethodCardOrEmi(),
                    'token'      => $token->getId()
                ]);
                return;
            }

            // adding these check before checking feature flag constraint since we are updating error code there and we don;t want to update any error for international and bajaj since these cases are not supported for network tokenisation

            if ($token->card->isInternational() === true)
            {
                return ;
            }

            $networkCode = $token->card->getNetworkCode();

            if (in_array($networkCode, Card\Network::NETWORKS_SUPPORTING_TOKEN_PROVISIONING, true) === false)
            {
                    return ;
            }

            if (($token->merchant->isFeatureEnabled(Feature\Constants::NETWORK_TOKENIZATION_LIVE) === false) && ($token->merchant->isFeatureEnabled(Feature\Constants::NETWORK_TOKENIZATION) === false) && ($token->merchant->isFeatureEnabled(Feature\Constants::ISSUER_TOKENIZATION_LIVE) === false))
            {
                $this->trace->info(TraceCode::TRACE_TOKEN_MIGRATION_FAILURE, [
                    'featureEnabled'        => $token->merchant->isFeatureEnabled(Feature\Constants::NETWORK_TOKENIZATION_LIVE),
                    'featureEnabledIssuer'  => $token->merchant->isFeatureEnabled(Feature\Constants::ISSUER_TOKENIZATION_LIVE),
                    'token'      => $token->getId()
                ]);

                $errorCode = ErrorCode::BAD_REQUEST_MERCHANT_NOT_ONBOARDED_FOR_TOKENISATION;

                $core->updateTokenStatus($token->getId(), Token\Constants::FAILED, $errorCode);

                return;
            }

            if (($payment->isRecurring() === true) and
                ($token->getMethod() === Method::CARD) and
                ($token->card->isRzpSavedCard() === true))
            {
                $variant = $this->app->razorx->getTreatment($token->merchant->getId(),
                    Merchant\RazorxTreatment::RECURRING_TOKENISATION,
                    $this->mode);

                if (strtolower($variant) !== 'on')
                {
                    return;
                }

                $variant = $this->app->razorx->getTreatment($token->card->getIin(),
                    Merchant\RazorxTreatment::RECURRING_TOKENISATION,
                    $this->mode);

                if (strtolower($variant) !== 'on')
                {
                    return;
                }
            }

            if ($core->checkIfTokenisationApplicable($token) === false)
            {
                $this->trace->info(TraceCode::TRACE_TOKEN_MIGRATION_FAILURE, [
                    'tokenApplicable'     => 'false',
                     'token'              => $token->getId()
                ]);
                return;
            }

            if ($token->isGlobal() === true) {
                $this->trace->info(TraceCode::TRACE_TOKEN_MIGRATION_FAILURE, [
                    'isGlobal'     => $token->isGlobal(),
                    'token'      => $token->getId()
                ]);

                $core->updateTokenStatus($token->getId(), Token\Constants::FAILED);

                // Sync. provisioning of globals network tokens is controlled using a razorx contextramp experiment
                // to control the amount of traffic we send to the networks & gradually ramp it up.
                return;
            }

            [$authReferenceNumber, $isTokenizationAllowed] = $this->getAuthenticationReferenceNumber($callbackData);

            if ($token->card->isRupay() === true)
            {
                if ($isTokenizationAllowed === false or $authReferenceNumber === '')
                {
                    $this->trace->info(TraceCode::TRACE_TOKEN_MIGRATION_FAILURE, [
                        'authReferenceNumber'  => $authReferenceNumber,
                        'tokenizedAllowed'     => $isTokenizationAllowed,
                        'token'      => $token->getId()
                    ]);

                    $errorCode = ErrorCode::BAD_REQUEST_CARD_NOT_ELIGIBLE_FOR_TOKENISATION;

                    $core->updateTokenStatus($token->getId(), Token\Constants::FAILED, $errorCode);

                    return;
                }
            }

            $rupay_recurring = false;
            if (($payment->isRecurring() === true) &&
                ($token->getMethod() === Method::CARD) &&
                isset($token->card) &&
                ($token->card->isRuPay()))
            {
                $cardMandateId = $token->getCardMandateId();
                $cardMandate = $this->repo->card_mandate->findByIdAndMerchant($cardMandateId, $payment->merchant);

                $mandate_end_date = $cardMandate->getEndAt();

                $authorizationData = (new Payment\Service)->getAuthorizationEntity($payment->getPublicId());
                $notes = $authorizationData['notes'];
                $data = json_decode($notes, true);
                $mandate_id = $data['si_registration_id'];

                $authReferenceNumber = $payment->card->getReference4();
                $rupay_recurring = true;
            }

            $input['payment'] = $payment->toArrayGateway();
            $input['card'] = $payment->card->toArray();

            $this->setCardNumberAndCvv($input,$payment->card->toArray());

            $cardInput = [
                'cvv'                             => $input['card']['cvv'] ?? Card\Entity::getDummyCvv($payment->card->getNetworkCode()),
                'last4'                           => $input['card']['last4'] ?? "0000",
                'expiry_month'                    => $input['card']['expiry_month'] ?? "0",
                'expiry_year'                     => $input['card']['expiry_year'] ?? "9999",
                'emi'                             => $input['card']['emi'] ?? false,
                'iin'                             => $input['card']['iin'] ?? 0,
                'name'                            => $input['card']['name'] ?? null,
                'authentication_reference_number' => $authReferenceNumber,
                'mandate_id'                      => $mandate_id ?? null,
                'end_date'                        => $mandate_end_date ?? null,
                'rupay_recurring'                 => $rupay_recurring
            ];

            if ($payment['recurring'] === false){

                $asyncTokenisationJobId = "paymentmigrate";

                $core->updateTokenStatus($token->getId(), Token\Constants::INITIATED);

                SavedCardTokenisationJob::dispatch($this->mode, $token->getId(), $asyncTokenisationJobId,  $payment->getId());

                $this->trace->info(TraceCode::TRACE_TOKEN_DISPATCH_LOG, [
                    'tokenid'     =>  $token->getId(),
                    'async'       =>  $asyncTokenisationJobId,
                    'paymentId'    => $payment->getId()
                ]);
                return;
            }

            $core->migrateToTokenizedCard($token, $cardInput, $payment);

            (new Token\Metric())->pushMigrateMetrics($token, Metric::SUCCESS);

            (new Metric())->pushTokenHQResponseTimeMetrics($startTime, BaseMetric::SUCCESS, Token\Action::MIGRATE);
        }
        catch (\Throwable $e)
        {
            $this->trace->warning(TraceCode::VAULT_TOKEN_MIGRATION_DISPATCH_FAILED, [
                'error' => $e,
                'level' => Trace::WARNING,
                'payment_id' => $payment->getId()
                ]);

            (new Metric())->pushTokenHQResponseTimeMetrics($startTime, BaseMetric::FAILED, Token\Action::MIGRATE);
        }
    }

    protected function getAuthenticationReferenceNumber($callbackData)
    {
        $isTokenizationAllowed = false;

        $authReferenceNumber = '';

        if (isset($callbackData['additional_products_supported']) === true)
        {
            $additionalProductsSupportedArr = explode(",", $callbackData);

            if (in_array("05", $additionalProductsSupportedArr) === true)
            {
                $isTokenizationAllowed = true;
            }
        }

        if (isset($callbackData['authentication_reference_number']) === true)
        {
            $authReferenceNumber = $callbackData['authentication_reference_number'];
        }

        return [$isTokenizationAllowed, $authReferenceNumber];
    }

    protected function postPaymentAuthenticateProcessing(Payment\Entity $payment): array
    {
        return $this->processAuthenticateResponse($payment);
    }

    protected function processAuthenticateResponse(Payment\Entity $payment): array
    {
        $returnData = [
            'razorpay_payment_id' => $payment->getPublicId(),
        ];

        if ($payment->hasOrder() === true)
        {
            $this->fillReturnDataWithOrder($payment, $returnData);
        }

        if (($this->app['basicauth']->isPrivateAuth() === false) and
            ($payment->getCallbackUrl()))
        {
            $this->fillReturnRequestDataForMerchant($payment, $returnData);
        }

        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_RESPONSE_SENT, $payment);

        return $returnData;
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
        // This has been moved to eventsubscriber onPaymentAuthorized.
        // since this flow does not get called for late auth cases
        // Will be deleting this function in future.
    }

    protected function postTokenisationRecurringPaymentProcessingIfApplicable(Payment\Entity $payment)
    {
        try
        {
            if($payment->isTokenisationUnhappyFlowHandlingApplicable() === true)
            {
                $token = $payment->localToken;

                $card = $this->repo->card->fetchForToken($token);

                if ($card->isRzpSavedCard() === true)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_TOKENISATION_FAILED_FOR_RECURRING_CARD,null,
                        [
                            'payment_id'   => $payment->getPublicId(),
                            'method'       => $payment->getMethod(),
                            'token_id'     => $payment->getTokenId(),
                        ]
                    );
                }

                (new CardMandate\Core)->reportInitialPayment($payment);

                $cardMandate = $this->repo->card_mandate->findByIdAndMerchant($payment->localToken->getCardMandateId(), $payment->merchant);

                if($cardMandate->getStatus() !== CardMandate\Status::ACTIVE)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_FAILED_REPORTING_TO_MANDATE_HUB,null,
                        [
                            'payment_id'            => $payment->getPublicId(),
                            'method'                => $payment->getMethod(),
                            'card_mandate_id'       => $cardMandate->getId(),
                        ]
                    );
                }

                $this->updateTokenOnAuthorized($payment, []);

                (new CardMandate\Metric())->generateMetric(CardMandate\Metric::CARD_RECURRING_TOKENISATION_AND_HUB_UPDATES,
                    ['mandate_hub' => $cardMandate->getMandateHub()]);
            }
        }
        catch(\Exception $e)
        {
            $traceCode = TraceCode::CARD_MANDATE_REPORT_INITIAL_PAYMENT_FAILED;
            $data[Token\Entity::RECURRING_FAILURE_REASON] = Error\PublicErrorDescription::BAD_REQUEST_FAILED_REPORTING_TO_MANDATE_HUB;

            if ($e->getCode() === ErrorCode::BAD_REQUEST_TOKENISATION_FAILED_FOR_RECURRING_CARD)
            {
                $traceCode = TraceCode::FAILED_TO_TOKENISED_THE_RECURRING_CARD;
                $data[Token\Entity::RECURRING_FAILURE_REASON] = Error\PublicErrorDescription::BAD_REQUEST_TOKENISATION_FAILED_FOR_RECURRING_CARD;
            }

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                $traceCode,
                [
                    "message" => "failed in postTokenisationRecurringPaymentProcessingIfApplicable",
                    "paymentId" => $payment->getId(),
                ]);

            $this->updateTokenOnRecurringTokenisationFailure($payment, $data);

            $cardMandate = $this->repo->card_mandate->findByIdAndMerchant($payment->localToken->getCardMandateId(), $payment->merchant);

            (new CardMandate\Metric())->generateMetric(CardMandate\Metric::CARD_RECURRING_TOKENISATION_AND_HUB_UPDATES_ERROR,
                ['mandate_hub' => $cardMandate->getMandateHub()]);
        }
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
                    'method'                => $payment->getMethod()
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

        // based on experiment, refund request will be routed to Scrooge
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
        // based on experiment, refund request will be routed to Scrooge
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
            else if (($payment->hasOrder() === true) &&
                     ($payment->isUpiTransfer() === false)
            ) {
                // adding isUpiTransfer check because icici upi transfer callback happens in direct auth
                // this is a hack. other upi va callbacks might not need this check
                // because they might come under isProxyOrPrivilegeAuth check
                // but there's nothing to sign here and va doesnt care about the return response

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
                    ($payment->isFileBasedEmandateRegistrationPayment() === false) and
                    ($payment->isApiBasedEmandateAsyncPayment() === false) and
                    ($payment->isCod() === false))
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

        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_RESPONSE_SENT, $payment);

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
                                                Payment\Entity::METHOD          => $payment->getMethod(),
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
        // If accessed via keyless flow (public auth routes or direct auth routes like payment callback on UPI QR) and
        //    key doesn't exist, then skip calculating signatures.
        // Otherwise, we calculate signature with secret from either API keys or OAuth client or partner's dummy client.
        if (($this->ba->isPublicAuth() || $this->ba->isDirectAuth()) and
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

            if($this->payment->merchant->isFeatureEnabled(Feature\Constants::SILENT_REFUND_LATE_AUTH) === true)
            {
                return;
            }
        }

        if ($this->payment->hasInvoice() === true)
        {
            $event = Payment\Event::INVOICE_PAYMENT_AUTHORIZED;
        }

        $notifier = (new Notify($this->payment, true));

        $notifier->trigger($event);

        if($event === Payment\Event::AUTHORIZED)
        {
            $notifier->triggerSms();
        }

    }

    public function notifyMigratedCard($payment)
    {
        $this->payment = $payment;

        $this->notifyIfCardSaved();
    }

    protected function notifyIfCardSaved()
    {
        /** @var Payment\Entity $payment */
        $payment = $this->payment;

        $token = $payment->getGlobalOrLocalTokenEntity();

        $isGlobalCustomerToken = ((isset($token)) &&
            ($token->isGlobal() || $token->isLocalTokenOnGlobalCustomer()));

        if (($payment->isMethod(Payment\Method::CARD)) and
            ($payment->getSave() === true) and
            ($isGlobalCustomerToken === true) and
            ($payment->card->getVault() !== Card\Vault::RZP_ENCRYPTION))
        {
            $notifier = new Notify($this->payment);

            $trigger = Payment\Event::CARD_SAVED;

            $notifier->trigger($trigger);
        }
    }

    public function eventPaymentAuthorized()
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $this->payment,
        ];

        $this->app['events']->dispatch('api.payment.authorized', $eventPayload);
    }

    public function eventPaymentPending()
    {
        $payment = $this->payment;

        $eventPayload = [
            ApiEventSubscriber::MAIN => $payment
        ];

        $this->app['events']->dispatch('api.payment.pending', $eventPayload);
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

            $this->app['events']->dispatch($event, $eventPayload);
        }
    }

    protected function recordTerminalAudit(array $terminalData, Payment\Entity $payment, int $retryAttempts)
    {
        try
        {
            $log = [
                TerminalAnalytics\Constants::PAYMENT_ID    => $terminalData['payment_id'],
                TerminalAnalytics\Constants::TERMINAL_ID   => $terminalData['terminal_id']
            ];

            // convert difference to milliseconds to record as integer
            $responseTime = (int) (($terminalData['end'] - $terminalData['start']) * 1000);

            $log[TerminalAnalytics\Constants::TERMINAL_RESPONSE_TIME] = $responseTime;

            $log[TerminalAnalytics\Constants::PAYMENT_TYPE] = 1;

            $log[TerminalAnalytics\Constants::TERMINAL_STATUS] = 1;

            $errorCode = null;

            $errorMsg = null;

            if (isset($terminalData['exception']))
            {
                $e = $terminalData['exception'];

                $log[TerminalAnalytics\Constants::TERMINAL_STATUS] = 0;

                // we care about this exception, since its an indicator of
                // terminal failure
                $log[TerminalAnalytics\Constants::TERMINAL_STATUS_CODE] = $e->getError()->getHttpStatusCode();

                $log[TerminalAnalytics\Constants::TERMINAL_STATUS_MSG] = $e->getError()->getDescription();
            }

            $tStatus = $log[TerminalAnalytics\Constants::TERMINAL_STATUS];

            $terminalStatus = ($tStatus === 1) ? TraceCode::TERMINAL_SUCCESS : TraceCode::TERMINAL_FAILURE;

            $log['retry_attempt'] = $retryAttempts;

            $this->segment->trackPayment($payment, $terminalStatus, $log);
        }
        catch(\Exception $e)
        {
            $this->trace->error(
                TraceCode::RECORD_TERMINAL_AUDIT_FAILED,
                ['terminalData' => $terminalData]
            );

            $this->trace->traceException($e);
        }
    }

    protected function setAnalyticsLog(Payment\Entity $payment, $deviceId = null)
    {
        try
        {
            $paymentAnalytics = (new Analytics\Core)->create($payment, $deviceId);

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
        if ($payment->hasTerminal() === true and $payment->terminal->isIvr() === true)
        {
            if ($payment->getAuthType() === Payment\AuthType::_3DS)
            {
                return false;
            }

            return true;
        }

        // If the payment is card payment with headless browser flow then
        // we render the otp submission page to the user
        if (($payment->isMethodCardOrEmi() === true) and ($payment->isGooglePayCard() === false))
        {
            if ($payment->getAuthType() === Payment\AuthType::_3DS)
            {
                return false;
            }

            if ($this->canRunAxisTokenHQOTP($payment) === true)
            {
                return true;
            }

            if ($payment->card->iinRelation !== null)
            {
                if (empty($gatewayInput['auth_type']) === false)
                {
                    if (($gatewayInput['auth_type'] === Payment\AuthType::OTP) or
                        ($gatewayInput['auth_type'] === Payment\AuthType::IVR))
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

                if($this->canRunPaysecureOTP($payment) === true or ($this->canRunAxisTokenHQOTP($payment) === true) or ($this->canRunICICIOTP($payment) === true))
                {
                    return true;
                }

                if($this->canRunDebitEMIOTP($payment) === true)
                {
                    return true;
                }

                if ((in_array($payment->getGateway(), Payment\Gateway::$otpPostFormSubmitGateways, true) === true) and
                    ($payment->isEmi() === true))
                {
                    return true;
                }

                if ($payment->getAuthType() === Payment\AuthType::IVR)
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
            (($wallet === PayLater::ICICI)))
        {
            return true;
        }

        //When cps is 3 and nbplus can run OTP flow then we run OTP flow on nbplus with action name authorize.
        if(Payment\Gateway::canRunOtpFlowViaNbPlus($payment))
        {
            return false;
        }

        // Show the OTP page for Lazypay in Test Mode
        if ((isset($this->mode) === true) and ($this->mode === Mode::TEST) and ($payment->isPayLater() === true) and
            (($wallet === PayLater::LAZYPAY)))
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

        // For payments via external gateways like payu and ccavenue,
        // all wallets including power wallets do not support otp flow
        if (($payment->isWallet() === true) and
            (Payment\Gateway::isPowerWallet($wallet) === true) and
            ($payment->hasTerminal() === true) and
            (Payment\Gateway::isPowerWalletNotSupportedForGateway($payment->terminal->getGateway()) === true)) {
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

    protected function canRunPaysecureOTP(Payment\Entity $payment)
    {
        if (($payment->getGateway() === Payment\Gateway::PAYSECURE) and
            ($payment->getAuthType() === Payment\AuthType::OTP))
        {
            return true;
        }

        return false;
    }

    protected function canRunICICIOTP(Payment\Entity $payment)
    {
        if ($payment->getGateway() === Payment\Gateway::ICICI)
        {
            return true;
        }

        return false;
    }

    protected function canRunAxisTokenHQOTP(Payment\Entity $payment)
    {
        if (($payment->getGateway() === Payment\Gateway::AXIS_TOKENHQ) and
            ($payment->getAuthType() === Payment\AuthType::OTP))
        {
            return true;
        }

        return false;
    }

    protected function canRunDebitEMIOTP(Payment\Entity $payment)
    {

        if(in_array($payment->getGateway(),Payment\Gateway::$OtpSupportDebitEmiGateways) and $payment->getAuthType() === Payment\AuthType::OTP)
        {
            return true;
        }

        return false;
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
        if (($payment->merchant->isIvrEnabled() === true) and
            (is_null($payment->card) === false) and
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

    protected function canRunAsyncPaymentFlow($payment, $request)
    {
        if ((Payment\Method::supportsAsync($payment->getMethod()) === true) and
            (Payment\Gateway::supportsAsync($payment->getGateway()) === true) and
            ($payment->isAppCred() === false) and
            (($payment->getMetadata('flow') !== 'intent') or
             ($payment->getMetadata(Payment\Entity::UPI_PROVIDER, null) !== null)))
        {
            return true;
        }

        if (($payment->isAppCred() === true) and
            ($this->canRunAsyncPaymentFlowCred($payment, $request) === true))
        {

            return true;
        }

        return false;
    }

    protected function canRunAsyncIntentPaymentFlow($payment, $request)
    {
        if (($this->canRunAsyncIntentPaymentFlowUpi($payment) === true) or
            ($this->canRunAsyncPaymentFlowWallet($payment) === true) or
            ($this->canRunAsyncIntentPaymentFlowCred($payment, $request) === true))
        {
            return true;
        }

        return false;
    }

    protected function canRunAsyncIntentPaymentFlowCred($payment, $request)
    {
        if (($payment->isAppCred() === true) and
            ($payment->getGateway() === Payment\Gateway::CRED) and
            (empty($request['data']['intent_url']) === false))
        {
            return true;
        }

        return false;
    }

    protected function canRunAsyncPaymentFlowCred($payment, $request)
    {
        if (($payment->isAppCred() === true) and
            ($payment->getGateway() === Payment\Gateway::CRED) and
            (empty($request['data']['intent_url']) === true) and
            (empty($request['data']['vpa']) === false))
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

    protected function canRunGooglePayPaymentFlow($payment)
    {
        if ($payment->isGooglePayCard() === true or $payment->isGooglePay() === true)
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

        /*  if dummy_payment parameter is set, the flow is being called from
         *  dummyPrePaymentAuthorizeProcessing, In this case we don't need to create
         *  any card in database. Instead we return a card object.
         */
        $dummyProcessing = $input['dummy_payment'] ?? false;

        $this->setIsCVVOptionalFlagIfApplicable($cardInput, $input);

        $recurring = (($this->payment->isRecurring()) or
                      ($this->isPreferredRecurring($input)));

        $cardData = $cardCore->createAndReturnWithSensitiveData($cardInput, $merchant, $recurring, $dummyProcessing);

        $card = $cardCore->getCard();

        if ($card->isUnsupported())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED);
        }

        $this->payment->card()->associate($card);

        // commenting this code as card entity gets save in Card\Core
        // $this->repo->saveOrFail($card);

        return $cardData;

    }

    protected function createVpaEntity($input)
    {
        $vpaCore = new PaymentsUpi\Vpa\Core;

        $vpa = $vpaCore->firstOrCreate($input);

        return $vpa->toArray();
    }

    protected function updateUpiMandateEntity(Token\Entity $token, Payment\Entity $payment): \RZP\Models\UpiMandate\Entity
    {
        $this->upiMandate->setTokenId($token->getId());

        if($this->upiMandate['start_time'] < Carbon::now()->getTimestamp())
        {
            $this->upiMandate['start_time'] = Carbon::now()->getTimestamp();
        }

        $this->repo->saveOrFail($this->upiMandate);

        return $this->upiMandate;
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
            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CARDSAVING_INITIATED, $this->payment);
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
    protected function associateAndGetCardArrayForSavedToken(Token\Entity $token, array & $input, Payment\Entity $payment=null): array
    {
        $card = $this->repo->card->fetchForToken($token);

        if ($card->isRzpSavedCard() === false)
        {
            return $this->createCardEntityForTokenisedCard($token, $input, $payment);
        }

        $gateway = $input['payment']['gateway'] ?? null;

        $cardNumber = (new Card\CardVault)->getCardNumber($card->getVaultToken(),$card->toArray(),$gateway);

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

        protected function fetchCryptogramForDualToken($card, Payment\Entity $payment, $merchant = null) {

        $cryptogram = null;

        $cardEntity = $card;
        $cardEntity['trivia'] = '1';
        $cardEntity['iin'] = $cardEntity['iin'] === "" ? '000000' : $cardEntity['iin'];
        $payment->card()->associate($cardEntity);


        // make a call to smart router to get which terminal should be used to fetch cryptogram
        $this->selectedTerminals = (new TerminalProcessor)->getTerminalsForPayment($payment, null, null, $this->authenticationChannel);

        $payment->card()->dissociate();

        if (in_array($this->selectedTerminals[0]['gateway'], Payment\Gateway::TOKENISATION_CRYPTOGRAM_NOT_REQUIRED_GATEWAYS)) {
            // currently we only support axis terminals & it doesn't require cryptogram
            return $cryptogram;
        }else {
            $cryptogram = (new Card\CardVault)->fetchCryptogramForPayment($card->getVaultToken(), $merchant);
        }



        return $cryptogram;
    }

    protected function createCardForNetworkToken($card, $input, $payment, $merchant = null, $recurringTokenNumber = null)
    {
        if ($merchant === null){
            $merchant = $card->merchant;
        }

        $cryptogram = null;
        $cardVault = $card->getVault();
        if ($cardVault === Card\Vault::PROVIDERS || $cardVault == Card\Vault::AXIS){
            $cryptogram = $this->fetchCryptogramForDualToken($card, $payment, $merchant);
        }

        // for recurring subsequent calls we dont need cryptogram
        // we are storing tokenPAN in card_mandate table for recurring purposes. so need to make fetchcryptogram
        // $recurringTokenNumber is passed the value of TokenPAN in recurring use cases
        else if ($recurringTokenNumber === null && $card->getVault() !== Card\Vault::AXIS)
        {
            $cryptogram = (new Card\CardVault)->fetchCryptogramForPayment($card->getVaultToken(), $merchant);
        }

        $cardCore = new Card\Core;

        $cardInput = $cardCore->getCardInputFromCryptogram($cryptogram, $card, $input, $recurringTokenNumber);

        return $this->createCardEntity($cardInput, true, $this->merchant, $input);
    }

    /**
     * Creates card entity from tokenised card and associates it to payment
     * Calls experiment and creates card entity using either
     * 1. Tokenised card number (OR)
     * 2. Actual card number
     *
     * @param  Token\Entity  $token
     * @param $input
     * @return array
     * @throws Exception\BadRequestException
     */
    protected function createCardEntityForTokenisedCard(Token\Entity $token, $input, $payment): array
    {
        $card = $token->card;
        $this->logTokenisedCardPaymentRoutingInfo($token, false);

        // we are storing tokenPAN in card_mandate table for recurring purposes. so need to make fetchcryptogram
        if($token->isRecurring() and
           $token->getRecurringStatus() === Token\RecurringStatus::CONFIRMED)
        {
            $paymentProcessor = (new Processor($this->merchant));

            $paymentProcessor->setPayment($this->payment);

            return $paymentProcessor->createCardForNetworkTokenCardMandate($card, $token, [], $payment);
        }

        $merchant = $card->merchant;
        // using partner merchant for cryptogram api on token_interoperabilty
        $partnerMerchantId = $this->app['basicauth']->getPartnerMerchantId();

        if (($token->isRecurring() === false) and
            (empty($token->getCustomerId()) === false) and
            $partnerMerchantId !== null )
        {
            $merchant = $merchant->getFullManagedPartnerWithTokenInteroperabilityFeatureIfApplicable($merchant);
        }

        return $this->createCardForNetworkToken($card, $input, $payment, $merchant);
    }

    protected function logTokenisedCardPaymentRoutingInfo(Token\Entity $token, bool $isActualCard): void
    {
        $card = $token->card;

        $this->trace->info(TraceCode::TOKENISED_CARD_PAYMENT_ROUTING_INFO, [
            'paymentId'     => $this->payment->getId(),
            'tokenId'       => $token->getId(),
            'isGlobal'      => $token->isGlobal(),
            'routedThrough' => $isActualCard ? 'actualCard' : 'tokenisedCard',
            'cardInfo'      => [
                'issuer'    => $card->getIssuer(),
                'network'   => $card->getNetworkCode(),
                'type'      => $card->getType(),
            ],
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
    protected function createCardEntityFromSavedToken(Token\Entity $token, array & $input, $payment): array
    {
        $card = $this->repo->card->fetchForToken($token);

        if ($card->isRzpSavedCard() === false)
        {
            return $this->createCardEntityForTokenisedCard($token, $input, $payment);
        }

        $gateway = $input['payment']['gateway']?? null;

        $cardNumber = (new Card\CardVault)->getCardNumber($card->getVaultToken(),$card->toArray(),$gateway);

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

    /**
     * @throws Exception\BadRequestException
     */
    protected function verifyBankEnabled(Payment\Entity $payment)
    {
        $merchant = $payment->merchant;

        $merchantMethods = (new Methods\Core)->getMethods($merchant);

        if (($merchantMethods === null) or
            ($merchantMethods->isNetbankingEnabled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_NETBANKING_NOT_ENABLED_FOR_MERCHANT,null,
                [
                    'payment_id'   => $payment->getPublicId(),
                    'method'       => $payment->getMethod()
                ]
            );
        }

        $merchantBanks = ($merchantMethods === null) ? [] : $merchantMethods->getSupportedBanks();

        $merchantBanks = Netbanking::removeDefaultDisableBanks($merchantBanks);

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
                    'method'            => $payment->getMethod(),
                    'order_id'          => ($payment?->order) ? $payment->order->getPublicId(): null
                ]);
        }
    }

    protected function verifyWalletEnabled(Payment\Entity $payment)
    {
        $merchantMethods = $this->methods;

        $paymentWallet = $payment->getWallet();

        if ($this->verifyMethodInTestModeByMerchantAndTerminal($paymentWallet, $payment->merchant->getId()) === true)
        {
            return ;
        }

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

    protected function verifyUpiEnabled(Payment\Entity $payment)
    {
        $merchantMethods = $this->methods;

        if (($merchantMethods === null) or
            ($merchantMethods->isUPIEnabled() === false))
        {
            if ($payment->isGooglePay() === true)
            {
                $payment->unsetGooglePayMethod(Method::UPI);

                return;
            }

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_NOT_ENABLED_FOR_MERCHANT);
        }

        $this->checkAndValidateIfUpiSubTypeDisabled($merchantMethods, $payment);

    }

    protected function checkAndValidateIfUpiSubTypeDisabled($methods, Payment\Entity $payment)
    {
        $isIntentType = $payment->isFlowIntent();

        $upiProvider = $payment->getMetadata(Payment\Entity::UPI_PROVIDER); // if upiProvider is set, it is omnichannel

        if (($isIntentType === true) && ($methods->isUpiIntentEnabled() === false) && (in_array($upiProvider, Payment\UpiProvider::$omnichannelProviders) === false))
        {
            // unsetting upi method here as for gpay,
            // upi payment processed as intent flow
            if ($payment->isGooglePay() === true)
            {
                $payment->unsetGooglePayMethod(Method::UPI);

                return;
            }

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_INTENT_NOT_ENABLED_FOR_MERCHANT);
        }

        if ($payment->isGooglePay() === true)
        {
            return;
        }

        else if (!$isIntentType && ($methods->isUpiCollectEnabled() === false)
                && ($payment->getRecurringType() !== RecurringType::AUTO))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_NOT_ENABLED_FOR_MERCHANT);
        }
        else if ((in_array($upiProvider, Payment\UpiProvider::$omnichannelProviders) === true) && ($methods->isUpiCollectEnabled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_NOT_ENABLED_FOR_MERCHANT);
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

    protected function verifyOfflineEnabled()
    {
        $merchantMethods = $this->methods;

        if (($merchantMethods === null) or
            ($merchantMethods->isOfflineEnabled() === false))
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

    protected function verifyAppEnabled(Payment\Entity $payment)
    {
        $merchantMethods = $this->methods;

        if (($merchantMethods === null) or
            ($merchantMethods->isAppEnabled($payment->getWallet()) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_APP_NOT_ENABLED_FOR_MERCHANT);
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

    protected function verifyPayLaterEnabled(Payment\Entity $payment)
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

        $paylaterProviders = $merchantMethods->getEnabledPaylaterProviders();

        $wallet = $payment[Payment\Entity::WALLET];

        if(isset($wallet) === true and $wallet === Payment\Gateway::LAZYPAY and (isset($paylaterProviders[$wallet]) === false or
                $paylaterProviders[$wallet] == 0))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_INSTRUMENT_NOT_ENABLED);
        }

        $merchantWhitelistedForLazypay = (new MerchantCore())->isMerchantWhitelistedForLazypay($payment->merchant);

        if(isset($wallet) === true and $wallet === Payment\Gateway::LAZYPAY and !$merchantWhitelistedForLazypay)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_INSTRUMENT_NOT_ENABLED);
        }
    }

    /**
     * @throws Exception\BadRequestException
     */
    protected function verifyCoDEnabled(Payment\Entity $payment)
    {
        $methods = $this->methods;

        $reason = '';

        if (($methods === null) or
            ($methods->isCoDEnabled() === false))
        {
            $reason = 'method not enabled';
        }

        if ($payment->merchant->isRazorpayOrgId() === false)
        {
            $reason = 'cash on delivery not enabled for non razorpay org';
        }

        if ($payment->merchant->isFeeBearerPlatform() === false)
        {
            $reason = 'cash on delivery not supported for customer fee bearer';
        }

        if (empty($reason) === true)
        {
            return;
        }

        $this->trace->info(TraceCode::PAYMENT_COD_INELIGIBLE_REASON, [
            'reason' => $reason,
        ]);

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_PAYMENT_COD_NOT_ENABLED_FOR_MERCHANT);
    }

    protected function verifyFpxEnabled(Payment\Entity $payment)
    {
        if ($this->mode === Mode::TEST)
        {
            $merchantMethods = $this->methods;

            if (($merchantMethods === null) or
                ($merchantMethods->isFpxEnabled() === false))
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PAYMENT_METHOD_NOT_ALLOWED_IN_CONFIG);
            }
        }

        // FPX is not processed via Monolith in live mode
        else
        {
            throw new Exception\LogicException('Should not reach here.', null, ['payment_method' => $payment->getMethod()]);
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
            if ($payment->isGooglePay() === true)
            {
                $payment->unsetGooglePayMethod(Method::CARD);

                return;
            }

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_NOT_ENABLED_FOR_MERCHANT);
        }

        if ($payment->isGooglePayCard() === true || $payment->isGooglePay() === true)
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

    protected function validateLast4ForS2STokenisedEmiPayments(Payment\Entity $payment, array $input)
    {
        if ($payment->isMethodCardOrEmi() === false){
            return ;
        }

        $last4 = $input['card']['last4'] ?? '';

        if ( ($payment->getMethod() === Method::EMI) and  ($this->app['api.route']->isS2SPaymentRoute() === true) and isset($input['card'])=== true and  empty($input['card']['tokenised']) === false and empty($last4) === true)
        {

            $this->trace->info(
                TraceCode::S2S_TOKENISED_EMI_LAST4_VALIDATION,
                [
                    'payment_id'  => $payment->getId(),
                    'method'      => $payment->getMethod(),
                    'last4'       => $last4,
                    'tokenised'   => $input['card']['tokenised'],

                ]);

            throw new Exception\BadRequestValidationFailureException (
                'The last4 field is required when method is emi and tokenised is true'
            );
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

    protected function validateUpiMerchantCategory(Payment\Entity $payment, $input)
    {
        // If merchant category is not set, we can not run any validation
        if (empty($payment->merchant->getCategory()) === true)
        {
            return;
        }

        $config = new UpiMetadata\MccConfig((string) $payment->merchant->getCategory());

        if ($this->isFlowIntent($input) === true)
        {
            $config->validateIntentPayment($payment);
        }
        // The MCC based collect validations need to be done for collect payments only and not for BharatQR or
        // Upi Transfer payments.
        else if (($this->isFlowCollect($input) === true) and (isset($payment['receiver_type']) === false))
        {
            $config->validateCollectPayment($payment);
        }
    }

    protected function validateIfIntentEnabled(Payment\Entity $payment)
    {
        if ($payment->merchant->isFeatureEnabled(Feature\Constants::DISABLE_UPI_INTENT) === true)
        {
            if ($payment->isGooglePay())
            {
                $payment->unsetGooglePayMethod(Payment\Method::UPI);

                return;
            }

            throw new Exception\BadRequestValidationFailureException(
                'UPI intent is not enabled for the merchant');
        }

        /**
         * For QrV2 payments, We create payment entity only after we receive success gateway callback
         * Hence, VPA should be allowed in the input for such payments.
         * Here VPA is the id through which payment was received on the QR
         */
        if ((isset($payment['vpa']) === true) &&
            ($payment[Payment\Entity::RECEIVER_TYPE] !== Entity::QR_CODE)
        ) {
            throw new Exception\BadRequestValidationFailureException(
                'The vpa field is not required and not shouldn\'t be sent.'
            );
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
            $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_CARD_SUBTYPE_BUSINESS_NOT_SUPPORTED;

            if ($subtype === Card\SubType::CONSUMER)
            {
                $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_CARD_SUBTYPE_CONSUMER_NOT_SUPPORTED;
            }

            throw new Exception\BadRequestException(
                $errorCode,
                null,
                [
                    'method'   => Method::CARD,
                    'sub_type' => $subtype,
                    'iin'      => $card->getIin()
                ]);
        }
    }

    protected function updatePaymentAuthenticated($data = [])
    {
        $payment = $this->payment;

        $updated = $this->repo->transaction(function() use ($payment, $data)
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

            $payment->setStatus(Payment\Status::AUTHENTICATED);

            $payment->setAuthenticatedTimestamp();

            $this->trace->info(
                TraceCode::PAYMENT_STATUS_AUTHENTICATED,
                [
                    'payment_id'        => $payment->getId(),
                    'old_status'        => $status,
                    'method'            => $payment->getMethod(),
                    'gateway'           => $payment->getGateway(),
                ]);

            $this->repo->saveOrFail($payment);

            $customProperties = $payment->toArrayTraceRelevant();

            $this->segment->trackPayment($payment, TraceCode::PAYMENT_AUTHENTICATION_SUCCESS, $customProperties);

            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_AUTHENTICATION_PROCESSED, $payment);

            return true;
        });

        if ($updated === true)
        {
            $this->tracePaymentInfo(TraceCode::PAYMENT_AUTHENTICATION_SUCCESS);

            $this->sendFeedbackPaymentAuthenticatedToDoppler($payment);
        }

        return $updated;
    }

    protected function updatePaymentAuthorized($data = [], bool $wasFailed = false)
    {
        $payment = $this->payment;

        $this->validateAuthCode($data, $wasFailed);

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

            // Disable Auto Refunds Merchants' payments must not have the auto refund timestamp set
            if ($payment->merchant->isFeatureEnabled(Feature\Constants::DISABLE_AUTO_REFUNDS) === false)
            {
                // Auto refund timestamp will be set, when the payment is authorized.
                $this->setAutoRefundTimestamp($payment);
            }

            $payment->setErrorNull();

            $payment->setNonVerifiable();

            $payment->setAmountAuthorized();

            $payment->setStatus(Payment\Status::AUTHORIZED);

            $payment->setAuthorizeTimestamp();

            $this->updateAcquirerData($payment, $data);

            $this->setPayerAcccountTypeIfApplicable($payment, $data);

            // If payment was earlier failed, then that means it's
            // getting authorized late.
            $payment->setLateAuthorized($wasFailed);

            $tracableAcquirerData = [];

            if ($payment->isUpi() === true)
            {
                $tracableAcquirerData = $this->getTracableAcquirerDataForUpi($payment, $data);
            }

            $this->trace->info(
                TraceCode::PAYMENT_STATUS_AUTHORIZED,
                [
                    'payment_id'        => $payment->getId(),
                    'late_authorize'    => $wasFailed,
                    'old_status'        => $status,
                    'method'            => $payment->getMethod(),
                    'gateway'           => $payment->getGateway(),
                    'acquirer'          => $tracableAcquirerData,
                ]);

            //
            // If gateway is authorizing the payment (basically, no authAndCapture support), create transaction.
            //
            if ($this->isGatewayActuallyAuthorizingPayment($payment) === false)
            {
                $payment->setGatewayCaptured(true);

                if ($payment->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === false)
                {
                    [$txn, $feesSplit] = (new Transaction\Core)->createFromPaymentAuthorized($payment);

                    $this->repo->saveOrFail($txn);
                }
                else
                {
                    (new ReverseShadowPaymentsCore())->createLedgerEntryForGatewayCaptureReverseShadow($payment);
                }
                // Also sets the transaction association with the payment.
                // Fee Split would be null, as its the dummy transaction, so we are not saving fee split.

                $this->trace->info(
                    TraceCode::PAYMENT_GATEWAY_CAPTURED,
                    [
                        'payment_id'        => $payment->getId(),
                        'late_authorize'    => $wasFailed,
                ]);

                $this->createLedgerEntriesForGatewayCaptureOnAuthorize($payment);
            }

            $this->repo->saveOrFail($payment);

            $this->updateAssociatedPaymentEntities($payment, $data);

            $customProperties = $payment->toArrayTraceRelevant();

            $this->segment->trackPayment($payment, TraceCode::PAYMENT_AUTH_SUCCESS, $customProperties);

            $event = $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_AUTHORIZATION_PROCESSED, $payment);

            $this->app['shield.service']->enqueueShieldEvent($event);

            return true;
        });

        if ($updated === true)
        {
            $this->tracePaymentInfo(TraceCode::PAYMENT_AUTH_SUCCESS);

            $this->sendFeedbackPaymentAuthorizedToDoppler($payment);


        }

        return $updated;
    }


    private function createLedgerEntriesForGatewayCaptureOnAuthorize(Payment\Entity $payment)
    {
        try
        {
            if($this->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_JOURNAL_WRITES) === true)
            {
                $transactionMessage = CaptureJournalEvents::createTransactionMessageForGatewayCapture($payment);

                if (empty($transactionMessage) === false)
                {
                    \Event::dispatch(new TransactionalClosureEvent(function () use ($transactionMessage)
                    {
                        LedgerEntryJob::dispatchNow($this->mode, $transactionMessage);
                    }));

                    $this->trace->info(
                        TraceCode::GATEWAY_CAPTURED_EVENT_TRIGGERED,
                        [
                            'payment_id'        => $payment->getId(),
                            'message'           => $transactionMessage,
                        ]);
                }
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PG_LEDGER_ENTRY_FAILED,
                []);
        }
    }


    protected function sendFeedbackPaymentAuthenticatedToDoppler($payment)
    {
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

    protected function sendFeedbackPaymentAuthorizedToDoppler($payment)
    {

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

    /**
     * This method is used to store payer account type for UPI payments, if present.
     * @param Payment\Entity $payment
     * @param $input
     * @return void
     */
    public function setPayerAcccountTypeIfApplicable(Payment\Entity $payment, $data)
    {
        // update payer account type in reference2 column, if present.
        try
        {
            if (($payment->isUpi()) === true and
                (isset($data[Payment\Entity::PAYER_ACCOUNT_TYPE]) === true) and
                (in_array(strtolower($data[Payment\Entity::PAYER_ACCOUNT_TYPE]), PaymentsUpi\PayerAccountType::SUPPORTED_PAYER_ACCOUNT_TYPES)))
            {
                $payment->setReference2(strtolower($data[Payment\Entity::PAYER_ACCOUNT_TYPE]));
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::PAYER_ACCOUNT_TYPE_SAVE_FAILED,
                $data);
        }
    }

    protected function updateAssociatedPaymentEntities(Payment\Entity $payment, array $data)
    {
        // if subscription payment with offers applied => create a new invoice, associate with payment, order entities
        if ($payment !== null and $payment->hasSubscription())
        {
            (new Invoice\Core)->addOfferDetails($payment->getInvoiceId(), $payment);
        }

        if ($payment->isTokenisationUnhappyFlowHandlingApplicable() === false)
        {
            $this->updateTokenOnAuthorized($payment, $data);
        }

        // We will be updating the details in upi_mandate too.
        $this->updateRecurringEntitiesForUpiIfApplicable($payment, $data);
        // store billing_address for AVS
        $this->validateAndSaveBillingAddressForAVSIfApplicable($payment);

        //
        // If payment has an associated order
        // set the order to be paid
        //
        //
        // Please keep this function at the end of transaction block, as
        // we are updating orders which lies in PG Router service now.
        // This has been done to temporarily handle the distributed transaction failures.
        $this->updateAuthorizedOrderStatus($payment);
    }

    protected function isGatewayActuallyAuthorizingPayment(Payment\Entity $payment): bool
    {
        //
        // No gateway for bank transfer or Bharat Qr or UPI Transfer, everything is internal
        //
        if (($payment->isBankTransfer() === true) or
            ($payment->isBharatQr() === true) or
            ($payment->isUpiTransfer() === true) or
            ($payment->isCoD() === true) or
            ($payment->isPos() === true)
        )
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

        $acquirer = null;
        if($networkCode !== null)
        {
            $acquirer = $payment->terminal->getGatewayAcquirer();
        }

        $terminalMode = $payment->terminal->getMode();

        /*
         In case of UPI OTM payments, we are using auth and capture flow.
         And we need payment to be gateway_captured=false, and thereby,
         capture actually hits the gateway to execute the mandate.
        */
        if ($payment->isUpiOtm() === true)
        {
            return true;
        }

        if ($terminalMode === Terminal\Mode::AUTH_CAPTURE)
        {
            return true;
        }
        else if ($terminalMode === Terminal\Mode::PURCHASE)
        {
            return (Payment\Gateway::supportsPurchase($gateway, $networkCode, $acquirer) === false);
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

    protected function verifyCurrency($gatewayInput, $payment)
    {
        if(array_key_exists("currency", $gatewayInput) == true)
        {
            if($gatewayInput["currency"] == Currency\Currency::getIsoCode($payment->getCurrency())
             || strtolower($gatewayInput["currency"]) == strtolower($payment->getCurrency())) {
                return ;
            }
            throw new Exception\BadRequestValidationFailureException(
                'Callback payment currency does not match. Please notify the admin of this error.');
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
            'x_entity_id' => $this->payment->getPublicId()
        ];

        $otpSubmitUrl = $this->route->getUrl('payment_otp_submit_private', $params);

        return $otpSubmitUrl;
    }

    protected function getPaymentRedirectTo3dsUrl(): string
    {
        $params = [
            'x_entity_id' => $this->payment->getPublicId()
        ];

        $otpFallbackUrl = $this->route->getUrlWithPublicAuth('payment_redirect_3ds', $params);

        return $otpFallbackUrl;
    }

    protected function getOtpResendUrl(): string
    {
        $params = [
            'x_entity_id' => $this->payment->getPublicId()
        ];

        $otpResendUrl = $this->route->getUrlWithPublicAuth('payment_otp_resend', $params);

        return $otpResendUrl;
    }


    protected function getOtpResendUrlPrivate(): string
    {
        $params = [
            'x_entity_id' => $this->payment->getPublicId()
        ];

        $otpResendUrl = $this->route->getUrl('payment_otp_resend_private', $params);

        return $otpResendUrl;
    }

    protected function getOtpResendUrlJson(): string
    {
        $params = [
            'x_entity_id' => $this->payment->getPublicId()
        ];

        $otpResendUrl = $this->route->getUrlWithPublicAuth('payment_otp_resend_json', $params);

        return $otpResendUrl;
    }

    protected function getPaymentIdAndHashParams(): array
    {
        $publicId = $this->payment->getPublicId();

        $hash = $this->getHashOf($publicId);

        return ['x_entity_id' => $publicId, 'hash' => $hash];
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

    protected function isAutoRecurring(array $input)
    {
        return ((empty($input[Payment\Entity::RECURRING]) === false) and
                ($input[Payment\Entity::RECURRING] === 'auto'));
    }

    protected function validateAndSaveInputDetailsIfRequired($payment, $input, $gatewayInput, $ret)
    {
        $type = $this->getFallbackOrRedirectOrCardMandateType($payment, $ret);

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
            ($payment->hasCard() === true) and
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

        // Multiplying by 60 since cache put() expect ttl in seconds
        $this->cache->put($key, $input, $ttl * 60);
    }

    protected function shouldRedirect(Payment\Entity $payment)
    {
        $routeName = $this->app['request.ctx']->getRoute();

        if (($this->app['basicauth']->isPrivateAuth() === false) or
            ($this->app['api.route']->isJsonRoute($routeName) === true))
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

        if ($payment->isCod() === true)
        {
            return false;
        }

        if ($payment->isNetbanking() === true)
        {
            return true;
        }

        if ((($payment->isMethodCardOrEmi() === false) and
                ($payment->isAppCred() === false)) or
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

        if ($payment->isVisaSafeClickPayment() === true)
        {
            return false;
        }

        if (empty($payment->getGooglePayMethods()) === false)
        {
            return false;
        }

        return true;
    }

    protected function shouldRedirectPaymentCreateReq(Payment\Entity $payment, $gatewayInput)
    {
        $routeName = $this->app['request.ctx']->getRoute();
        $this->isAjaxRoute = $this->app['api.route']->isAjaxPaymentCreateRoute($routeName);
        $this->isJsonRoute = $this->app['api.route']->isJsonRoute($routeName);

        // In case of 3ds/non-headless card payments we return redirect response for /payments/create/ajax
        if(($payment->isCard() === true) and ($this->isAjaxRoute === true) and (($this->canRunHeadlessOtpFlow($payment, $gatewayInput) === false))
            and ($this->merchant->Is3dsDetailsRequiredEnabled() === true) and (isset($gatewayInput["fraud_check"]) === false))
        {
            return true;
        }

        // In case of s2s Route we send redirect response
        if($this->isJsonRoute === true){
            return true;
        }

        return false;
    }

    protected function shouldRedirectV2(Payment\Entity $payment, $gatewayInput)
    {
        /*
         * We don't use the redirect flow for the following scenarios
         * 1. Request is not an s2s route
         * 2. Route is not a s2s json response route i.e /payments/create/json
         * 3. Payment recurring type is auto
         * 4. Payment is method is banktransfer, upi
         * 5. auth type is OTP or preferred auth contains OTP
         * 6. BharathQR payment
         * 7. Payment receiver is VPA
         * 8. Payment is of Google pay cards
         * 9. Payment is of Google pay provider
         * 10. Request is not /payments/create/ajax for 3ds/non-headless card payment
         */

        if (($this->shouldRedirectPaymentCreateReq($payment, $gatewayInput) === false) or
            ($payment->isRecurringTypeAuto() === true) or
            ($payment->isBankTransfer() === true) or
            ($payment->isUpi() === true) or
            ($payment->isBharatQr() === true) or
            ($payment->isUpiTransfer() === true) or
            ($payment->isAppCred() === true) or
            ($payment->isVisaSafeClickPayment() === true) or
            ($payment->isNach() === true) or
            ($payment->isGooglePayCard() === true) or
            ($payment->isCod() === true) or
            (empty($payment->getGooglePayMethods()) === false))
        {
            return false;
        }

        $merchant = $payment->merchant;

        if ($merchant->isFeatureEnabled(Feature\Constants::JSON_V2)  === true)
        {
            return true;
        }

        if (((empty($gatewayInput['auth_type']) === false) and
            ($gatewayInput['auth_type'] === Payment\AuthType::_3DS)) or
            ($payment->getAuthType() === Payment\AuthType::_3DS))
        {
            return true;
        }

        // check only for headless need to figure out for IVR and Axis express pay
        if (($this->canRunHeadlessOtpFlow($payment, $gatewayInput) === true) and
            ($this->headlessError === false))
        {
            return false;
        }

        if (($this->isAuthTypeOtp($payment)) and ($payment->getGateway() === Gateway::ICICI)){
            return false;
        }

        if (($this->canRunIvrFlow($payment) === true))
        {
            return false;
        }

        return true;
    }

    /**
     * Check and return if DCC is applicable for this s2s payment
     * @param Payment\Entity $payment
     * @return bool
     */
    protected function shouldRedirectDCC(Payment\Entity $payment): bool
    {
        $library = $payment->getMetadata(Analytics\Entity::LIBRARY);

        // For non S2S and non-custom checkout calls, this redirection should not happen

        if ((($this->app['api.route']->isS2SPaymentRoute() === false) or
            ($this->app['basicauth']->isPrivateAuth() === false)) and
            $this->isLibrarySupportedForDCC($library) === false)
        {
            return false;
        }

        if ($payment->isRecurring() === true)
        {
            return false;
        }

        if($payment->isMethodInternationalApp() === true and $payment->merchant->isDCCEnabledInternationalMerchant())
        {
            return true;
        }

        if (($payment->isCard() === false) or ($payment->merchant->isDCCEnabledInternationalMerchant() === false))
        {
            return false;
        }

        if($payment->card === null or (new Payment\Service)->isDccEnabledIIN($payment->card->iinRelation, $payment->merchant) === false)
        {
            return false;
        }

        if($payment->getCurrency() === $payment->card->iinRelation->getIinCurrency())
        {
            return false;
        }

        return true;
    }
    /**
     * Check and return if Address collection is applicable for this s2s payment
     * @param Payment\Entity $payment
     * @return bool
     */
    protected function shouldRedirectForAddressCollection(Payment\Entity $payment): bool
    {
        if ($payment->isRecurring() === true)
        {
            return false;
        }

        //Check if address required is enabled
        $addressRequired = false;

        $library = $payment->getMetadata(Analytics\Entity::LIBRARY);

        if(($payment->merchant->isOpgspImportEnabled()) and
            (in_array($library, Analytics\Metadata::OPGSP_SUPPORTED_LIBRARIES) === true))
        {
            return true;
        }

        if($payment->isMethodInternationalApp() === true and
            (in_array($library,Analytics\Metadata::SUPPORTED_LIBRARIES_FOR_INTERNATIONAL_APPS) === true))
        {
            if($library === Analytics\Metadata::CHECKOUTJS or $library === Analytics\Metadata::HOSTED)
            {
                return false;
            }
            return true;
        }

        if (($payment->isInternational() === true) and
            ($payment->isCard() === true) and
            ($payment->card !== null))
        {
            // CheckoutJS has native address collection support, hence doesn't need a redirect
            if($library === Analytics\Metadata::CHECKOUTJS or $library === Analytics\Metadata::HOSTED)
            {
                return false;
            }

            $addressRequired = (new Payment\Service)->isAddressRequired($library, $payment->card->iinRelation, $payment->merchant);
        }

        return $addressRequired;
    }
    /**
     * Check and return if RaaS is applicable for this payment and is of type International
     * @param Payment\Entity $payment
     * @return bool
     */
    protected function shouldRedirectRaasInternational(Payment\Entity $payment): bool
    {
        if (($payment->merchant->isFeatureEnabled(Features::RAAS) === true) and ($payment->isInternational()===true))
        {
            return true;
        }

        return false;
    }

    /**
     * To be removed.
     * @param $library
     * @return bool
     */
    public function isLibrarySupportedForDCC($library): bool
    {
        if ((isset($library) === true) and
            (in_array($library, Analytics\Metadata::DCC_SUPPORTED_LIBRARIES) === true)
        ) {
            return true;
        }
        return false;
    }

    /**
     * @param $library
     * @return bool
     */
    public function isDCCEnabledLibraryOnFeatureFlag($library): bool
    {
        if ((isset($library) === true) and
            (in_array($library, Analytics\Metadata::DCC_SUPPORTED_LIBRARIES_ON_FEATURE_FLAG) === true)
        ) {
            return true;
        }
        return false;
    }

    /**
     * @param $library
     * @return bool
     */
    public function isLibrarySupportedForInternationalApps($library): bool
    {
        if ((isset($library) === true) and
            (in_array($library, Analytics\Metadata::SUPPORTED_LIBRARIES_FOR_INTERNATIONAL_APPS) === true)
        ) {
            return true;
        }
        return false;
    }

    public function isLibrarySupportedForAVSHttpResponse($library): bool
    {
        if((isset($library) === true) and
            (in_array($library, Analytics\Metadata::ADDRESS_COLLECTION_VIA_REDIRECT_LIBS) === true))
        {
            return true;
        }
        return false;
    }

    /* To support 3ds 2.0 payments on checkout route i.e. /payments/create/checkout
     * the payment details need to be cached
     * which will be required in the second authenticate call
     * */
    protected function cachePaymentDataIfApplicableForCheckout(Payment\Entity $payment)
    {
        $routeName = $this->app['request.ctx']->getRoute();
        $this->isCheckoutRoute = $this->app['api.route']->isCheckoutPaymentCreateRoute($routeName);
        if(($payment->isCard() === true) and ($this->isCheckoutRoute === true)){
            $payload = [
                'merchant_id' => $payment->getMerchantId(),
                'payment_id' => $payment->getPublicId(),
                'mode'  => $this->mode,
                'public_key' => $this->app['basicauth']->getPublicKey(),
                'account_id' => $this->app['basicauth']->authCreds->creds['account_id'],
                'oauth_client_id' => $this->app['basicauth']->getOAuthClientId(),
            ];
            // encrypt with key
            $encryptedPayload = Crypt::encrypt($payload);

            $trackId = $payment->getId();

            $key = Payment\Entity::getRedirectToAuthorizeTrackIdKey($trackId);

            // Multiplying by 60 since cache put() expect ttl in seconds
            $this->cache->put($key, $encryptedPayload, self::REDIRECT_CACHE_TTL * 60);
        }
    }

    // function accepts, $terminalGatewayInput to check whether we can return a redirect response or not
    // since it has auth terminal selection data and if we can return a redirect response, we are using
    // $gatewayInput to add selected terminalIds node which will be used in the redirect flow
    protected function validateAndReturnRedirectResponseIfApplicable(Payment\Entity $payment, array $terminalGatewayInput, array & $gatewayInput = [])
    {
        try
        {
            //cache payment payload for 3ds2.0 cards payment create /checkout
            $this->cachePaymentDataIfApplicableForCheckout($payment);

            $merchant = $payment->merchant;

            $redirectDcc = $this->shouldRedirectDCC($payment);

            $redirectAddressCollection = $this->shouldRedirectForAddressCollection($payment);

            if (($this->shouldRedirect($payment) === false) and
                ($this->shouldRedirectV2($payment, $terminalGatewayInput) === false) and
                ($redirectDcc === false) and
                ($redirectAddressCollection === false))
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

            $trackId = $payment->getId();

            $key = Payment\Entity::getRedirectToAuthorizeTrackIdKey($trackId);

            // Multiplying by 60 since cache put() expect ttl in seconds
            $this->cache->put($key, $encryptedPayload, self::REDIRECT_CACHE_TTL * 60);

            $redirectUrl = '';
            $httpMethod = '';
            $routeName = $this->app['request.ctx']->getRoute();
            $this->isAjaxRoute = $this->app['api.route']->isAjaxPaymentCreateRoute($routeName);
            $library = $payment->getMetadata(Analytics\Entity::LIBRARY);

            if ($redirectDcc === true)
            {
                $redirectUrl = $this->route->getUrl('payment_redirect_to_dcc_info', ['id' => $trackId]);
                $httpMethod = $this->route::getApiRoute('payment_redirect_to_dcc_info')[0];
            }
            elseif ($redirectAddressCollection === true)
            {
                $redirectUrl = $this->route->getUrl('payment_redirect_to_address_collect', ['id' => $trackId]);
                $httpMethod = $this->route::getApiRoute('payment_redirect_to_address_collect')[0];
            }
            else
            {
                $redirectUrl = $this->route->getUrl('payment_redirect_to_authenticate_get', ['id' => $trackId]);
            }

            $data['payment_id'] = $payment->getPublicId();
            $data['redirect'] = true;
            $data['type'] = 'first';
            $data['request'] = [
                'url'      => $redirectUrl,
                'method'   => 'redirect',
                'task_id'  => $this->request->getTaskId()
            ];

            // In case of 3ds/non-headless card payment on /payments/create/ajax route
            // we return redirect response to support 3ds 2.0 payments
            // applicable for 3ds 1.0 payments as well
            if(($payment->isCard() === true) and ($this->isAjaxRoute === true) and ($this->canRunHeadlessOtpFlow($payment, $gatewayInput) === false) and empty($httpMethod)){
                $data['type'] = 'redirect';
                $data['request'] = [
                    'url'      => $redirectUrl,
                    'method'   => 'POST',
                    'task_id'  => $this->request->getTaskId()
                ];
            }

            // Passing http-method additionally for custom checkout redirect
            if(($this->isLibrarySupportedForDCC($library) || $this->isLibrarySupportedForAVSHttpResponse($library))
                && empty($httpMethod) !== true)
            {
                $data['request']['http_method'] = $httpMethod;
            }

            if ($this->shouldAddOtpGenerateUrl($terminalGatewayInput) === true)
            {
               $data['request']['otp_generate_url'] = $this->route->getUrlWithPublicAuthInQueryParam('payment_otp_generate',
                [
                        'x_entity_id' => $payment->getPublicId(),
                        'track_id' => $trackId,
                ]);
            }


            $data['version'] = 1;

            $this->repo->saveOrFail($payment);

            $payload['track_id'] = $trackId;
            $payload['request']  = $data;

            $this->trace->info(
                TraceCode::PAYMENT_CREATED_IN_REDIRECT_TO_AUTHORIZE_FLOW,
                $payload
            );

            $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CREATE_REDIRECT_RESPONSE_SENT , $payment);

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

    public function shouldAddOtpGenerateUrl(array $terminalGatewayInput)
    {
        $authTypes = [
            Payment\AuthType::HEADLESS_OTP,
            Payment\AuthType::IVR,
            Payment\AuthType::OTP,
        ];

        if ((empty($terminalGatewayInput['auth_type']) === false) and
            (in_array($terminalGatewayInput['auth_type'], $authTypes, true) === true))
        {
            return true;
        }

        // OTP generate URLs are present only for s2s jsonv2 routes merchants
        // For others the payment happens via ACS page
        if(($this->merchant->isFeatureEnabled(Feature\Constants::JSON_V2) === true) and
            (empty($terminalGatewayInput['payment']) === false) and
            ($terminalGatewayInput['payment']['method'] === Method::PAYLATER
                and $terminalGatewayInput['payment']['wallet'] === PayLater::LAZYPAY))
        {
            return true;
        }

        return false;
    }

    public function processOtpGenerate($payment, $input)
    {
        $this->setPayment($payment);

        $resource = $this->getCallbackMutexResource($payment);

        $response = $this->mutex->acquireAndRelease(
            $resource,
            function() use ($payment)
            {
                $ret = $this->reCheckPayment($payment, PaymentConstants::REQUEST_TYPE_OTP);

                if ($ret !== null)
                {
                    return $ret;
                }

                if (($payment->getAuthType() !== null) and
                    (in_array($payment->getAuthType(), Payment\AuthType::$otpAuthTypes, true) === true))
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_OTP_GENERATE_ALREADY_PROCESSED);
                }

                $key = $payment->getCacheRedirectInputKey();

                $inputDetails = $this->getInputDetails($payment, $key);

                $gatewayInput = $inputDetails['gateway_input'];

                $this->setAnalyticsLog($payment);

                $payment->setAuthType(Payment\AuthType::OTP);

                unset($inputDetails['gatewayInput']);

                $this->runFraudChecksIfApplicable($payment);

                $response = $this->gatewayRelatedProcessing($payment, $inputDetails, $gatewayInput);

                if ($response['type'] !== 'otp')
                {
                   throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_OTP_GENERATE_FAILURE);
                }

                return $response;
            },
            120,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            20,
            1000,
            2000);


        return $response;
    }


    protected function reCheckPayment($payment, $requestType)
    {
        // Reload in case it's processed by another thread.
        $this->repo->reload($payment);

        $merchant = $payment->merchant;

        if (($this->payment->hasBeenAuthenticated() === true) and
            ($this->payment->hasNotBeenAuthorized() === true))
        {
            return $this->processAuthenticateResponse($this->payment);
        }

        if ($payment->hasBeenAuthorized() === true)
        {
            return $this->processPaymentCallbackSecondTime($payment);
        }

        if (($merchant->isFeatureEnabled(Feature\Constants::JSON_V2) === true) and
            ($payment->isFailed() === true))
        {
            if (in_array($payment->getInternalErrorCode(), self::AUTHORIZE_JSON_V2_3DS_FALLBACK_ERRORS) === true)
            {
                $payment->setStatus(Status::CREATED);

                $payment->setErrorNull();
            }
        }

        // if payment is already processed and failed we will throw an error
        if ($payment->isFailed() === true)
        {
            return $this->rethrowFailedPaymentErrorException($payment);
        }

        $diff = Carbon::now(Timezone::IST)->getTimestamp() - $payment->getCreatedAt();

        if ($diff > self::PAYMENT_REDIRECT_TO_AUTHORIZE_TIME_DURATION)
        {
            $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_CANNOT_REDIRECT_TO_AUTHORIZE;

            if ($requestType === PaymentConstants::REQUEST_TYPE_OTP)
            {
                $errorCode = ErrorCode::BAD_REQUEST_PAYMENT_CANNOT_GENERATE_OTP;
            }

            $this->segment->trackPayment($payment, $errorCode);

            throw new Exception\BadRequestException(
                $errorCode);
        }

        return null;
    }

    public function ValidateAndProcessDccInput(Payment\Entity $payment, $input=[], array & $inputDetails)
    {
        //checking for route as dcc is only processed in this redirect route for now
        if (($this->route->getCurrentRouteName() === 'payment_update_and_redirect') and
            (isset($input['dcc_currency']) === true) and
            (isset($input['currency_request_id']) === true))
        {
            if ($this->checkDccMetaRecord($payment) === false)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_CONFLICT_ALREADY_EXISTS);
            }
            $this->preProcessDCCInputs($input, $payment);
            $this->preProcessAppCurrencyWrapperForRedirectFlow($input,$payment);
            // This will force the terminal selection to happen for the second time
            // this is required if the DCC is applied in update and redirect
            // Usecase if merchant requests in INR, the user decides to pay in USD
            // the terminal should be selected with the USD
            unset($inputDetails['gateway_input']['selected_terminals_ids']);

            $this->trace->info(
                TraceCode::INTERNATIONAL_TERMINAL_DESELECT_FOR_DCC,
                [
                    'payment_id' => $payment->getId()
                ]
            );
        }
    }

    public function processRedirectToAuthorize(Payment\Entity $payment, string $trackId, $input=[])
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
            function() use ($payment, $input)
            {
                $ret = $this->reCheckPayment($payment, PaymentConstants::REQUEST_TYPE_REDIRECT);

                if ($ret !== null)
                {
                    return $ret;
                }
                $this->validatePayLaterIfApplicableForRedirect($payment,$input);
                //Address validation if required
                $this->validateAddressIfPresent($payment,$input);

                $key = $payment->getCacheRedirectInputKey();

                $inputDetails = $this->getInputDetails($payment, $key);

                $inputDetails = $this->getInputDetailsForPaylaterIfApplicable($payment, $input, $inputDetails);

                //DCC S2S Flow. Doing this inside mutex to avoid duplicate processing
                $this->ValidateAndProcessDccInput($payment,$input,$inputDetails);

                if(isset($input[Payment\Entity::BILLING_ADDRESS]) === true)
                {
                    $inputDetails[Payment\Entity::BILLING_ADDRESS] = $input[Payment\Entity::BILLING_ADDRESS];
                    $inputDetails['gateway_input']['billing_address'] = $input[Payment\Entity::BILLING_ADDRESS];
                }

                $gatewayInput = [];

                if (isset($inputDetails['gateway_input'])){
                    $gatewayInput = $inputDetails['gateway_input'];
                }

                if (isset($input['auth_step'])){
                    $inputDetails['auth_step'] = $input['auth_step'];
                }

                if (isset($input['browser'])){
                    $inputDetails['browser'] = $input['browser'];
                }

                if (isset($input['ip'])){
                    $inputDetails['ip'] = $input['ip'];
                }

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

                if ((empty($inputDetails['headless_error']) === true) or
                    ($inputDetails['headless_error'] === false))
                {
                    $this->setPreferredAuthIfApplicable($payment);
                }

                unset($inputDetails['gatewayInput']);

                $this->runFraudChecksIfApplicable($payment, $inputDetails);

                return $this->gatewayRelatedProcessing($payment, $inputDetails, $gatewayInput);
            },
            120,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            20,
            1000,
            2000);

        return $response;
    }

    public function processPaymentAuthorize($payment, $input)
    {
       $this->setPayment($payment);

       if (($this->merchant->isFeatureEnabled(Feature\Constants::AUTH_SPLIT) === false) or
           ($payment->hasBeenAuthenticated() === false) or
           ($payment->isMethodCardOrEmi() === false))
       {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PAYMENT_TO_AUTHORIZE);
       }

        // if payment is already processed and failed we will throw an error
       if ($payment->isFailed() === true)
       {
            return $this->rethrowFailedPaymentErrorException($payment);
       }

       $resource = $this->getAuthorizeMutexResource($payment);

       $response = $this->mutex->acquireAndRelease(
            $resource,
            function() use ($payment, $input)
            {
                $this->repo->reload($payment);

                if ($payment->hasBeenAuthorized() === true)
                {
                    return $this->postPaymentAuthorizeProcessing($payment);
                }

                $this->preProcessPaymentMeta($input, $payment);

                $input['payment'] = $payment->toArrayGateway();

                $token = $this->repo->token->getGlobalOrLocalTokenEntityOfPayment($payment);

                if ($token !== null)
                {
                    $input['token'] = $token;
                }

                if ($payment->hasCard())
                {
                    $card = $this->repo->card->fetchForPayment($payment);

                    $input['card'] = $card->toArray();
                }

                try
                {
                    $data = $this->callGatewayPay($input);

                    if (isset($data[Payment\Entity::TWO_FACTOR_AUTH]) === true)
                    {
                        $twoFactorAuth = $data[Payment\Entity::TWO_FACTOR_AUTH];

                        $payment->setTwoFactorAuth($twoFactorAuth);

                        $this->repo->saveOrFail($payment);
                    }

                    $this->updateAndNotifyPaymentAuthorized($data);
                }
                catch (Exception\BaseException $e)
                {
                    $this->processPaymentCallbackException($e);
                }

                return $this->postPaymentAuthorizeProcessing($payment);
            },
            120,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            20,
            1000,
            2000);

        return $response;
    }



    protected function getAuthorizeMutexResource(Payment\Entity $payment): string
    {
        return 'authorize_' . $payment->getId();
    }

    protected function getInputDetailsForPaylaterIfApplicable($payment, $input, $inputDetails)
    {
        if ($payment->isPayLater() === true and $payment->getWallet() === Gateway::GETSIMPL)
        {
            $inputDetails = $input;
        }

        return $inputDetails;
    }

    protected function getInputDetails($payment, $key = null)
    {
        if ($key === null)
        {
            $key = $payment->getCacheRedirectInputKey();
        }

        $token = $payment->localToken;

        if (($payment->isRecurring() === true) and
            (($token !== null) and (empty($token->getCardMandateId()) === false)))
        {
            if (($payment->getAuthType() !== null) and
                (in_array($payment->getAuthType(), Payment\AuthType::$otpAuthTypes, true) === true))
           {
               $key = $payment->getCacheInputKey();
           }
        }

        $inputDetails = $this->cache->get($key);

        if ($inputDetails === null and !($payment->isPaylater() === true and $payment->getWallet() === Gateway::GETSIMPL))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED
            );
        }

        if (($payment->isMethodCardOrEmi() === true) and (empty($inputDetails[Payment\Entity::TOKEN]) === true))
        {
            $this->setCardNumberAndCvv($inputDetails,$payment->card->toArray());

            if(empty($inputDetails['gateway_input']) === false)
            {

                $gatewayInput = $inputDetails['gateway_input'];

                $this->setCardNumberAndCvv($gatewayInput,$payment->card->toArray());

                $inputDetails['gateway_input'] = $gatewayInput;
            }
        }

        return $inputDetails;
    }

    protected function getFallbackOrRedirectOrCardMandateType(Payment\Entity $payment, $ret)
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

        // payment create /ajax for 3ds2.0
        if (($payment->isCard() === true) and
            (empty($ret['type']) === false) and
            ($ret['type'] === 'redirect'))
        {
            return 'redirect';
        }

        // payment create /checkout for 3ds2.0
        if (($payment->isCard() === true) and
            (empty($ret['type']) === false) and
            ($ret['type'] === 'first'))
        {
            return 'redirect';
        }

        if (($payment->isCardMandateRecurringInitialPayment() === true) and
            ($payment->localToken->cardMandate->shouldSaveAInputDetailsToCache() === true))
        {
            return 'card_mandate';
        }

        return null;
    }

    // returns the emi plan which belongs to merchant, in case not present it returns the plan mapped to shared merchant
    protected function getMerchantEmiPlans($iinEntity, $emiDuration, $merchant, $type = null)
    {
        //fetches the emi plans for merchant as well as shared merchant
        $emiPlans = $this->repo->emi_plan->fetchRelevantMerchantEmiPlan($iinEntity, $emiDuration, $merchant, $type);

        // If SBI EMI is disabled, don't consider that plan
        if ((new Merchant\Service)->isSbiEmiEnabled() === false)
        {
            $emiPlans = $emiPlans->reject(
                function($emiPlan)
                {
                    return (($emiPlan->getType() === Emi\Type::CREDIT) and
                        ($emiPlan->getBank() === IFSC::SBIN));
                }
            );
        }

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

        [$fee, $tax, $feesSplit] = $this->repo->useSlave(function () use ($payment)
        {
            return (new Pricing\Fee)->calculateMerchantFees($payment);
        });

        $gatewayInput['payment_fee'] = $fee;
    }

    protected function modifyAccountNumberForSpecificBanks($payment, array & $gatewayInput)
    {
        $accountNumber = $gatewayInput['order']['account_number'];
        $bank          = $gatewayInput['order']['bank'];

        // prepend required zeroes in the account number based on bank
        switch ($bank)
        {
            case IFSC::SBIN:
                $accountNumber = str_pad($accountNumber, 17, '0', STR_PAD_LEFT );
                break;

            case IFSC::KKBK:
                $accountNumber = str_pad($accountNumber, 10, '0', STR_PAD_LEFT );
                break;

            case IFSC::CBIN:
                $accountNumber = str_pad($accountNumber, 17, '0', STR_PAD_LEFT );
                break;

            case IFSC::RATN:
                if (starts_with($accountNumber, '0'))
                {
                    $accountNumber = substr($accountNumber, 4);
                }
                break;

            case IFSC::APGB:
                $accountNumber = str_pad($accountNumber, 17, '0', STR_PAD_LEFT);
                break;

            case IFSC::APGV:
                $accountNumber = str_pad($accountNumber, 17, '0', STR_PAD_LEFT);
                break;

            case IFSC::VARA:
                $accountNumber = str_pad($accountNumber, 19, '0', STR_PAD_LEFT);
                break;

            case IFSC::SPCB:
            case IFSC::MAHG:
                $accountNumber = str_pad($accountNumber, 17, '0', STR_PAD_LEFT);
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

        if (isset($billingAddressFromInput['first_name']) === true)
        {
            $billingAddressFromInput['name'] = $billingAddressFromInput['first_name'];

            unset($billingAddressFromInput['first_name']);
        }

        if (isset($billingAddressFromInput['last_name']) === true)
        {
            $billingAddressFromInput['name'] .= " " . $billingAddressFromInput['last_name'];

            unset($billingAddressFromInput['last_name']);
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

        /** Removing this as we are not storing in enach entity after migration.
         * Details are being stored in payments_nbplus.emandate_registration
         * reference number is to be fetched from token entity
        */
        //$gatewayPayment = $this->repo->enach->findByPaymentIdAndActionOrFail($payment['id'], GatewayAction::AUTHORIZE);

        $returnData['emandate_details'] = enachNpciGateway::fetchEmandateDisplayDetails(
                                                                               $payment,
                                                                               $token,
                                                                               $terminal,
                                                                               $merchant,
                                                                               $config,
                                                                               $mode
                                                                              );

        return $returnData;
    }

    protected function validateAndSaveBillingAddressForAVSIfApplicable(Payment\Entity $payment)
    {
        if($payment->isAVSSupportedForPayment() === false)
        {
            return;
        }

        $token = $payment->getGlobalOrLocalTokenEntity();

        if($token === null)
        {
            return;
        }

        $paymentBillingAddress = $payment->fetchBillingAddressFromPayment();

        if($paymentBillingAddress === null)
        {
            return;
        }

        $tokenBillingAddress = $payment->fetchBillingAddressFromCustomerToken();

        $billingAddressToSave = $paymentBillingAddress->getBillingAddress();

        $billingAddressToSave['type'] = Address\Type::BILLING_ADDRESS;

        if (isset($billingAddressToSave['postal_code']) === true) {
            // address entity stores zip code as "zipcode"
            // in input, we get zip code as "postal_code"
            $billingAddressToSave['zipcode'] = $billingAddressToSave['postal_code'];

            unset($billingAddressToSave['postal_code']);
        }

        if (empty($tokenBillingAddress) === true)
        {
            (new Address\Core)->create($token, Address\Type::TOKEN, $billingAddressToSave);
        }
        else
        {
            (new Address\Core)->edit($tokenBillingAddress, $billingAddressToSave);
        }
    }

    protected function pushCardMetaDataEvent($input, Payment\Entity $payment){

        if ($this->app->runningUnitTests() === false)
        {
            try
            {
                $data = [];
                $iin_number = "";
                if ($payment->isMethodCardOrEmi() === false)
                {
                    return;
                }

                if (empty($input['token']) === false)
                {
                    // search for card data if already saved card
                    $token = $payment->getGlobalOrLocalTokenEntity();
                    $network_card = $token->card;

                    if ((empty($network_card) === false) and
                        ($network_card->isNetworkTokenisedCard() === true))
                    {
                        $iin_number = Card\IIN\IIN::getTransactingIinforRange($network_card->getTokenIin()) ?? substr($network_card->getTokenIin(),0,6);
                    }
                    else
                    {
                        $iin_number = $network_card->getIin();
                    }
                }
                else
                {
                    // create a payload to send from input
                    if (empty($input['card']['number']) === false)
                    {

                        $trimmed_number = str_replace(' ', '', trim($input['card']['number']));
                        $trimmed_number = str_replace('-', '', $trimmed_number);

                        if (isset($input[Payment\Entity::CARD][Card\Entity::TOKENISED]) == true && $input[Payment\Entity::CARD][Card\Entity::TOKENISED] == true)
                        {
                            $iin_token = substr($trimmed_number, 0, 9);
                            $iin_number = Card\IIN\IIN::getTransactingIinforRange($iin_token) ?? substr($iin_token,0,6);
                        }
                        else
                        {
                            $iin_number = substr($trimmed_number, 0, 6);
                        }
                    }
                }

                $this->trace->info(
                    TraceCode::CARD_META_DATA_EVENT,
                    [
                        'input_token' => empty($input['token']) === false,
                        'is_network_card' => empty($network_card) === false,
                        'card_number_condition' => empty($input['card']['number']) === false,
                        'tokenised_condition' => isset($input[Payment\Entity::CARD][Card\Entity::TOKENISED]) == true && $input[Payment\Entity::CARD][Card\Entity::TOKENISED] == true,
                        'iin_number' => $iin_number === null,
                        'payment_id' => $payment->getId(),
                    ]);

                $data = [
                    "payment_id" => $payment->getId(),
                    "iin" => $iin_number,
                ];

                if (empty($data) === false)
                {

                    $topic = 'events.payments-card-meta.v1.' . $this->mode;  //'events.payments-card-meta.v1.live'

                    $event = [
                        'event_name' => 'PAYMENT.CARD.METADATA',
                        'event_type' => 'payment-events',
                        'event_group' => 'initiation',
                        'version' => 'v1',
                        'event_timestamp' => (int)(microtime(true)),
                        'producer_timestamp' => (int)(microtime(true)),
                        'source' => 'api',
                        'mode' => $this->mode,
                        'payment_id' => $data['payment_id'],
                        'iin' => $data['iin']
                    ];

                    (new KafkaProducer($topic, stringify($event)))->Produce();
                }
            }
            catch(\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    500,
                    TraceCode::CARDS_METADATA_KAFKA_PUSH_FAILED,
                    ["input" => $input]);
            }
        }
    }



    /**
     * @param Payment\Entity $payment
     * @param $input
     */
    protected function storeRewards(Payment\Entity $payment, $input)
    {
        if (isset($input['reward_ids']) === true)
        {
            $rewards = array_slice($input['reward_ids'], 0, 3);

            foreach ($rewards as $rewardId)
            {
                try
                {
                    $merchantReward = $this->repo->merchant_reward->fetchLiveMerchantRewardByRewardIdAndMerchantId(
                                             RewardEntity::verifyIdAndStripSign($rewardId), $payment->getMerchantId());

                    if (isset($merchantReward) === true)
                    {
                        $payment->associateReward($merchantReward->getRewardId());
                    }
                }
                catch (\Exception $e)
                {
                    $this->trace->traceException($e);
                }
            }
        }
    }

    /* Validates that if we are authorizing a payment then an auth code should
     * be present otherwise throws and exception.
     *
     * Added a check on wasFailed to verify whether this is a case of lateAuth or not.
     * We should skip this auth code verification in case this is a late auth payment.
     *
     * Skipping auth code validation for gateways which are not sending auth code to authorize the payments
     *
     * @param array $data
     * @throws Exception\LogicException
     */
    protected function validateAuthCode(array $data = [], bool $wasFailed)
    {
        $payment = $this->payment;

        if (($wasFailed === false) and
            (in_array($payment->getGateway(), Payment\Gateway::SKIP_AUTH_CODE_GATEWAYS, true) !== true) and
            ($payment->isMethodCardOrEmi() === true) and
            ($payment->getCpsRoute() === Payment\Entity::CARD_PAYMENT_SERVICE) and
            ($payment->getStatus() !== Payment\Status::AUTHORIZED) and
            (empty($data['acquirer']['reference2']) === true))
        {
            throw new Exception\LogicException(
                'Authorization cannot be done without an auth code.',
                null,
                [
                    'payment_id'    => $payment->getId(),
                    'gateway'       => $payment->getGateway(),
                    'status'        => $payment->getStatus(),
                    'method'        => $payment->getMethod(),
                ]
            );
        }
    }

    /**
     * @param $payment
     */
    protected function deleteBillingAddressIfExists(Payment\Entity $payment): void
    {
        $billingAddress = $payment->fetchBillingAddressFromCustomerToken();

        if (isset($billingAddress)) {
            (new Address\Core)->delete($billingAddress);
        }
    }

    protected function setIsCVVOptionalFlagIfApplicable(array &$cardInput, $input)
    {
        if ($this->payment->isVisaSafeClickStepUpPayment() === true)
        {
            $cardInput[Card\Entity::IS_CVV_OPTIONAL] = true;
        }
        // For few axis org merchants who need to support commercial card, cvv is optional, commercial cards don't have cvv
        else if ($this->payment->skipCvvCheck() === true)
        {
            $cardInput[Card\Entity::IS_CVV_OPTIONAL] = true;
        }
        else if ((isset($input['recurring']) === true) and
            ($input['recurring'] === RecurringType::AUTO))
        {
            $cardInput[Card\Entity::IS_CVV_OPTIONAL] = true;
        }
        else
        {
            $cardInput[Card\Entity::IS_CVV_OPTIONAL] = false;
        }
    }

    protected function processGatewayAmountAuthorized(
        Payment\Entity $payment,
        string $currency,
        int $amountAuthorized): bool
    {
        $diff = ($amountAuthorized - $payment->getAmount());

        if ($diff === 0)
        {
            return true;
        }

        if ($payment->shouldAllowGatewayAmountMismatch($currency, $amountAuthorized) === true)
        {
            (new Payment\PaymentMeta\Core)->addGatewayAmountInformation($payment, $amountAuthorized);

            $payment->setAmount($amountAuthorized);

            $this->processCurrencyConversions($payment);

            $this->repo->saveOrFail($payment);

            return true;
        }

        return false;
    }

    /**
     * This method is used to validate address if re-direction
     * is not required (in case of s2s/razorpayjs/custom/embedded/direct we need to redirect for address collection
     * without throwing validation error).
     * @param Payment\Entity $payment
     * @param array $input
     * @throws Exception\BadRequestException
     */
    protected function validateAddressIfPresentWithoutRedirect(Payment\Entity $payment, array $input)
    {
        $library = $payment->getMetadata(Analytics\Entity::LIBRARY);
        if (($this->isLibrarySupportedForAVSHttpResponse($library) === false) and
            $payment->isRecurring() !== true)
        {
            try {
                $this->validateAddressIfPresent($payment, $input);
            } catch (Exception\BadRequestException $e) {
                throw $e;
            }
        }
    }

    /**
     * Validating Libraries for Emerchantpay Payments
     * This validation will be removed once address and name collection screen
     * is rolled out for all libraries
     *
     * @param Payment\Entity
     * @throws Exception\BadRequestException
     */

    protected function validateLibraryForInternationalApps(Payment\Entity $payment, array $input)
    {
        if ($input['method'] !== Method::APP || $payment->isInternational() !== true){
            return;
        }

        $library = (new Payment\Service)->getLibraryFromPayment($payment);

        if($this->isLibrarySupportedForInternationalApps($library) === false){

            $this->trace->info(
                TraceCode::UNSUPPORTED_LIBRARY_FOR_INTERNATIONAL_APPS,
                [
                    'app'          => $input['provider'],
                    'library'      => $library
                ]);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PAYMENT_METHOD,
                null,
                null,
                "Payment method not supported on this integration"
            );
        }
    }

    protected function validateAddressIfPresent(Payment\Entity $payment, array $input)
    {
        $addressRequired = false;

        $addressRequiredWithName = false;

        $library = (new Payment\Service)->getLibraryFromPayment($payment);

        if ($payment->isInternational() === true) {
            if ($payment->isCard() === true and $payment->getBatchId() !== null) {
                return;
            }

            if (($payment->isCard() === true) and ($payment->card !== null)) {

                $addressRequired = (new Payment\Service)->isAddressRequired($library, $payment->card->iinRelation, $payment->merchant);
            }

            if (in_array($payment->getWallet(), Payment\Gateway::ADDRESS_REQUIRED_APPS) === true) {
                $addressRequiredWithName = (new Payment\Service)->isAddressWithNameRequired($library,$input, $payment->merchant);
            }
        }

        if($payment->merchant->isOpgspImportEnabled() and
            (in_array($library,Analytics\Metadata::OPGSP_SUPPORTED_LIBRARIES) === true))
        {
            $addressRequiredWithName = true;
        }

        if ($addressRequired === true || $addressRequiredWithName === true) {
            //TODO : Validate Address fields as well

            // Skip the address check if address is already set for the payment
            $address = $payment->fetchBillingAddress();
            if(isset($address)){
                return;
            }

            if (isset($input[Payment\Entity::BILLING_ADDRESS]) === false) {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_REQUEST_BODY,
                    null,
                    null,
                    "Billing Address is Empty"
                );
            }

            if (isset($input[Payment\Entity::BILLING_ADDRESS]['postal_code']) === false) {

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_REQUEST_BODY,
                    null,
                    null,
                    "Zipcode is Mandatory"
                );
            }

            if (($addressRequiredWithName === true) and
                ((isset($input[Payment\Entity::BILLING_ADDRESS]['first_name']) === false) or
                    (isset($input[Payment\Entity::BILLING_ADDRESS]['last_name']) === false))) {

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_REQUEST_BODY,
                    null,
                    null,
                    "First or Last Name is Empty"
                );
            }
        }
    }

    /**
     * This function is currently supported for Gpay only
     * Used to select terminals for the supported application methods
     * @param Payment\Entity $payment
     *
     * @throws Exception\RuntimeException
     */
    protected function setSelectedTerminalsForApplicationMethodsIfApplicable(Payment\Entity $payment)
    {
        $application = $payment->getApplication();

        if (isset($application) === false)
        {
            return;
        }

        switch ($application)
        {
            case Payment\Entity::GOOGLE_PAY:

                if ($payment->isGooglePay() === false)
                {
                    return ;
                }

                $methodTerminals = $this->separateMethodSpecificTerminalsForGooglePay();

                if (isset($methodTerminals[Method::CARD]))
                {
                    $this->selectedTerminals = [];

                    $cardNetworks = $this->fetchCardNetworksSupportedForGooglePay($methodTerminals[Method::CARD]);

                    $payment->setGooglePayCardNetworks($cardNetworks);
                }

                if (isset($methodTerminals[Method::UPI]))
                {
                    $this->selectedTerminals = $methodTerminals[Method::UPI];
                    // setting method as UPI here as this is required for aggregator gateway call
                    $this->payment->setMethod(Method::UPI);
                }

                $googlePayMethods = $payment->getGooglePayMethods();

                $this->trace->info(
                    TraceCode::GOOGLE_PAY_SUPPORTED_METHODS,
                    [
                        'payment'          => $payment->getId(),
                        'googlePayMethods' => $googlePayMethods
                    ]);

                if(empty($googlePayMethods))
                {
                    throw new Exception\RuntimeException(
                        'No terminal found.',
                        ['payment' => $this->payment->toArrayAdmin()],
                        null,
                        ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND);
                }

                break;
        }
    }

    /**
     * @param Payment\Entity $payment
     * @param array $input
     * @param $request
     * @param array $gatewayInput
     * @return mixed
     */
    protected function callAuthenticationGatewayBasedOnApplicationIfApplicable(Payment\Entity $payment, array $input, $request, array & $gatewayInput)
    {
        $application = $payment->getApplication();

        if (isset($application) === false)
        {
            return $request;
        }

        switch ($application)
        {
            case Payment\Entity::GOOGLE_PAY:

                return $this->callGooglePayAuthenticationGateway($payment, $request, $input, $gatewayInput);
        }

        return $request;
    }

    /**
     * @param Payment\Entity $payment
     * @param $gatewayInput
     * @param array $input
     * @param $request
     */
    protected function updateInputWithUpiParamsIfApplicable(Payment\Entity $payment, $gatewayInput, array & $input, $request)
    {
        if ($payment->isGooglePayMethodSupported(Method::UPI) and
            isset($request['data']) and
            isset($request['data']['intent_url']))
        {
            $input['upi'] = $gatewayInput['upi'];
            parse_str(str_replace('upi://pay?', '', $request['data']['intent_url']), $params);
            $input['upi']['params'] = $params;
            $input['upi']['params']['url'] = $request['data']['intent_url'];
        }
    }

    /**
     * @param $paymentMethod
     * @param Payment\Entity $payment
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\LogicException
     */
    protected function coreVerifyPaymentMethodEnabled($paymentMethod, Payment\Entity $payment): void
    {
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
                $this->verifyUpiEnabled($payment);
                break;

            case Payment\Method::BANK_TRANSFER:
                $this->verifyBankTransferEnabled();
                break;

            case Payment\Method::OFFLINE:
                $this->verifyOfflineEnabled();
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
                $this->verifyPayLaterEnabled($payment);
                break;

            case Payment\Method::NACH:
                $this->verifyNachEnabled();
                break;

            case Payment\Method::APP:
                $this->verifyAppEnabled($payment);
                break;
            case Payment\Method::COD:
                $this->verifyCoDEnabled($payment);
                break;
            case Payment\Method::FPX:
                $this->verifyFpxEnabled($payment);
                break;
            default:
                throw new Exception\LogicException(
                    'Should not reach here.',
                    null,
                    ['payment_method' => $paymentMethod]);
        }
    }

    /**
     * @param Payment\Entity $payment
     * @param $request
     * @param array $input
     * @param array $gatewayInput
     * @return mixed
     */
    protected function callGooglePayAuthenticationGateway(Payment\Entity $payment, $request, array $input, array $gatewayInput)
    {
        if (empty($payment->getGooglePayMethods()) === true)
        {
            return $request;
        }

        $input['payment'] = $payment;

        // Pushing to kafka here in case not pushed earlier
        // This can happen in case only cards method is enabled for
        // google_pay payment
        if ($payment->getIsPushedToKafka() === null)
        {
            $isPushedToKafka = $this->pushPaymentToKafkaForVerify($this->payment);

            $payment->setIsPushedToKafka($isPushedToKafka);
        }

        $this->updateAndSavePaymentFields($payment);

        $this->tracePaymentAndPushEvents($payment);

        $this->updateInputWithUpiParamsIfApplicable($payment, $gatewayInput, $input, $request);

        return $this->app['gateway']->call(Gateway::GOOGLE_PAY, GatewayAction::AUTHENTICATE, $input, $this->mode);
    }

    /**
     * @param Payment\Entity $payment
     */
    protected function updateAndSavePaymentFields(Payment\Entity $payment): void
    {
        // Resetting method here as it was set to 'upi' during upi gateway call
        $payment->setMethod(Method::UNSELECTED);

        $payment->setAuthenticationGateway(Gateway::GOOGLE_PAY);

        $payment->saveOrFail();
    }

    /**
     * @return bool
     */
    protected function checkIfRedirectRoute(): bool
    {
        $routeName = $this->app['request.ctx']->getRoute();

        $this->isJsonRoute = $this->app['api.route']->isJsonRoute($routeName);

        return (($this->app['basicauth']->isPrivateAuth() === true) and
            ($this->app['api.route']->isJsonRoute($routeName) === false));
    }

    /**
     * @param Payment\Entity $payment
     */
    protected function tracePaymentAndPushEvents(Payment\Entity $payment): void
    {
            $this->eventPaymentCreated();

            $this->tracePaymentInfo(TraceCode::PAYMENT_CREATED, Trace::DEBUG);

            $this->segment->trackPayment($payment, TraceCode::PAYMENT_CREATED);
    }

    /**
     * @param Payment\Entity $payment
     */
    protected function storeSavedCardConsentIfPresent(Payment\Entity $payment): void
    {
        try
        {
            $tokenEntity = $payment->getGlobalOrLocalTokenEntity();

            if(isset($tokenEntity) === false)
            {
                return;
            }

            if($tokenEntity->getMethod() !== Token\Entity::CARD)
            {
                return;
            }

            $redisKey = $payment->getPublicId() . '_saved_card_consent';

            $consent = $this->app['cache']->get($redisKey);

            if(empty($consent) === true)
            {
                return;
            }

            $this->trace->info(
                TraceCode::TOKENISATION_CONSENT_STORAGE,
                [
                    'paymentId'       => $payment->getId(),
                    'tokenId'         => $tokenEntity->getId(),
                    'tokenMerchantId' => $tokenEntity->getMerchantId(),
                ]);

            //consent will be a non boolean value (acknowledged_at time) in case of CAW saved card flow
            if($consent !== true)
            {
                $this->trace->info(
                    TraceCode::FETCH_AND_STORE_CONSENT_FROM_REDIS_RECURRING,
                    [
                        'consent' => $consent,
                    ]
                );
                $tokenEntity->setAcknowledgedAt($consent);
            }
            else
            {
                $tokenEntity->setAcknowledgedAt(Carbon::now()->timestamp);
            }

            $tokenEntity->saveOrFail();

            $this->app['cache']->delete($redisKey);

            /**
             * For global saved card payments, upon consent
             * we are creating local token and storing consent on the local token
             * The below code fetches the global token and adds consent to it as well
             */
            $consentedTokenId = $this->app['cache']->get($redisKey . '_token');

            $this->app['cache']->delete($redisKey . '_token');

            if (empty($consentedTokenId) || !isset($tokenEntity->customer))
            {
                return;
            }

            $consentedToken = (new Token\Core())->getByTokenIdAndCustomer($consentedTokenId, $tokenEntity->customer);

            if ((!isset($consentedToken)) ||
                ($consentedToken->hasBeenAcknowledged()) ||
                ($consentedToken->getId() === $tokenEntity->getId()))
            {
                return;
            }

            $consentedToken->setAcknowledgedAt(Carbon::now()->timestamp);

            $consentedToken->saveOrFail();
        }
        catch(\Exception $ex)
        {
            $this->trace->traceException($ex, Trace::ERROR, TraceCode::TOKENISATION_CONSENT_STORAGE_ERROR, [
                'paymentId' => $payment->getId(),
            ]);
        }
    }

    /**
     * @param  Payment\Entity  $payment
     * @return void
     */
    protected function createGlobalTokenIfApplicable(Payment\Entity $payment): void
    {
        try
        {
            if (! $payment->getSave())
            {
                return;
            }

            $token = $payment->localToken;

            if ((isset($token)) &&
                ($token->isCard()) &&
                ($token->isLocalTokenOnGlobalCustomer()) &&
                ($token->card->isGlobalTokenCreationSupportedOnCard())
            ) {
                $globalToken = (new Token\Core())->createGlobalTokenFromLocalToken($token,$payment->getGateway());

                SavedCardTokenisationJob::dispatch($this->mode, $globalToken->getId(), $payment->getId(), $payment->getId());
            }
        }
        catch(\Exception $ex)
        {
            $this->trace->traceException($ex, Trace::ERROR, TraceCode::GLOBAL_TOKEN_CREATION_ERROR, [
                'paymentId' => $payment->getId(),
            ]);
        }
    }

    /**
     * This function runs the FraudCheck for terminals where procurer is Razorpay , as fraud checks were skipped initially
     * for RaaS International Payments
     * @param Payment\Entity $payment
     * @param array $input
     */
    protected function performFraudCheckRaasInternational(Payment\Entity $payment, $input = [])
    {

            if (($this->shouldRedirect($payment) === true) or
                ($this->shouldRedirectV2($payment, []) === true) or
                ($this->shouldRedirectDCC($payment) === true) or
                ($this->shouldRedirectRaasInternational($payment) === false) or
                ($this->shouldRedirectForAddressCollection($payment) === true))
            {
                return ;
            }

            $sortedTerminals = $this->selectedTerminals;

            $priorityTerminal = $sortedTerminals[0];

            $runFraudCheck = false;
            $i = 0;
            $finalNonFraudTerminals=[];
                foreach ($sortedTerminals as $terminal)
                {

                if ($terminal['procurer'] === Procurer::RAZORPAY)
                {

                    $runFraudCheck = true;


                }else {
                    $finalNonFraudTerminals[$i++]=$terminal;
                }
            }
            //performs FraudCheck only when Procurer is RZP
            if (!$runFraudCheck)
            {
                return ;
            }

            try
                {

                    $this->runFraudChecks($payment, $input);

                }
                catch (\Throwable $ex)
                {   // if procurer of the terminal with priority is 1 , we consider the fraud detection
                    // and throw the exception
                    if ($priorityTerminal['procurer'] === Procurer::RAZORPAY)
                    {
                        $this->updatePaymentAuthFailed($ex);
                        throw $ex;
                    }
                    else
                    {
                        $this->trace->info(
                            TraceCode::FRAUD_DETECTION_FAILED_RAAS_INTERNATIONAL,
                            [
                                'error' => $ex->getMessage(),
                                'payment_id' => $payment->getId(),
                                'input_terminals' =>$this->selectedTerminals,
                                'output_terminals' => $finalNonFraudTerminals,

                            ]);
                        // when procurer is merchant , we skip the do not consider the fraud check assessment
                        // and replace the remove the razorpay terminals
                        $this->selectedTerminals=$finalNonFraudTerminals;

                    }

                }
    }

    /**
     * Dispatch an SQS job that attempts to create an order in Shopify for those that
     * failed due to network issues at the customer end
     * @param Payment\Entity $payment
     * @return void
     * @throws none
     */
    protected function dispatchOrderFor1ccShopify(Payment\Entity $payment)
    {
        $start = millitime();

        try
        {
            if ($payment->hasOrder() === false)
            {
                return;
            }

            $order = $payment->order;

            // Certain orders are not being dispatched to the queue
            // Splitting up the conditions to check the status temporarily
            if ($order->is1ccShopifyOrder() === true)
            {
                $dispatched = false;

                if ($payment->isCod() === true)
                {
                    $dispatched = true;

                    OneCCShopifyCreateOrder::dispatch([
                        'mode'                => $this->mode,
                        'razorpay_order_id'   => $order->getPublicId(),
                        'razorpay_payment_id' => $payment->getPublicId(),
                        'merchant_id'         => $this->merchant->getId(),
                        'type'                => 'create_order',
                        'dispatch_time'       => millitime() - $start,
                    ])->delay(now()->addMinutes(5));

                    // To debug payloads not being handled properly in sqs
                    $this->trace->info(
                        TraceCode::SHOPIFY_1CC_PLACE_ORDER_JOB,
                        [
                            'step'                => 'dispatch',
                            'dispatched'          => $dispatched,
                            'mode'                => $this->mode,
                            'razorpay_order_id'   => $order->getPublicId(),
                            'razorpay_payment_id' => $payment->getPublicId(),
                            'payment_method'      => $payment->getMethod(),
                            'payment_status'      => $payment->getStatus(),
                            'merchant_id'         => $this->merchant->getId(),
                            'from'                => 'Authorize',
                        ]);
                }
            }

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SHOPIFY_1CC_DISPATCH_JOB_FAILED,
                [
                    'payment_id' => $payment->getPublicId()
                ]);
        }
    }

    /**
     * For international initial recurring payments, if the gateway requires address collection
     * the method validates and saves the billing address from input into the token
     * @param Payment\Entity $payment
     * @param array $input
     * @return void
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\InvalidArgumentException
     */
    private function validateAndSaveTokenBillingAddressIfApplicable(Payment\Entity $payment, array $input)
    {
        if ($payment->merchant->isFeatureEnabled(Features::RECURRING_CHECKOUT_DOT_COM) and $payment->isInternational() and $payment->isRecurring() and $payment->isRecurringTypeInitial()
            and Payment\Gateway::isInternationalRecurringAddressRequired($payment->getGateway()))
        {
            $token = $payment->getGlobalOrLocalTokenEntity();
            if ($token === null)
            {
                return;
            }

            $tokenBillingAddress = $token->getBillingAddress();
            if (empty($tokenBillingAddress) === false)
            {
                return;
            }

            $this->validateAddressIfPresent($payment, $input);

            if (isset($input[Payment\Entity::BILLING_ADDRESS]))
            {
                $billingAddressFromInput = $this->getBillingAddressFromInput($input);

                (new Address\Core)->create($token, Address\Type::TOKEN, $billingAddressFromInput);
            }

        }
    }

    private function getBillingAddressFromInput(array $input): array
    {
        $billingAddressFromInput = $input[Payment\Entity::BILLING_ADDRESS];

        $billingAddressFromInput['type'] = Address\Type::BILLING_ADDRESS;

        if (isset($billingAddressFromInput['postal_code']) === true)
        {
            // address entity stores zip code as "zipcode"
            // in input, we get zip code as "postal_code"
            $billingAddressFromInput['zipcode'] = $billingAddressFromInput['postal_code'];

            unset($billingAddressFromInput['postal_code']);
        }

        if (isset($billingAddressFromInput['first_name']) === true)
        {
            $billingAddressFromInput['name'] = $billingAddressFromInput['first_name'];

            unset($billingAddressFromInput['first_name']);
        }

        if (isset($billingAddressFromInput['last_name']) === true)
        {
            $billingAddressFromInput['name'] .= " " . $billingAddressFromInput['last_name'];

            unset($billingAddressFromInput['last_name']);
        }

        return $billingAddressFromInput;
    }

    /**
     * @param Payment\Entity $payment
     * @param array          $input
     *
     * @return boolean
     *
     * Following Conditions to check and if all passes return true -
     * 1. Payment Should be Recurring Auto on Direct Library
     * 2. Should be a Card Payment
     * 3. Merchant should be international & dcc enabled
     * 4. Card Should be Supported for DCC and Card Country Shouldn't be Null
     */

    private function checkDCCForRecurringAutoOnLibraryDirect(array $input, Payment\Entity $payment)
    {
        if($payment->isRecurring() === false or $payment->getRecurringType() !== Payment\RecurringType::AUTO)
        {
            return false;
        }

        if((new Payment\Service)->getLibraryFromPayment($payment) !== Analytics\Metadata::DIRECT)
        {
            return false;
        }

        if (($payment->isCard() === false) or ($payment->merchant->isDCCEnabledInternationalMerchant() === false))
        {
            return false;
        }

        if($payment->card === null or $payment->card->getCountry() === null or (new Payment\Service)->isDccEnabledIIN($payment->card->iinRelation, $payment->merchant) === false)
        {
            return false;
        }

        return true;
    }

    protected function evalExperimentDCCRecurringAutoOnLibraryDirect(Payment\Entity $payment): bool
    {
        try
        {
            $properties = [
                'id'            => UniqueIdEntity::generateUniqueId(),
                'experiment_id' => $this->app['config']->get('app.dcc_recurring_on_auto_direct_experiment_id'),
                'request_data'  => json_encode(
                    [
                        'merchant_id' => $payment->merchant->getId(),
                    ]),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            $this->trace->info(TraceCode::SPLITZ_RESPONSE, $response);

            if ($variant === 'variant_on')
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::GLOBAL_CARD_PAYMENT_PROCESS_SPLITZ_ERROR
            );
        }

        return false;
      }

    protected function skipAVSon3DSExperiment(Payment\Entity $payment): bool
    {
        try
        {
            $properties = [
                'id'            => UniqueIdEntity::generateUniqueId(),
                'experiment_id' => $this->app['config']->get('app.skip_avs_on_3ds_experiment_id'),
                'request_data'  => json_encode(
                    [
                        'merchant_id' => $payment->merchant->getId(),
                    ]),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            if ($variant === 'variant_on')
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::SKIP_AVS_ON_3DS_CARD_PAYMENTS_SPLITZ_ERROR
            );
        }

        return false;
    }

    protected function saveOpgspImportDataIfApplicable($payment)
    {
        if($payment->merchant->isOpgspImportEnabled() === false)
        {
            return;
        }
        $invoiceEntity = [];
        try
        {
            $paymentNotes = $payment->getNotes()->toArray();

            // todo: add check for type in future to support AWB
            $invoiceEntity[InvoiceEntity::TYPE] = InvoiceType::OPGSP_INVOICE;

            $invoice = (new InvoiceService())->createPaymentSupportingDocuments($invoiceEntity, $payment);

            $invoice->setReceipt($paymentNotes[InvoiceConstants::OPGSP_INVOICE_NUMBER]);
            $this->repo->saveOrFail($invoice);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(
                TraceCode::OPGSP_INVOICE_SAVE_FAILED, [
                    'payment' => $payment,
                    'invoiceEntity' => $invoiceEntity,
                ]);
            $this->trace->traceException($e);
            throw new Exception\ServerErrorException(Error\PublicErrorDescription::SERVER_ERROR, ErrorCode::REPO_FAILED_TO_SAVE);
        }
    }
}
