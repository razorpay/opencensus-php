<?php

namespace RZP\Models\Payment;

use App;

use RZP\Exception;
use RZP\Error\Error;
use RZP\Models\Base;
use RZP\Models\Currency\Currency;
use RZP\Models\Feature\Constants;
use RZP\Trace\TraceCode;
use RZP\Gateway\Upi\Base\ProviderCode;
use RZP\Models\Payment\Service as PaymentService;

class Metric extends Base\Core
{
    // Labels for Payment Metrics
    const LABEL_PAYMENT_GATEWAY                 = 'gateway';
    const LABEL_PAYMENT_METHOD                  = 'method';
    const LABEL_PAYMENT_CURRENCY                = 'currency';
    const LABEL_PAYMENT_INTERNATIONAL           = 'international';
    const LABEL_PAYMENT_ISSUER                  = 'issuer';
    const LABEL_PAYMENT_TRANSACTION_TYPE        = 'transaction_type';
    const LABEL_PAYMENT_STATUS                  = 'status';
    const LABEL_CARD_TYPE                       = 'card_type';
    const LABEL_CARD_NETWORK                    = 'card_network';
    const LABEL_CARD_TOKENISED                  = 'card_tokenised';
    const LABEL_CARD_ALTID                      = 'alt_id';
    const LABEL_CARD_VAULT                      = 'card_vault';
    const LABEL_CARD_PROTOCOL_VERSION           = 'card_protocol_version';
    const LABEL_CARD_ENROLLMENT_STATUS          = 'card_enrolled';
    const LABEL_PAYMENT_LATE_AUTHORIZED         = 'late_authorized';
    const LABEL_PAYMENT_AUTO_CAPTURED           = 'auto_captured';

    const LABEL_SHOULD_AUTO_CAPTURE             = 'should_auto_capture';
    const LABEL_AUTO_CAPTURE_ERROR              = 'error';
    const LABEL_PAYMENT_GATEWAY_CAPTURED        = 'gateway_captured';
    const LABEL_PAYMENT_ERROR_CODE              = 'error_code';
    const LABEL_PAYMENT_IS_CREATED              = 'is_created';
    const LABEL_TRACE_CODE                      = 'code';
    const LABEL_TRACE_FIELD                     = 'field';
    const LABEL_TRACE_SOURCE                    = 'source';
    const LABEL_TRACE_EXCEPTION_CLASS           = 'exception_class';
    const LABEL_UPI_FLOW                        = 'upi_flow';
    const LABEL_UPI_PSP                         = 'upi_psp';
    const LABEL_PAYMENT_IS_TPV                  = 'is_tpv';
    const LABEL_MERCHANT_COUNTRY_CODE           = 'merchant_country_code';
    const LABEL_PAYMENT_MANDATE_HUB             = 'mandate_hub';
    const LABEL_ORG                             = 'org';

    const LABEL_LIBRARY                         = 'library';

    const LABEL_CRED_ELIGIBILITY                = 'is_eligible_for_cred';

    const  LABEL_OFFER                          = 'payment_offer';

    const LABEL_IS_COLLECTX_PAYMENT             = 'is_collectx_payment';

    const IS_VERIFY_NEW_FLOW                    = 'is_verify_new_flow';
    const IS_TIMEOUT_NEW_FLOW                   = 'is_timeout_new_flow';
    const LABEL_OPTIMIZER                       = 'optimizer';
    const LABEL_RECURRING                       = 'recurring';
    const LABEL_RECURRING_TYPE                  = 'recurring_type';
    const LABEL_IS_REARCH                       = 'is_rearch';
    const LABEL_IMPORT_PAYMENT                  = 'import_payment';

    // Add constant for router canary label
    const LABEL_ROUTER_CANARY = 'router_canary';

