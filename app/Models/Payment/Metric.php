<?php

namespace RZP\Models\Payment;

use App;

use RZP\Exception;
use RZP\Error\Error;
use RZP\Models\Base;
use RZP\Models\Currency\Currency;
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
    const LABEL_CARD_VAULT                      = 'card_vault';
    const LABEL_CARD_PROTOCOL_VERSION           = 'card_protocol_version';
    const LABEL_CARD_ENROLLMENT_STATUS          = 'card_enrolled';
    const LABEL_PAYMENT_LATE_AUTHORIZED         = 'late_authorized';
    const LABEL_PAYMENT_AUTO_CAPTURED           = 'auto_captured';
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

    const IS_VERIFY_NEW_FLOW                    = 'is_verify_new_flow';
    const IS_TIMEOUT_NEW_FLOW                   = 'is_timeout_new_flow';


    // Metric Names
    const PAYMENT_CREATED                       = 'payment_created';
    const PAYMENT_CREATED_PG_ROUTER             = 'payment_created_pg_router';
    const PAYMENT_AUTHENTICATED                 = 'payment_authenticated';
    const PAYMENT_AUTHORIZED                    = 'payment_authorized_v1';
    const PAYMENT_CAPTURED                      = 'payment_captured_v1';
    const PAYMENT_CAPTURE_QUEUE                 = 'payment_capture_queue';
    const PAYMENT_CAPTURED_VERIFY               = 'payment_captured_verify';
    const PAYMENT_CREATE_REQUEST_TIME           = 'payment_create_request_time';
    const PAYMENT_CALLBACK_REQUEST_TIME         = 'payment_callback_request_time';
    const PAYMENT_UPI_CALLBACK_REQUEST_TIME     = 'payment_upi_callback_request_time';
    const PAYMENT_CREATE_REQUEST_TIME_PG_ROUTER = 'payment_create_request_time_pg_router';
    const PAYMENT_FAILED                        = 'payment_failed';
    const PAYMENT_FAILED_PG_ROUTER              = 'payment_failed_pg_router';
    const PAYMENT_PROCESS_FAILED                = 'payment_process_failed';
    const PAYMENT_CAPTURE_FAILED                = 'payment_capture_failed';
    const PAYMENT_REQUEST_ROUTE                 = 'payment_request_route';
    const PAYMENT_CALLBACK_ROUTE                = 'payment_callback_route';
    const SHIELD_FRAUD_DETECTION_FAILED         = 'shield_fraud_detection_failed';
    const SHIELD_FRAUD_DETECTION_SKIPPED        = 'shield_fraud_detection_skipped';
    const SHIELD_INTEGRATION_ERROR              = 'shield_integration_error';

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
        $dimensions = [
            self::LABEL_PAYMENT_GATEWAY          => $payment->getGateway(),
            self::LABEL_PAYMENT_METHOD           => $payment->getMethod(),
            self::LABEL_PAYMENT_ISSUER           => $payment->getIssuer(),
            self::LABEL_PAYMENT_CURRENCY         => $payment->getCurrency(),
            self::LABEL_PAYMENT_INTERNATIONAL    => $payment->isInternational(),
            self::LABEL_PAYMENT_TRANSACTION_TYPE => $payment->getTransactionType(),
            self::LABEL_PAYMENT_IS_TPV           => $payment->merchant->isTPVRequired(),
            self::LABEL_ORG                      => $payment->merchant->getOrgId(),
            self::LABEL_MERCHANT_COUNTRY_CODE       => $payment->merchant->getCountry(),
        ];

        if ($payment->hasCard() === true)
        {
            $card = $payment->card;

            $cardType = $card->getType();

            $network = $card->getNetwork();

            $iin = $card->getIin();

            $tokenised = $card->isTokenPan();

            $vault = $card->getVault();

            try
            {
                $authenticationData = (new PaymentService())->getAuthenticationEntity3ds2($payment->getPublicId());
                $protocolVersion = $this->getCardProtcolVersion($authenticationData);
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

        $dimensions += [
            self::LABEL_CARD_NETWORK            => $network  ?? null,
            self::LABEL_CARD_TYPE               => $cardType ?? null,
            self::LABEL_CARD_TOKENISED          => $tokenised ?? null,
            self::LABEL_CARD_VAULT              => $vault ?? null,
            self::LABEL_PAYMENT_MANDATE_HUB     => $mandateHub ?? null,
            self::LABEL_CARD_PROTOCOL_VERSION   => $protocolVersion ?? null,
            self::LABEL_CARD_ENROLLMENT_STATUS  => $enrolled ?? null,
        ];

        $upiDimensions = $this->getDefaultUpiDimensions($payment);

        $dimensions += $upiDimensions;

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

    protected function getCardProtcolVersion($authenticationData)
    {
        if (isset($authenticationData['protocol_version'])) {
            if($authenticationData['protocol_version'] == '2.1.0' || $authenticationData['protocol_version'] == '2.2.0'){
                return "3DS2";
            }
            else {
                return "3DS1";
            }
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
        $dimensions = [
            self::LABEL_PAYMENT_LATE_AUTHORIZED => $payment->isLateAuthorized(),
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

    public function pushRequestTimeMetricsViaPGRouter(array $payment, int $requestTime)
    {
        $route  = $this->app['api.route']->getCurrentRouteName();

        $dimensions = [
            self::LABEL_PAYMENT_METHOD  => $payment['method'],
            self::PAYMENT_REQUEST_ROUTE => $route,
        ];

        $this->trace->histogram(self::PAYMENT_CREATE_REQUEST_TIME_PG_ROUTER, $requestTime, $dimensions);
    }
}
