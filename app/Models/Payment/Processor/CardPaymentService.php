<?php

namespace RZP\Models\Payment\Processor;

use RZP\Constants\Environment;
use RZP\Gateway\Base\Metric;
use RZP\Diag\EventCode;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Models\Admin;
use RZP\Models\Feature;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Card\IIN;
use RZP\Services\Doppler;
use RZP\Gateway\Base\Action;
use Razorpay\Trace\Logger as Trace;

trait CardPaymentService
{

    public function canAuthorizeViaCps(Payment\Entity $payment): bool
    {
        if (($payment->isMethodCardOrEmi() === false) or
            ($payment->isGooglePayCard() === true))
        {
            return false;
        }

        $routeName = $this->app['request.ctx']->getRoute();
        $this->isJsonRoute = $this->app['api.route']->isJsonRoute($routeName);


        if ($this->app['api.route']->isJsonRoute($routeName) === true)
        {
            return false;
        }

        if ($this->isRupayNetwork($payment) === true)
        {
            return false;
        }

        if ($this->isCardPaymentServiceConfigEnabled() === false)
        {
            return false;
        }

        if ($this->app['env'] === Environment::PRODUCTION)
        {
            return false;
        }

        $variant = $this->app->razorx->getTreatment($payment->getMerchantId(), self::CARD_PAYMENTS_AUTHORIZE_ALL_TERMINALS, $this->mode);

        $this->trace->info(TraceCode::CPS_RAZORX_VARIANT, [
            'payment_id'     => $payment->getId(),
            'merchant_id'    => $payment->getMerchantId(),
            'razorx_variant' => $variant,
        ]);

        if (strtolower($variant) !== 'on')
        {
            return false;
        }

        //Added app['processor.cps'] to make canAuthorizeViaCps false for testcases as this flow is abandoned in production
        try
        {
            if($this->app['processor.cps']!=null)
            {
                return $this->app['processor.cps']->canAuthorizeViaCps($payment);
            }
        }
        catch (\Throwable $e)
        {
            return true;
        }

        return true;
    }

    public function authorizeViaCps(Payment\Entity $payment, array $input, array $gatewayInput)
    {
        $this->runPostGatewaySelectionPreProcessingViaCps($payment, $gatewayInput);

        $request = $this->validateAndReturnRedirectResponseIfApplicable($payment, [], $gatewayInput);

        if ($request !== null)
        {
            return $request;
        }

        return $this->callCpsAuthorizeAcrossTerminals($payment, $gatewayInput);
    }

    public function callCpsAction($payment, $gateway, $action, $gatewayData)
    {
        $statusCode = null;
        if ($action === Action::FORCE_AUTHORIZE_FAILED and (in_array($gateway, Payment\Gateway::FORCE_AUTHORIZE_FAILED_SYNC_GATEWAYS, true) !== true))
        {
            return $this->app['card.payments']->forceAuthorizeFailed($gateway, $action, $gatewayData);
        }

        try
        {
            $response = $this->app['card.payments']->action($gateway, $action, $gatewayData);

            $statusCode = (empty($response['status_code']) === true) ? 0 : $response['status_code'];

            if((in_array($gateway, Payment\Gateway::FORCE_AUTHORIZE_FAILED_SYNC_GATEWAYS, true) === true) and $action === Action::FORCE_AUTHORIZE_FAILED)
            {
                $this->handleCpsResponse($payment, $response);
                return isset($response['data']) ? 1 : 0;
            }

            $this->updatePaymentFromCpsResponse($payment, $response);

            $this->handleDisableIIN($payment, $response);

            $this->handleCpsResponse($payment, $response);
            // If action is verify we get verify trace data
            if (($action === Action::VERIFY) or ($action === Action::AUTHORIZE_FAILED) or ($action === Action::FORCE_AUTHORIZE_FAILED))
            {
                return $response;
            }

            $this->pushDimensions($action, $gatewayData, Metric::SUCCESS, $gateway, null, $statusCode);

            return $response['data'];
        }
        catch (\Throwable $exc)
        {
            $previousExc = $exc->getPrevious();

            if (($previousExc instanceof \WpOrg\Requests\Exception) and
                    ($previousExc->getType() === 'curlerror'))
            {
                $excData = curl_errno($previousExc->getData());

                $this->pushDimensions($action, $gatewayData, Metric::CURL_ERROR, $gateway, $excData, $statusCode);
            }
            else
            {
                $excData = 'UKNOWN';

                if($exc instanceof Exception\BaseException)
                {
                    $excData = $exc->getError()->getClass();
                }

                if ($this->isPaysecureUnknowCapture($action, $gateway, $excData) === true)
                {
                    $this->trace->traceException(
                        $exc,
                        Trace::ERROR,
                        TraceCode::CARD_PAYMENT_SERVICE_PAYSECURE_CAPTURE_UNKNOWN_ERROR,
                        [
                            'action'    => $action ?? 'none',
                            'gateway'   => $gateway ?? 'none',
                            'excData'   => $excData,
                            'payment_id'  => $payment->getId(),
                        ]);
                }

                $this->pushDimensions($action, $gatewayData, Metric::FAILED, $gateway, $excData, $statusCode);
            }

            throw $exc;
        }
    }