    // Metric Names
    const PAYMENT_CREATED                           = 'payment_created';
    const PAYMENT_CREATED_PG_ROUTER                 = 'payment_created_pg_router';
    const PAYMENT_AUTHENTICATED                     = 'payment_authenticated';
    const PAYMENT_AUTHORIZED                        = 'payment_authorized_v1';
    const PAYMENT_CAPTURED                          = 'payment_captured_v1';
    const PAYMENT_CAPTURE_QUEUE                     = 'payment_capture_queue';
    const PAYMENT_CAPTURED_VERIFY                   = 'payment_captured_verify';
    const PAYMENT_CREATE_REQUEST_TIME               = 'payment_create_request_time';
    const PAYMENT_CALLBACK_REQUEST_TIME             = 'payment_callback_request_time';
    const PAYMENT_UPI_CALLBACK_REQUEST_TIME         = 'payment_upi_callback_request_time';
    const PAYMENT_UPI_PAYER_PAYEE_CALLBACK_DIFF     = 'payment_upi_payer_payee_callback_diff';
    const PAYMENT_CREATE_REQUEST_TIME_PG_ROUTER     = 'payment_create_request_time_pg_router';
    const PAYMENT_FAILED                            = 'payment_failed';
    const PAYMENT_FAILED_PG_ROUTER                  = 'payment_failed_pg_router';
    const PAYMENT_PROCESS_FAILED                    = 'payment_process_failed';
    const PAYMENT_CALLBACK_PROCESS_FAILED           = 'payment_callback_process_failed';
    const PAYMENT_CAPTURE_FAILED                    = 'payment_capture_failed';
    const PAYMENT_REQUEST_ROUTE                     = 'payment_request_route';
    const PAYMENT_CALLBACK_ROUTE                    = 'payment_callback_route';
    const SHIELD_FRAUD_DETECTION_FAILED             = 'shield_fraud_detection_failed';
    const SHIELD_FRAUD_DETECTION_SKIPPED            = 'shield_fraud_detection_skipped';
    const SHIELD_INTEGRATION_ERROR                  = 'shield_integration_error';

    const PAYMENT_CREATION_AMOUNT_VALIDATION_FAILURE_COUNT = 'payment_creation_amount_validation_failure_count';

    const API_CHECKOUT_PREFERENCES_REQUEST_COUNT           = 'api_checkout_preferences_request_count';

    const API_CHECKOUT_SUBMIT_REQUEST_COUNT                = 'api_checkout_submit_request_count';

    const KAFKA_PUSH_SUCCESS_FOR_PAYMENT_COUNT             = 'kafka_push_success_for_payment_count';

    const KAFKA_PUSH_FAILED_FOR_PAYMENT_COUNT              = 'kafka_push_failed_for_payment_count';

    const VERIFY_FLOW_NEW_OR_OLD_COUNT                     = 'verify_flow_new_or_old_count';

    const TIMEOUT_FLOW_NEW_OR_OLD_COUNT                    = 'timeout_flow_new_or_old_count';

    const PAYMENT_SCHEDULER_DEREGISTER_KAFKA_SUCCESS_COUNT = 'payment_scheduler_deregister_kafka_success_count';
    const PAYMENT_SCHEDULER_DEREGISTER_KAFKA_FAILED_COUNT  = 'payment_scheduler_deregister_kafka_failed_count';

    const UNINTENDED_PAYMENT_ERROR_CODE_SUFFIX             = '_UNINTENDED_PAYMENT';

    const CRED_ELIGIBILITY_REQUEST_COUNT                   = 'cred_eligibility_request_count';

    const SPLIT_PAYMENT_CREATED_COUNT                      = 'split_payment_created_count';
    const SPLIT_PAYMENT_REQUEST_COUNT                      = 'split_payment_request_count';
    const SPLIT_PAYMENT_FAILED_COUNT                       = 'split_payment_failed_count';
    const SPLIT_PAYMENT_REQUEST_TIME                       = 'split_payment_request_time';

    const REFUND_SPLIT_PAYMENT_COUNT                       = 'refund_split_payment_request_count';
    const REFUND_SPLIT_PAYMENT_PROCESSED_COUNT             = 'refund_split_payment_processed_count';
    const REFUND_SPLIT_PAYMENT_FAILED_COUNT                = 'refund_split_payment_failed_count';
    const REFUND_SPLIT_PAYMENT_REQUEST_TIME                = 'refund_split_payment_request_time';

    const AUTO_CAPTURE_SPLIT_PAYMENT_COUNT                 = 'autocapture_split_payment_request_count';
    const AUTO_CAPTURE_SPLIT_PAYMENT_PROCESSED_COUNT       = 'autocapture_split_payment_processed_count';
    const AUTO_CAPTURE_SPLIT_PAYMENT_FAILED_COUNT          = 'autocapture_split_payment_failed_count';
    const AUTO_CAPTURE_SPLIT_PAYMENT_REQUEST_TIME          = 'autocapture_split_payment_request_time';

