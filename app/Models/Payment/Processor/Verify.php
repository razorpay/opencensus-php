<?php

namespace RZP\Models\Payment\Processor;

use App;
use Config;

use RZP\Error\Error;
use RZP\Exception;
use RZP\Diag\EventCode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Models\Payment\Verify\Status as VerifyStatus;
use RZP\Models\Payment\Verify\Action as VerifyAction;
use \RZP\Models\Feature;

trait Verify
{
    /**
     * Run Verify on a given Payment
     *
     * @param Payment\Entity $payment Payment for which verify should be ran
     * @param array $gatewayData Additional gateway data if required for verify
     *
     * @return array having refund and payment data
     * @throws Exception\PaymentVerificationException
     * @throws \Exception
     */
    public function verify(Payment\Entity $payment, array $gatewayData = null, $isBarricade = false)
    {
        $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_INITIATED, $payment);

        $this->setPayment($payment);

        if ($isBarricade === true)
        {
            $data = [
                'payment' => $payment->toArrayGateway(),
                'merchant' => $this->merchant,
            ];
        }
        else {
            $refunds = $this->repo->refund->findForPayment($payment);

            $data = [
                'payment' => $payment->toArrayGateway(),
                'refunds' => $refunds->toArrayGateway(),
                'merchant' => $this->merchant,
            ];
        }

        if (isset($gatewayData) === true)
        {
            $data['gateway_data'] = $gatewayData;
        }

        if ($payment->isMethodCardOrEmi())
        {
            $data['card'] = $this->repo->card->fetchForPayment($payment)->toArray();
        }

        if ($payment->isUpi())
        {
            $upi = $this->repo->upi->fetchByPaymentId($payment->getId());

            if ($upi !== null)
            {
                $data['upi'] = [
                    'flow'         => $upi['type'],
                    'expiry_time'  => $upi['expiry_time'],
                ];
            }

            // Upi for OTM and Recurring need to send extra information in verify
            $this->modifyGatewayInputForUpi($payment, $data);
        }

        $customProperties = [
            'verify_route' => 'verify/all',
        ];

        // So that verification calls can be made with the relevant token related information
        if ($payment->getGlobalOrLocalTokenEntity())
        {
            $data['token'] = $payment->getGlobalOrLocalTokenEntity();
        }