    private function isPaysecureUnknowCapture($action, $gateway, $excData): bool
    {
        if (is_null($action) or is_null($gateway) or is_null($excData))
        {
            return false;
        }

        return $excData === 'UKNOWN' and $action === Action::CAPTURE and $gateway === Payment\Gateway::PAYSECURE;
    }

    protected function pushDimensions($action, $input, $status, $gateway, $excData = null, $statusCode = null)
    {
        if (($this->mode === Mode::TEST) and
            ($this->app->runningUnitTests() === false))
        {
            return;
        }

        $gatewayMetric = new Metric;

        $gatewayMetric->pushGatewayDimensions($action, $input, $status, $gateway, $excData, $statusCode);

        $gatewayMetric->pushOptimiserGatewayDimensions($action,$input,$status,$gateway,$excData,$statusCode);
    }

    protected function callCpsAuthorizeAcrossTerminals(Payment\Entity $payment, array $data)
    {
        $response = [];

        try
        {
            $response = $this->app['card.payments']->authorizeAcrossTerminals($payment, $data, $this->selectedTerminals);

            $this->updatePaymentFromCpsResponse($payment, $response);

            $this->handleDisableIIN($payment, $response);

            $this->handleCpsResponse($payment, $response);

            return $response['data'];
        }
        catch (Exception\BaseException $e)
        {
            $error = $e->getError();

            $errorCode = $error->getPublicErrorCode();

            $internalErrorCode = $error->getInternalErrorCode();

            $error->setDetailedError($internalErrorCode, Payment\Method::CARD);

            $step = $error->getStep();

            $source = $error->getSource();

            $reason = $error->getReason();

            $internalErrorDetails = [
                'step'                  => $step,
                'reason'                => $reason,
                'source'                => $source,
                'internal_error_code'   => $internalErrorCode,
            ];

            //TODO: Remove this later
            try
            {
                $this->app->doppler->sendFeedback($payment, Doppler::PAYMENT_AUTHORIZATION_FAILURE_EVENT, $errorCode, $internalErrorDetails);
            }
            catch (\Throwable $ex)
            {
                $this->trace->info(
                    TraceCode::DOPPLER_SERVICE_SNS_PUBLISH_FAILED,
                    [
                        'payment'             => $payment->toArray(),
                        'code'                => $errorCode,
                        'internal_code'       => $internalErrorCode,
                        'error'               => $ex->getMessage()
                    ]
                );
            }

            // An error occurred on gateway due to user or gateway.
            // We need to record this and mark payment as failed.
            //
            $terminalData['exception'] = $e;

            $this->migrateCardDataIfApplicable($payment);

            $this->logRiskFailureForGateway($payment, $internalErrorCode);

            $this->updatePaymentOnExceptionAndThrow($e);
        }

        return $response;
    }

    // This function updates payment entity with optimizer specific data.
    protected function updatePaymentWithOptimizerGatewayData($payment, $response)
    {
        // check optimizer_gateway_data is passsed in CPS response
        if ((empty($response) === true) or (empty($response[Constants::DATA]) === true) or
            (empty($response[Constants::DATA][Constants::OPTIMIZER_GATEWAY_DATA]) === true)) {
            return;
        }

        // check if merchant is Optimizer
        if ($payment->merchant->isFeatureEnabled(Features::RAAS) === false or
            $payment->terminal->isOptimizer() === false) {
            return;
        }

        $optimizerData = $response[Constants::DATA][Constants::OPTIMIZER_GATEWAY_DATA];

        // Append notes. Razorx is done at CPS level, and data is sent based on that. So no repetitive razorx call.
        if ((empty($optimizerData) === false) and
            (empty($optimizerData[Payment\Entity::NOTES]) === false)) {

            $newNotes = json_decode($optimizerData[Payment\Entity::NOTES], TRUE);
            $payment->appendNotes($newNotes);

            $this->trace->info(TraceCode::OPTIMIZER_GATEWAY_DATA, [
                'payment_id' => $payment->getId(),
                'action' => 'append_notes',
            ]);
        }
    }