    const VALIDATE_ACCOUNT_UPS_REQUEST_FAILED_COUNT        = 'validate_account_ups_request_failed_count';
    const VALIDATE_VPA_UPS_REQUEST_FAILED_COUNT            = 'validate_vpa_ups_request_failed_count';

    const GET_DCC_INFO_COUNT                              = 'get_dcc_info_count';

    const GET_PAYMENT_FLOWS_COUNT                         = 'get_payment_flows_count';

    const DCC_INFO_ROUTE_COUNT                             = 'dcc_info_route_count';

    const CACHE_MISS_COUNT                                 = 'cache_miss_count';

    const CACHE_HIT_COUNT                                  = 'cache_hit_count';

    const INTL_CARD_SHIELD_REQUEST_ONE_COUNT               = 'intl_card_shield_request_one_count';

    const CURRENCY_EXCHANGE_RATE_CURRENCY_KEY              = 'currency_exchange_rates_currency';

    const CURRENCY_EXCHANGE_RATE_REQUEST_VS_TIME_KEY       = 'currency_exchange_rates_request_vs_time';
    const IMPORT_PAYMENT_VALIDATION_FAILURE                = 'import_payment_validation_failure';


    const CROSS_BORDER_UPDATE_AND_REDIRECT_COUNT           = 'cross_border_update_and_redirect_count';

    const PAYMENT_FETCH_BY_ID_DISTRIBUTION                      = 'payment_fetch_by_id_decomp';

    const AUTO_CAPTURE_RESULT                              = 'auto_capture_result';

    const PAYMENT_CREATE_REQUEST_WITH_OFFER = 'payment_create_request_with_offer';

    public function pushCreateMetrics(Entity $payment)
    {
        $dimensions = $this->getDefaultDimentions($payment);

        $extraDimensions = $this->getPaymentCreatedDimensions($payment);

        $dimensions = array_merge($dimensions, $extraDimensions);

        $this->trace->count(self::PAYMENT_CREATED, $dimensions);
    }

    public function pushFailedMetrics(Entity $payment)
    {
        $dimensions = $this->getDefaultDimentions($payment);

        $extraDimensions = $this->getPaymentFailedDimensions($payment);

        $dimensions = array_merge($dimensions, $extraDimensions);

        $this->trace->count(self::PAYMENT_FAILED, $dimensions);
    }

    public function pushExceptionMetrics(\Throwable $e, string $metricName, array $extraDimensions = [], Entity $payment = null)
    {
        $dimensions = $this->getDefaultExceptionDimensions($e);

        //Adding default dimensions in case payment entity is passed as an argument
        if($payment !== null)
        {
            $defaultDimensions = $this->getDefaultDimentions($payment);

            $dimensions = array_merge($dimensions, $defaultDimensions);
        }

        $dimensions = array_merge($dimensions, $extraDimensions);

        $this->trace->count($metricName, $dimensions);
    }

    public function pushAuthenticationMetrics(Entity $payment)
    {
        $dimensions = $this->getDefaultDimentions($payment);

        $authenticationTime = ($payment->getAuthenticatedTimestamp() - $payment->getCreatedAt());

        $this->trace->histogram(self::PAYMENT_AUTHENTICATED, $authenticationTime, $dimensions);
    }

    public function pushAuthMetrics(Entity $payment)
    {
        $dimensions = $this->getDefaultDimentions($payment);

        $extraDimensions = $this->getPaymentAuthDimensions($payment);

        $dimensions = array_merge($dimensions, $extraDimensions);

        $authTime = ($payment->getAuthorizeTimestamp() - $payment->getCreatedAt());

        $this->trace->histogram(self::PAYMENT_AUTHORIZED, $authTime, $dimensions);
    }

    public function pushCreateRequestTimeMetrics(Entity $payment, int $requestTime)
    {
        $route  = $this->app['api.route']->getCurrentRouteName();

        $dimensions = [
            self::LABEL_PAYMENT_METHOD  => $payment->getMethod(),
            self::PAYMENT_REQUEST_ROUTE => $route,
        ];

        $extraDimensions = $this->getDefaultDimentions($payment);

        $dimensions = array_merge($dimensions, $extraDimensions);

        $this->trace->histogram(self::PAYMENT_CREATE_REQUEST_TIME, $requestTime, $dimensions);
    }


