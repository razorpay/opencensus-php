<?php

namespace RZP\Models\Payment\Processor;

use RZP\Diag\EventCode;
use RZP\Exception;
use RZP\Models\Admin;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Card\IIN;
use RZP\Gateway\Base\Action;
use Razorpay\Trace\Logger as Trace;

trait CardPaymentService
{

    public function canAuthorizeViaCps(Payment\Entity $payment): bool
    {
        if ($payment->isMethodCardOrEmi() == false)
        {
            return false;
        }

        $routeName = $this->app['request.ctx']->getRoute();
        $this->isS2SJsonRoute = $this->app['api.route']->isS2SJsonRoute($routeName);


        if ($this->app['api.route']->isS2SJsonRoute($routeName) === true)
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
        $response = $this->app['card.payments']->action($gateway, $action, $gatewayData);

        $this->updatePaymentFromCpsResponse($payment, $response);

        $this->handleCpsResponse($payment, $response);

        // If action is verify we get verify trace data
        if ($action === Action::VERIFY)
        {
            return $response;
        }

        return $response['data'];
    }

    protected function callCpsAuthorizeAcrossTerminals(Payment\Entity $payment, array $data)
    {
        $response = [];

        try
        {
            $response = $this->app['card.payments']->authorizeAcrossTerminals($payment, $data, $this->selectedTerminals);

            $this->updatePaymentFromCpsResponse($payment, $response);

            $this->handleCpsResponse($payment, $response);

            return $response['data'];
        }
        catch (Exception\BaseException $e)
        {
            $errorCode = $e->getError()->getPublicErrorCode();

            $internalErrorCode = $e->getError()->getInternalErrorCode();

            //TODO: Remove this later
            try
            {
                $this->app->doppler->sendFeedback($payment, Doppler::PAYMENT_AUTHORIZATION_FAILURE_EVENT, $errorCode, $internalErrorCode);
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

    protected function updatePaymentFromCpsResponse($payment, $response)
    {
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

            // (new Admin\Service)->setConfigKeys([Admin\ConfigKey::CARD_PAYMENT_SERVICE_ENABLED => 0]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED
            );
        }

        $response = $this->app['card.payments']->checkForErrors($response);
    }


    public function runPostGatewaySelectionPreProcessingViaCps(Payment\Entity $payment, array & $gatewayInput)
    {

        // not sure how to run this
        // $this->verifyFeesLessThanAmount($payment);

        $this->setAuthenticationGatewayForSelectedTerminals($payment, $gatewayInput);

        $payment->enableCardPaymentService();

        $this->repo->saveOrFail($payment);

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
}