    protected function updatePaymentFromCpsResponse($payment, $response)
    {
        $this->updatePaymentWithOptimizerGatewayData($payment, $response);

        if ((empty($response) === true) or
            (empty($response['payment']) === true))
        {
            return;
        }

        if (is_array($response['payment']) === false)
        {
            return;
        }

        $input = $response['payment'];

        // set Terminal
        if (empty($input['terminal_id']) === false)
        {
            foreach ($this->selectedTerminals as $terminal)
            {
                if ($terminal->getId() === $input['terminal_id'])
                {
                    $payment->associateTerminal($terminal);
                }
            }

            unset($input['terminal_id']);
        }

        $payment->edit($input, 'edit_cps_response');

        $this->repo->saveOrFail($payment);

        $this->tracePaymentInfo(TraceCode::PAYMENT_UPDATED_WITH_CPS_RESPONSE, Trace::DEBUG);
    }

    protected function handleCpsResponse($payment, $response)
    {
        if (empty($response) === true)
        {

            $this->trace->info(TraceCode::CARD_PAYMENT_SERVICE_DISABLING, [
                'payment_id'  => $payment->getId(),
                'response'    => $response,
            ]);

            // (new Admin\Service)->setConfigKey([Admin\ConfigKey::CARD_PAYMENT_SERVICE_ENABLED => 0]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED
            );
        }

        if (isset($response['status_code']) and $response['status_code'] == 401)
        {
            throw new Exception\ServerErrorException('CPS Authentication Failed', ErrorCode::SERVER_ERROR_CARD_PAYMENT_SERVICE_FAILURE);
        }

        $this->app['card.payments']->checkForErrors($response);
    }


    public function runPostGatewaySelectionPreProcessingViaCps(Payment\Entity $payment, array & $gatewayInput)
    {

        // not sure how to run this
        // $this->verifyFeesLessThanAmount($payment);

        $this->setAuthenticationGatewayForSelectedTerminals($payment, $gatewayInput);

        $payment->enableCardPaymentService();

        if ($payment->getIsPushedToKafka() === null)
        {
            $isPushedToKafka = $this->pushPaymentToKafkaForVerify($this->payment);

            $payment->setIsPushedToKafka($isPushedToKafka);
        }

        $this->repo->saveOrFail($payment);

        $this->eventPaymentCreated();

        $this->tracePaymentInfo(TraceCode::PAYMENT_CREATED, Trace::DEBUG);

        $gatewayInput['payment'] = $payment->toArrayGateway();
        $gatewayInput['callbackUrl'] = $this->getCallbackUrl();
        $gatewayInput['otpSubmitUrl'] = $this->getOtpSubmitUrl();
        $gatewayInput['payment_analytics'] = $payment->getMetadata('payment_analytics');

        // set token for local card saving in gateway input
        $gatewayInput['token'] = $payment->getGlobalOrLocalTokenEntity();
    }

    public function setAuthenticationGatewayForSelectedTerminals(Payment\Entity $payment, array & $gatewayInput)
    {
        $this->trace->info(
            TraceCode::AUTH_SELECTION_VIA_GATEWAY_RULES_V2,
            [
                'payment_id'        => $payment->getId(),
            ]);

        $authTerminals = (new TerminalProcessor)->selectAuthenticationGatewayForTerminals($payment, $this->selectedTerminals);

        $this->trace->info(TraceCode::AUTH_SELECTION_FOR_TERMINALS_V2, [
            'payment_id'                => $payment->getId(),
            'authentication_terminals'  => $authTerminals,
        ]);

        $gatewayInput['authentication_terminals'] = $authTerminals;
    }

    // Handle response for headless and ivr payments where we disable iin
    protected function handleDisableIIN($payment, $response)
    {
        if (isset($response["headless"]["disable_iin"])
            and $response["headless"]["disable_iin"] === true )
        {
            $this->disableIinFlowIfApplicable($payment, TraceCode::HEADLESS_OTP_ELF_FAILURE);
        }

        if (isset($response["ivr"]["disable_iin"])
            and $response["ivr"]["disable_iin"] === true )
        {
            $this->disableIinFlowIfApplicable($payment, ErrorCode::GATEWAY_ERROR_IVR_AUTHENTICATION_NOT_AVAILABLE);
        }

    }
}