    public function pushCallbackRequestTimeMetrics(Entity $payment, int $requestTime)
    {
        $route  = $this->app['api.route']->getCurrentRouteName();

        $dimensions = [
            self::LABEL_PAYMENT_METHOD  => $payment->getMethod(),
            self::PAYMENT_CALLBACK_ROUTE => $route,
            self::LABEL_PAYMENT_GATEWAY => $payment->getGateway()
        ];

        $this->trace->histogram(self::PAYMENT_CALLBACK_REQUEST_TIME, $requestTime, $dimensions);
    }

    // This metric is added by Optimizer team for instrumenting exceptions in payment callbacks.
    // If needed for other payments, the check can be removed and the optimizer dimension needs to be updated
    public function pushCallbackExceptionMetrics(Entity $payment, \Throwable $ex)
    {
        if (empty($payment) === false &&
            (isset($payment->terminal) && $payment->terminal->isOptimizer()) or
            ($payment->isUpiRecurring() === true)) {

            $dimensions[Metric::PAYMENT_CALLBACK_ROUTE] = $this->app['api.route']->getCurrentRouteName();
            $dimensions[Metric::LABEL_PAYMENT_GATEWAY] = $payment->getGateway();
            $dimensions[Metric::LABEL_PAYMENT_METHOD] = $payment->getMethod();
            $dimensions[Metric::LABEL_OPTIMIZER] = true;

            if($payment->isUpiRecurring() === true)
            {
                $dimensions[Metric::LABEL_OPTIMIZER] = false;
                $dimensions[Metric::LABEL_RECURRING] = $payment->isRecurring();
                $dimensions[Metric::LABEL_RECURRING_TYPE] = $payment->getRecurringType();
            }

            $this->pushExceptionMetrics($ex, Metric::PAYMENT_CALLBACK_PROCESS_FAILED, $dimensions, $payment);
        }
    }

    public function pushCapturedMetrics(Entity $payment)
    {
        $dimensions = $this->getDefaultDimentions($payment);

        $extraDimensions = $this->getPaymentCapturedDimensions($payment);

        $dimensions = array_merge($dimensions, $extraDimensions);

        $captureTime = ($payment->getCapturedAt() - $payment->getCreatedAt());

        $this->trace->histogram(self::PAYMENT_CAPTURED, $captureTime, $dimensions);
    }

    public function pushCaptureQueueMetrics(Entity $payment, $status, array $extraDimensions = [], $exe = null)
    {
        $dimensions = $this->getDefaultDimentions($payment);

        $dimensions = array_merge($dimensions, $extraDimensions);

        $dimensions[self::LABEL_PAYMENT_STATUS] = $status;

        if ($exe !== null)
        {
            $this->pushExceptionMetrics($exe, self::PAYMENT_CAPTURE_QUEUE, $dimensions);
            return;
        }

        $this->trace->count(self::PAYMENT_CAPTURE_QUEUE, $dimensions);
    }

    public function pushCapturedVerifyMetrics(Entity $payment, $status, array $extraDimensions = [], $exe = null)
    {
        $dimensions = $this->getDefaultDimentions($payment);

        $dimensions = array_merge($dimensions, $extraDimensions);

        $dimensions[self::LABEL_PAYMENT_STATUS] = $status;

        if ($exe !== null)
        {
            $this->pushExceptionMetrics($exe, self::PAYMENT_CAPTURED_VERIFY, $dimensions);
            return;
        }

        $this->trace->count(self::PAYMENT_CAPTURED_VERIFY, $dimensions);
    }

    public function pushCheckoutPreferenceRequestMetrics($input, $requestTime)
    {
        if ((isset($input['_']) === true) and
            (isset($input['_']['checkout_id']) === true) and
            ((isset($input['_']['request_index']) === true)) and
            ($input['_']['request_index'] === "0"))
        {
            $dimensions = $this->getCheckoutPreferenceDimensions($input);

            $this->trace->histogram(self::API_CHECKOUT_PREFERENCES_REQUEST_COUNT, $requestTime, $dimensions);
        }
    }

    public function pushCredEligibilityMetrics($input, $response, $exe = null)
    {
        $dimensions = $this->getCredEligibilityDimensions($input, $response);

        if ($exe !== null)
        {
            $exceptionDimensions = $this->getDefaultExceptionDimensions($exe);

            $dimensions = array_merge($dimensions, $exceptionDimensions);
        }

        $this->trace->count(self::CRED_ELIGIBILITY_REQUEST_COUNT, $dimensions);
    }