        try
        {
            // if barricade flow directly return response
            if ($isBarricade === true)
            {
                 $data['gateway'] = $this->callGatewayFunction(Payment\Action::VERIFY_GATEWAY, $data);
                 $this->trace->info(
                                 TraceCode::PAYMENT_VERIFY_RESPONSE,
                                 $data['gateway']);
                 return $data;
            }

            $data['gateway'] = $this->callGatewayFunction(Payment\Action::VERIFY, $data);

            $this->updatePaymentVerified($payment, VerifyStatus::SUCCESS, $data['gateway']);

            $data['payment'] = $payment->toArrayAdmin();

            $customProperties += [
                'payment_id' => $payment->getId(),
                'bucket'=> $payment->getVerifyBucket(),
                'error_code' => $payment->getErrorCode(),
                'internal_error_code' => $payment->getInternalErrorCode(),
                'error_desc' => $payment->getErrorDescription(),
                'verify_response' => $data['gateway'],
                'verify_status' => 'success',
            ];

            $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_PROCESSED, $payment, null, $customProperties);
        }
        catch (Exception\PaymentVerificationException $e)
        {
            $action = $e->getAction();

            $customProperties += [
                'internal_error_code' => $payment->getInternalErrorCode(),
                'error_desc' => $payment->getErrorDescription(),
                'exception_error_code' => $e->getCode(),
                'verify_action' => $action,
            ];

            // If action is BLOCK, RETRY, FINISH we don't update Verify Status
            if ($action === null)
            {
                $this->updatePaymentVerified($payment, VerifyStatus::FAILED, $e->getData());

                $customProperties += [
                    'verify_status' => 'failed',
                    'exception_data'=> $e->getData(),
                ];
            }
            else
            {
                $this->updatePaymentVerified($payment, VerifyStatus::UNKNOWN);

                $customProperties += [
                    'verify_status' => 'unknown'
                ];
            }

            $this->trace->info(
                TraceCode::PAYMENT_VERIFY_FAILED,
                $e->getData());

            $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_PROCESSED, $payment, $e, $customProperties);

            throw $e;
        }
        catch (\Exception $e)
        {
            $this->updatePaymentVerified($payment, VerifyStatus::ERROR);

            $customProperties += [
                'verify_status' => 'error',
                'internal_error_code' => $payment->getInternalErrorCode(),
                'error_desc' => $payment->getErrorDescription(),
                'exception_error_code' => $e->getCode(),
            ];

            $this->trace->info(
                TraceCode::PAYMENT_VERIFY_FAILED,
                $customProperties);

            $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_PROCESSED, $payment, $e, $customProperties);

            throw $e;
        }
        catch (\Error $e)
        {
            $this->updatePaymentVerified($payment, VerifyStatus::ERROR);

            $customProperties += [
                'verify_status' => 'error',
                'internal_error_code' => $payment->getInternalErrorCode(),
                'error_desc' => $payment->getErrorDescription(),
                'exception_error_code' => $e->getCode(),
            ];

            $this->trace->info(
                TraceCode::PAYMENT_VERIFY_FAILED,
                $customProperties);

            $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_PROCESSED, $payment, $e, $customProperties);

            throw $e;
        }

        $this->trace->info(
            TraceCode::PAYMENT_VERIFY_EVENT_DATA,
            $this->getTracableVerifyProperties($customProperties));

        return $data;
    }

    /**
     * Run Verify on a given Payment by new route, where the verify cron will stop
     * based on terminal error codes.
     *
     * @param Payment\Entity $payment
     * @param array|null $gatewayData
     * @return array
     * @throws \Exception
     */
    public function verifyNewRoute(Payment\Entity $payment, string $verifyRoute = 'verify/new_cron', array $gatewayData = null)
    {
        $extraProperties = [
            'is_pushed_to_kafka'  => $payment->getIsPushedToKafka(),
        ];

        $this->trace->info(
            TraceCode::PAYMENT_VERIFICATION_INITIATED,
            [
                'payment_id' => $payment->getId(),
                'gateway'    => $payment->getGateway(),
            ]);

        $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_INITIATED, $payment, null, $extraProperties);

        $this->setPayment($payment);

        $data = $this->populateVerifyData($payment, $gatewayData);

        $customProperties = [
            'bucket'=> $payment->getVerifyBucket(),
            'payment_id' => $payment->getId(),
            'verify_route' => $verifyRoute,
        ];

        $finalException = $this->callVerification($payment, $data, $customProperties);

        if ($finalException !== null)
        {
            $customProperties += $extraProperties;

            $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_PROCESSED,
                $payment, $finalException, $customProperties);


            throw $finalException;
        }
        else
        {
            $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_PROCESSED,
                $payment, null, $customProperties);

        }

        $this->trace->info(
            TraceCode::PAYMENT_VERIFY_EVENT_DATA,
            [
                'event_properties' => $this->getTracableVerifyProperties($customProperties),
            ]);

        return $data;
    }

    /**
     * Populate verification data and return array
     *
     * @param Payment\Entity $payment
     * @param array $gatewayData
     * @return array
     */
    protected function populateVerifyData(Payment\Entity $payment, &$gatewayData) : array
    {
        $data = [
            'payment' => $payment->toArrayGateway(),
            'merchant' => $this->merchant,
        ];

        if (isset($gatewayData) === true)
        {
            $data['gateway_data'] = $gatewayData;
        }

        if ($payment->isMethodCardOrEmi())
        {
            $data['card'] = $this->repo->card->fetchForPayment($payment)->toArray();
        }

        if ($payment->isUpi())
        {
            $upi = $this->repo->upi->fetchByPaymentId($payment->getId());

            if ($upi !== null)
            {
                $data['upi'] = [
                    'flow'         => $upi['type'],
                    'expiry_time'  => $upi['expiry_time'],
                ];
            }

            // Upi for OTM and Recurring need to send extra information in verify
            $this->modifyGatewayInputForUpi($payment, $data);
        }

        // So that verification calls can be made with the relevant token related information
        if ($payment->getGlobalOrLocalTokenEntity())
        {
            $data['token'] = $payment->getGlobalOrLocalTokenEntity();
        }

        return $data;
    }

    /**
     * Call verify on gateway and return if any exception occured.
     * @param Payment\Entity $payment
     * @param array $data
     * @return \Exception
     */
    protected function callVerification(Payment\Entity $payment, array &$data, array &$eventData=[])
    {
        $finalException = null;

        try
        {
            $data['gateway'] = $this->callGatewayFunction(Payment\Action::VERIFY, $data);

            $this->updatePaymentVerified($payment, VerifyStatus::SUCCESS, $data['gateway']);

            $data['payment'] = $payment->toArrayAdmin();

            $eventData += [
                'internal_error_code' => $payment->getInternalErrorCode(),
                'error_desc' => $payment->getErrorDescription(),
                'verify_response' => $data['gateway'],
                'verify_status' => 'success',
            ];
        }
        catch (Exception\PaymentVerificationException $e)
        {
            $this->handlePaymentVerificationException($payment, $e, $eventData);

            $finalException = $e;
        }
        catch (\Exception $e)
        {
            $this->updatePaymentVerified($payment, VerifyStatus::ERROR);

            $finalException = $e;

            $eventData += [
                'internal_error_code' => $payment->getInternalErrorCode(),
                'error_desc' => $payment->getErrorDescription(),
                'exception_error_code' => $e->getCode(),
                'exception_message' => $e->getMessage(),
                'verify_status' => 'error',
            ];
        }

        return $finalException;
    }

    /**
     * PaymentVerificationException handler
     *
     * @param Payment\Entity $payment
     * @param Exception\PaymentVerificationException $e
     */
    protected function handlePaymentVerificationException(Payment\Entity $payment,
                                                          Exception\PaymentVerificationException &$e,
                                                          array &$eventData=[])
    {
        $errorCodeNonVerifiable = $this->finishVerifyIfApplicable($payment, $e);

        $action = $e->getAction();

        // If action is BLOCK, RETRY we don't update Verify Status
        if ($action === null)
        {
            $this->updatePaymentVerified($payment, VerifyStatus::FAILED, $e->getData());

            $eventData += [
                'verify_status' => 'failed',
            ];
        }
        else if ($action === VerifyAction::FINISH)
        {
            $this->verifyFinishAction($payment, $e);

            $eventData += [
                'verify_status' => 'failed',
            ];
        }
        else
        {
            $this->updatePaymentVerified($payment, VerifyStatus::UNKNOWN);

            $eventData += [
                'verify_status' => 'unknown',
            ];
        }

        $eventData += [
            'verify_action' => $e->getAction(),
            'internal_error_code' => $payment->getInternalErrorCode(),
            'error_desc' => $payment->getErrorDescription(),
            'exception_error_code' => $e->getCode(),
            'exception_message' => $e->getMessage(),
            'error_code_non_verifiable' => $errorCodeNonVerifiable,
        ];

        $this->trace->info(TraceCode::PAYMENT_VERIFY_EVENT_DATA, $this->getTracableVerifyProperties($eventData));
    }

    /**
     * Exception Handler for VerifyAction::Finish
     *
     * @param Payment\Entity $payment
     * @param Exception\PaymentVerificationException $e
     */
    protected function verifyFinishAction(Payment\Entity $payment,
                                          Exception\PaymentVerificationException &$e)
    {

        $internalErrorCode = $payment->getInternalErrorCode();

        if($internalErrorCode != null)
        {
            $this->updatePaymentVerified($payment, VerifyStatus::FAILED);
        }
        else
        {
            $errorData['error'] = $e->getError();

            $this->updatePaymentVerified($payment, VerifyStatus::FAILED, $errorData);
        }

        $this->trace->info(TraceCode::PAYMENT_VERIFY_FAILED,
            [
                'payment_id' => $payment->getId(),
                'internal_error_code' => $internalErrorCode,
                'updating error codes for Finish verify action' => $e->getData(),
            ]);
    }

    /**
     * Set verify action as Finish if the internal error code mapping is final
     * as determined from the error code verifiable mapping files.
     *
     * @param Payment\Entity $payment
     * @param Exception\PaymentVerificationException $e
     */
    protected function finishVerifyIfApplicable(Payment\Entity $payment,
                                                Exception\PaymentVerificationException &$e) : bool
    {
        $finalErrorCode = $payment->getInternalErrorCode();

        if(isset($finalErrorCode) === false)
        {
            $finalErrorCode = $e->getCode();
        }

        $isOptimizerPayment = false;

        $terminalTypeArray = array();

        if ($payment->hasTerminal() === true)
        {
            $terminalTypeArray = $payment->terminal->getType();

            if (($terminalTypeArray != null) && (in_array('optimizer', $terminalTypeArray) === true))
            {
                $isOptimizerPayment = true;
            }
        }

        $errorCodeNonVerifiable = $this->isFinal($payment->getMethod(), $finalErrorCode, $isOptimizerPayment);

        if ($isOptimizerPayment === true)
        {
            $this->trace->info(TraceCode::PAYMENT_VERIFY_OPTIMIZER_CHECK,
                [
                    'payment_id' => $payment->getId(),
                    'error_code_non_verifiable' => $errorCodeNonVerifiable,
                    'terminal_id' => $payment->terminal->getId(),
                    'terminal_type_array' => $terminalTypeArray,
                ]);
        }

        if($errorCodeNonVerifiable === true)
        {
            $e->setAction(VerifyAction::FINISH);
        }

        $this->trace->info(TraceCode::PAYMENT_VERIFY_FAILED,
            [
                'payment_id' => $payment->getId(),
                'internal_error_code' => $finalErrorCode,
                'error_code_non_verifiable' => $errorCodeNonVerifiable,
                'exception_verify_action' => $e->getAction(),
            ]);

        return $errorCodeNonVerifiable;
    }

    /**
     * Determine from the output of the mapping if this internal error code can be
     * marked as final
     *
     * @param Payment\Method $method
     * @param string $internalErrorCode
     * @return bool
     */
    protected function isFinal(string $method, string $internalErrorCode, bool $isOptimizerPayment) : bool
    {
        if ($isOptimizerPayment === true)
        {
            return false;
        }

        $code = $this->processErrorVerifiableMapping($method, $internalErrorCode);

        $this->trace->info(TraceCode::PAYMENT_VERIFY_FAILED,
            [
                'internal_error_code' => $internalErrorCode,
                'response' => $code,
                'isFinal' => $code === 'F',
            ]);

        if($code === 'F')
        {
            return true;
        }

        return false;
    }


    /**
     * Read Error Code mapping with Verifiable flag from files
     * of the format 'error_code_<method>_verifiable.csv'
     * @param Payment\Gateway $gateway
     * @param string $internalErrorCode
     * @return string
     */
    protected function processErrorVerifiableMapping(string $method, string $internalErrorCode) : string
    {
        if (isset($method) === false || !$this->isValidErrorCode($internalErrorCode))
        {
            return 'T';
        }

        $errorCodeMap = array();

        $error = new Error($internalErrorCode);

        $error->readVerifiableErrorMappingFromFile($method, $errorCodeMap);

        try
        {
            if(array_key_exists($internalErrorCode, $errorCodeMap))
            {
                $code = $errorCodeMap[$internalErrorCode]['0'];

                return $code;
            }
        }
        catch (\Exception $exception)
        {
            $this->trace->info(TraceCode::ERROR_RESPONSE_MAPPING_READ_FAILED, $errorCodeMap[$internalErrorCode]);
        }

        return 'T';
    }

    public function isValidErrorCode($code)
    {
        if (isset($code) === false || $code === ''){
            return false;
        }
        return (defined(ErrorCode::class.'::'.$code));
    }

    /**
     * Update Payment attributes after running verify
     * @param Payment\Entity $payment payment for which attributes should be updated
     * @param string $verifyStatus status of verify
     * @param array $gatewayData contains error, request , response and other gateway data
     * @return void
     */
    protected function updatePaymentVerified(Payment\Entity $payment, $verifyStatus, $gatewayData = null)
    {
        // as verify flow does not have any lock in place. Reloading the payment here
        // and adding condition in `updateErrorInPaymentFromGatewayIfApplicable` to update error codes
        // only if payment is in failed state.
        $payment->reload();

        $payment->setVerified($verifyStatus);

        if(($payment->merchant->isFeatureEnabled(Feature\Constants::SILENT_REFUND_LATE_AUTH) === true)
            and $payment->isCreated() === true)
        {

            $exception = new Exception\BadRequestException($gatewayData['error']['code']);

            $this->updatePaymentFailed($exception,TraceCode::FAIL_CREATED_PAYMENT_UNDER_FEATURE_FLAG);
        }
        else
        {
            $this->updateErrorInPaymentFromGatewayIfApplicable($payment, $gatewayData);

            $this->repo->saveOrFail($payment);
        }
    }

    protected function updateErrorInPaymentFromGatewayIfApplicable(Payment\Entity $payment, $data)
    {
        if ((empty($data['error']) === true) or ($payment->isStatusCreatedOrFailed() === false))
        {
            return;
        }

        // If payment method is upi and not failed return early, in other words
        // update error code only in case of failed payments
        if ( ($payment->getMethod() === 'upi') and  ($payment->isFailed() === false))
        {
            return;
        }

        $error = $data['error'];

        $internalErrorCode = $error['internal_error_code'];

        $errorCode = $error['code'];

        $errorDescription = $error['description'];

        $payment->setError($errorCode, $errorDescription, $internalErrorCode);
    }

    /**
     * Gateway response and entity may contain sensitive data, thus we need to remove that from trace
     *
     * @param array $properties
     * @return array
     */
    protected function getTracableVerifyProperties(array $properties)
    {
        unset($properties['verify_response']['verifyResponseContent']);
        unset($properties['verify_response']['gatewayPayment']);

        return $properties;
    }
}