    public function pushCheckoutSubmitRequestMetrics($input, $requestTime)
    {
        if ((isset($input['_']) === true) and
            (isset($input['_']['checkout_id']) === true) and
            ((isset($input['_']['request_index']) === true)) and
            ($input['_']['request_index'] === "0"))
        {
            $paymentDimensions = $this->getCheckoutSubmitDimensions($input);

            $checkoutDimensions = $this->getCheckoutPreferenceDimensions($input);

            $dimensions = array_merge($paymentDimensions, $checkoutDimensions);

            $this->trace->histogram(self::API_CHECKOUT_SUBMIT_REQUEST_COUNT, $requestTime, $dimensions);
        }
    }

    public function pushKafkaPushSuccessForFailedPaymentMetrics($requestTime)
    {
        $this->trace->histogram(self::KAFKA_PUSH_SUCCESS_FOR_PAYMENT_COUNT, $requestTime, []);
    }

    public function pushKafkaPushFailedForFailedPaymentMetrics($requestTime)
    {
        $this->trace->histogram(self::KAFKA_PUSH_FAILED_FOR_PAYMENT_COUNT, $requestTime, []);
    }

    public function pushKafkaPushSuccessForPaymentSchedulerDeRegistrationMetrics($requestTime)
    {
        $this->trace->histogram(self::PAYMENT_SCHEDULER_DEREGISTER_KAFKA_SUCCESS_COUNT, $requestTime, []);
    }

    public function pushKafkaPushFailedForPaymentSchedulerDeRegistrationMetrics($requestTime)
    {
        $this->trace->histogram(self::PAYMENT_SCHEDULER_DEREGISTER_KAFKA_FAILED_COUNT, $requestTime, []);
    }

    public function pushVerifyViaOldOrNewFlowMetrics($requestTime, $isReminderVerifyPayment, $gateway)
    {
        $dimensions = [
            self::LABEL_PAYMENT_GATEWAY    => $gateway,
            self::IS_VERIFY_NEW_FLOW       => $isReminderVerifyPayment
        ];

        $this->trace->histogram(self::VERIFY_FLOW_NEW_OR_OLD_COUNT, $requestTime, $dimensions);
    }

    public function pushTimeoutViaOldOrNewFlowMetrics($requestTime, $isReminderTimeoutPayment, $method)
    {
        $dimensions = [
            self::LABEL_PAYMENT_METHOD      => $method,
            self::IS_TIMEOUT_NEW_FLOW       => $isReminderTimeoutPayment
        ];

        $this->trace->histogram(self::TIMEOUT_FLOW_NEW_OR_OLD_COUNT, $requestTime, $dimensions);
    }

    protected function getDefaultDimentions(Entity $payment)
    {
        $isCollectXPayment = $payment[Entity::REFERENCE14] === Constant::COLLECTX;

        $dimensions = [
            self::LABEL_PAYMENT_GATEWAY          => $payment->getGateway(),
            self::LABEL_PAYMENT_METHOD           => $payment->getMethod(),
            self::LABEL_PAYMENT_CURRENCY         => $payment->getCurrency(),
            self::LABEL_PAYMENT_INTERNATIONAL    => $payment->isInternational(),
            self::LABEL_PAYMENT_ISSUER           => $payment->getIssuer(),
            self::LABEL_PAYMENT_TRANSACTION_TYPE => $payment->getTransactionType(),
            self::LABEL_PAYMENT_IS_TPV           => $payment->merchant->isTPVRequired(),
            self::LABEL_ORG                      => $payment->merchant->getOrgId(),
            self::LABEL_MERCHANT_COUNTRY_CODE    => $payment->merchant->getCountry(),
            self::LABEL_IS_COLLECTX_PAYMENT      => $isCollectXPayment,
            self::LABEL_ROUTER_CANARY            => app('request.ctx.v2')->routerCanaryContext,
        ];

        $offer = $payment->getOffer();

        if($offer !== null)
        {
            $dimensions += [
                self::LABEL_OFFER           => true,
            ];
        }

        if ($payment->hasCard() === true)
        {
            $card = $payment->card;

            $cardType = $card->getType();

            $network = $card->getNetwork();

            $iin = $card->getIin();

            $tokenised = $card->isTokenPan();
            $altid = false;
            if ($card->getTrivia() === '2') {
                $altid  = true;
            }

            $vault = $card->getVault();

            try
            {
                $authenticationData = (new PaymentService())->getAuthenticationEntity3ds2($payment->getPublicId());
                $protocolVersion = (new PaymentService())->getCardProtcolVersion($authenticationData);
                if (isset($authenticationData['enrollment_status']))
                {
                    $enrolled = $authenticationData['enrollment_status'];
                }
            }
            catch(\Throwable $e)
            {
                $protocolVersion = null;
                $enrolled = null;
            }
        }

        if ($payment->isCardRecurring() === true)
        {
            try
            {
                $token = $payment->localToken;
                if((empty($token) === false) and ($token->hasCardMandate() === true))
                {
                    $cardMandate = $token->cardMandate;
                    $mandateHub  = $cardMandate->getMandateHub();
                }
            }
            catch (\Exception $ex)
            {
                $this->trace->info(
                    TraceCode::FAILED_TO_FETCH_MANDATEHUB_FROM_PAYMENT,
                    [
                        'position' => 'PaymentDefaultDimensions',
                        'message'  => 'Error in fetching Token details',
                    ]);
            }
        }

        if (!empty($payment) && isset($payment->terminal) && $payment->terminal->isOptimizer()) {
            $dimensions[self::LABEL_OPTIMIZER] = true;
        }

        $dimensions += [
            self::LABEL_CARD_NETWORK            => $network  ?? null,
            self::LABEL_CARD_TYPE               => $cardType ?? null,
            self::LABEL_CARD_TOKENISED          => $tokenised ?? null,
            self::LABEL_CARD_ALTID              => $altid ?? null,
            self::LABEL_CARD_VAULT              => $vault ?? null,
            self::LABEL_PAYMENT_MANDATE_HUB     => $mandateHub ?? null,
            self::LABEL_CARD_PROTOCOL_VERSION   => $protocolVersion ?? null,
            self::LABEL_CARD_ENROLLMENT_STATUS  => $enrolled ?? null,
        ];

        $upiDimensions = $this->getDefaultUpiDimensions($payment);

        $dimensions += $upiDimensions;

        $importPaymentDimensions = $this->getDefaultImportPaymentDimensions($payment);
        $dimensions += $importPaymentDimensions;

        return $dimensions;
    }

    protected function getDefaultUpiDimensions(Entity $payment): array
    {
        $upiDimensions = [
            self::LABEL_UPI_FLOW    => null,
            self::LABEL_UPI_PSP     => null,
        ];

        if ($payment->isUpi() === false)
        {
            return $upiDimensions;
        }

        try
        {
            $upi = $payment->getUpiMetadata();

            $upiFlow  = $upi->getFlow();

            $psp = $this->getUpiPsp($upi);

            $upiDimensions[self::LABEL_UPI_FLOW] = $upiFlow ?? null;
            $upiDimensions[self::LABEL_UPI_PSP]  = $psp;
        }
        catch (\Error $exception)
        {
            $this->trace->warning(
                TraceCode::UPI_METRIC_DIMENSION_CREATE_FAILED,
                [
                    'message' => $exception->getMessage()
                ]
            );
        }

        return $upiDimensions;
    }

    protected function getDefaultImportPaymentDimensions(Entity $payment): array
    {
        $importPaymentDimensions = [
            self::LABEL_IMPORT_PAYMENT    => null,
        ];

        try
        {
            if ($payment->merchant->isFeatureEnabled(Constants::ENABLE_IMPORT_FLOW) === true ) {
                $importPaymentDimensions[self::LABEL_IMPORT_PAYMENT] = true;
            }
        }
        catch (\Error $exception)
        {
            $this->trace->warning(
                TraceCode::IMPORT_PAYMENT_DIMENSION_CREATE_FAILED,
                [
                    'message' => $exception->getMessage()
                ]
            );
        }

        return $importPaymentDimensions;
    }

    protected function getUpiPsp($upi)
    {
        $vpa = $upi->getVpa();

        if (isset($vpa) === true)
        {
            return ProviderCode::getPspForVpa($vpa);
        }

        $appName = $upi->getApp();

        if (isset($appName) === true)
        {
            return ProviderCode::getPspForAppName($appName);
        }

        return null;
    }

    public function getDefaultExceptionDimensions(\Throwable $e): array
    {
        $errorAttributes = [];

        if ($e instanceof Exception\BaseException)
        {
            if (($e->getError() !== null) and ($e->getError() instanceof Error))
            {
                $errorAttributes = $e->getError()->getAttributes();
            }
        }
        else
        {
            $errorAttributes = [
                Metric::LABEL_TRACE_CODE         => $e->getCode(),
            ];
        }

        $dimensions = [
            Metric::LABEL_TRACE_CODE                => array_get($errorAttributes, Error::INTERNAL_ERROR_CODE),
            Metric::LABEL_TRACE_FIELD               => array_get($errorAttributes, Error::FIELD),
            Metric::LABEL_TRACE_SOURCE              => array_get($errorAttributes, Error::ERROR_CLASS),
            Metric::LABEL_TRACE_EXCEPTION_CLASS     => get_class($e),
        ];

        return $dimensions;
    }

    protected function getPaymentCreatedDimensions(Entity $payment)
    {
        $dimensions = [
            self::LABEL_PAYMENT_STATUS => $this->getFormattedStatus($payment),
        ];

        return $dimensions;
    }

    protected function getPaymentFailedDimensions(Entity $payment)
    {
        $errorCode = $payment->getInternalErrorCode();

        if($payment->isUnintendedPayment() === true)
        {
            $errorCode = $errorCode . self::UNINTENDED_PAYMENT_ERROR_CODE_SUFFIX;
        }

        $dimensions = [
            self::LABEL_PAYMENT_ERROR_CODE => $errorCode,
        ];

        return $dimensions;
    }

    protected function getPaymentAuthDimensions(Entity $payment)
    {
        $isCollectXPayment = $payment[Entity::REFERENCE14] === Constant::COLLECTX;

        $protocolVersion = null;
        $enrolled = null;

        try {
            if ($payment->hasCard() === true && $payment->isInternational()) {
                $authorization_data = (new PaymentService())->getAuthorizationEntity($payment->getPublicId());
                $protocolVersion = (new PaymentService())->getCardProtcolVersion($authorization_data);

                if (isset($authorization_data['enrollment_status'])) {
                    $enrolled = $authorization_data['enrollment_status'];
                }

                $dimensions = [
                    self::LABEL_PAYMENT_LATE_AUTHORIZED => $payment->isLateAuthorized(),
                    self::LABEL_CARD_ENROLLMENT_STATUS  => $enrolled ?? null,
                    self::LABEL_CARD_PROTOCOL_VERSION   => $protocolVersion ?? null,
                ];
                return $dimensions;
            }
        } catch (\Throwable $e) {
            $this->trace->info(
                TraceCode::PAYMENTS_AUTH_DIMENSIONS_ERROR,
                [
                    'error_message' => $e->getMessage(),
                    'protocol_version' => $protocolVersion,
                    'card_enrollment_status' => $enrolled,
                ]
            );
        }

        $dimensions = [
            self::LABEL_PAYMENT_LATE_AUTHORIZED => $payment->isLateAuthorized(),
            self::LABEL_IS_COLLECTX_PAYMENT     => $isCollectXPayment
        ];
        return $dimensions;
    }

    protected function getPaymentCapturedDimensions(Entity $payment)
    {
        $dimensions = [
            self::LABEL_PAYMENT_AUTO_CAPTURED       => $payment->getAutoCaptured(),
            self::LABEL_PAYMENT_GATEWAY_CAPTURED    => $payment->getGatewayCaptured(),
        ];

        return $dimensions;
    }

    protected function getCheckoutPreferenceDimensions($input)
    {
        $dimensions = [];

        if (isset($input['_']['library']) === true)
        {
            $dimensions[self::LABEL_LIBRARY] = $input['_']['library'];
        }

        return $dimensions;
    }

    protected function getCredEligibilityDimensions($input, $response)
    {
        $dimensions = [];

        $dimensions[self::LABEL_PAYMENT_GATEWAY] = Gateway::CRED;

        $dimensions[self::LABEL_CRED_ELIGIBILITY] = $response['success'];

        $dimensions[self::LABEL_PAYMENT_CURRENCY] = $input['currency'] ?? Currency::INR;

        return $dimensions;
    }

    protected function getCheckoutSubmitDimensions($input)
    {
        $dimensions = [];

        if (isset($input['method']) === true)
        {
            $dimensions[self::LABEL_PAYMENT_METHOD] = $input['method'];

            if(isset($dimensions[self::LABEL_PAYMENT_METHOD]) === true && $dimensions[self::LABEL_PAYMENT_METHOD] ==='upi')
            {
                if(isset($input['flow']) === true)
                {
                    $dimensions[self::LABEL_UPI_FLOW] = $input['flow'];
                }
            }
        }

        return $dimensions;
    }

    protected function getFormattedStatus(Entity $payment)
    {
        return ($payment->getStatus() . '_' . $payment->getInternalErrorCode());
    }

    public function pushCreateMetricsViaPGRouter(array $payment)
    {
        $dimensions = [];

        if (isset($payment['gateway']) === true)
        {
            $dimensions[self::LABEL_PAYMENT_GATEWAY]          = $payment['gateway'];
        }
        if (isset($payment['method']) === true)
        {
            $dimensions[self::LABEL_PAYMENT_METHOD]          = $payment['method'];
        }
        if (isset($payment['currency']) === true)
        {
            $dimensions[self::LABEL_PAYMENT_CURRENCY]          = $payment["currency"];
        }
        if($this->merchant !== null){
            $dimensions[self::LABEL_MERCHANT_COUNTRY_CODE]          = $this->merchant->getCountry();
        }

        $this->trace->count(self::PAYMENT_CREATED_PG_ROUTER, $dimensions);
    }

    public function pushFailedMetricsViaPGRouter(array $payment)
    {
        $dimensions = [];

        if (isset($payment['gateway']) === true)
        {
            $dimensions[self::LABEL_PAYMENT_GATEWAY] = $payment['gateway'];
        }
        if (isset($payment['method']) === true)
        {
            $dimensions[self::LABEL_PAYMENT_METHOD] = $payment['method'];
        }
        if (isset($payment['currency']) === true)
        {
            $dimensions[self::LABEL_PAYMENT_CURRENCY] = $payment["currency"];
        }

        if($this->merchant !== null){
            $dimensions[self::LABEL_MERCHANT_COUNTRY_CODE]  = $this->merchant->getCountry();
        }

        $this->trace->count(self::PAYMENT_FAILED_PG_ROUTER, $dimensions);
    }

    public function pushRequestTimeMetricsViaPGRouter(array $input, int $requestTime, ?Entity $payment)
    {
        $route  = $this->app['api.route']->getCurrentRouteName();

        $dimensions = [
            self::LABEL_PAYMENT_METHOD  => $input['method'],
            self::PAYMENT_REQUEST_ROUTE => $route,
        ];

        if (empty($payment) === false)
        {
            $dimensions[self::LABEL_PAYMENT_GATEWAY] = $payment->getGateway();
            $dimensions[self::LABEL_PAYMENT_METHOD]  = $payment->getMethod();

            if (isset($payment->terminal) && $payment->terminal->isOptimizer())
            {
                $dimensions[self::LABEL_OPTIMIZER] = true;
            }
        }

        $this->trace->histogram(self::PAYMENT_CREATE_REQUEST_TIME_PG_ROUTER, $requestTime, $dimensions);
    }

    public function pushDccInfoRedirectMetrics(string $success, string $error)
    {
        $this->trace->count(
            Metric::DCC_INFO_ROUTE_COUNT, [
            "success" => $success,
            "error" => $error
        ]);
    }

    public function pushAutoCaptureResultMetrics(Entity $payment, $errorMessage)
    {
        $dimensions = $this->getDefaultDimentions($payment);

        $extraDimensions = [
            self::LABEL_SHOULD_AUTO_CAPTURE         => true,
            self::LABEL_PAYMENT_AUTO_CAPTURED       => $payment->getAutoCaptured(),
            self::LABEL_PAYMENT_GATEWAY_CAPTURED    => $payment->getGatewayCaptured(),
            self::LABEL_PAYMENT_STATUS              => $payment->getStatus(),
            self::LABEL_AUTO_CAPTURE_ERROR          => $errorMessage,
        ];

        $dimensions = array_merge($dimensions, $extraDimensions);

        $this->trace->count(self::AUTO_CAPTURE_RESULT, $dimensions);
    }

    public function pushOfferMetrics(Entity $payment,array $input)
    {
        $route  = $this->app['api.route']->getCurrentRouteName();

        $dimensions = $this->getDefaultDimentions($payment);

        $dimensions += [
            self::PAYMENT_REQUEST_ROUTE => $route,
        ];

        $this->trace->count(self::PAYMENT_CREATE_REQUEST_WITH_OFFER, $dimensions);
    }

}
