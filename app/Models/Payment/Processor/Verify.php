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
    public function verify(Payment\Entity $payment, array $gatewayData = null)
    {
        $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_INITIATED, $payment);

        $this->setPayment($payment);

        $refunds = $this->repo->refund->findForPayment($payment);

        $data = [
            'payment' => $payment->toArrayGateway(),
            'refunds' => $refunds->toArrayGateway(),
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

        try
        {
            $data['gateway'] = $this->callGatewayFunction(Payment\Action::VERIFY, $data);

            $this->updatePaymentVerified($payment, VerifyStatus::SUCCESS, $data['gateway']);

            $data['payment'] = $payment->toArrayAdmin();

            $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_PROCESSED, $payment);
        }
        catch (Exception\PaymentVerificationException $e)
        {
            $action = $e->getAction();

            // If action is BLOCK, RETRY, FINISH we don't update Verify Status
            if ($action === null)
            {
                $this->updatePaymentVerified($payment, VerifyStatus::FAILED, $e->getData());

                $this->trace->info(
                    TraceCode::PAYMENT_VERIFY_FAILED,
                    $e->getData());
            }
            else
            {
                $this->updatePaymentVerified($payment, VerifyStatus::UNKNOWN);
            }

            $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_PROCESSED, $payment, $e);

            throw $e;
        }
        catch (\Exception $e)
        {
            $this->updatePaymentVerified($payment, VerifyStatus::ERROR);

            $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_PROCESSED, $payment, $e);

            throw $e;
        }
        catch (\Error $e)
        {
            $this->updatePaymentVerified($payment, VerifyStatus::ERROR);

            $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_PROCESSED, $payment, $e);

            throw $e;
        }

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
    public function verifyNewRoute(Payment\Entity $payment, array $gatewayData = null)
    {
        $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_INITIATED, $payment);

        $this->setPayment($payment);

        $data = $this->populateVerifyData($payment, $gatewayData);

        $finalException = $this->callVerification($payment, $data);

        if ($finalException !== null)
        {
            $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_PROCESSED, $payment, $finalException);

            $this->trace->info(
                TraceCode::PAYMENT_VERIFY_FAILED,
                $finalException->getData());

            throw $finalException;
        }
        else
        {
            $this->app['diag']->trackVerifyPaymentEvent(EventCode::PAYMENT_VERIFICATION_PROCESSED, $payment);
        }

        return $data;
    }

    /**
     * Populate verification data and return array
     *
     * @param Payment\Entity $payment
     * @param array $gatewayData
     * @return array
     */
    protected function populateVerifyData(Payment\Entity $payment, $gatewayData) : array
    {
        $refunds = $this->repo->refund->findForPayment($payment);

        $data = [
            'payment' => $payment->toArrayGateway(),
            'refunds' => $refunds->toArrayGateway(),
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
    protected function callVerification(Payment\Entity $payment, array $data)
    {
        $finalException = null;
        try
        {
            $data['gateway'] = $this->callGatewayFunction(Payment\Action::VERIFY, $data);

            $this->updatePaymentVerified($payment, VerifyStatus::SUCCESS, $data['gateway']);

            $data['payment'] = $payment->toArrayAdmin();
        }
        catch (Exception\PaymentVerificationException $e)
        {
            $this->handlePaymentVerificationException($payment, $e);

            $finalException = $e;
        }
        catch (\Exception $e)
        {
            $this->updatePaymentVerified($payment, VerifyStatus::ERROR);

            $finalException = $e;
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
                                                          Exception\PaymentVerificationException &$e)
    {
        $this->finishVerifyIfApplicable($payment, $e);

        $action = $e->getAction();

        $this->trace->info(TraceCode::PAYMENT_VERIFY_FAILED,
            [
                'verify action' => $action,
            ]);

        // If action is BLOCK, RETRY we don't update Verify Status
        if ($action === null)
        {
            $this->updatePaymentVerified($payment, VerifyStatus::FAILED, $e->getData());
        }
        else if ($action === VerifyAction::FINISH)
        {
            $this->verifyFinishAction($payment, $e);
        }
        else
        {
            $this->updatePaymentVerified($payment, VerifyStatus::UNKNOWN);
        }
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
                                                Exception\PaymentVerificationException &$e)
    {
        $internalErrorCode = $payment->getInternalErrorCode();

        $finalErrorCode = '';

        if($internalErrorCode === null)
        {
            $finalErrorCode = $e->getCode();
        }
        else
        {
            $finalErrorCode = $internalErrorCode;
        }

        $errorCodeNonVerifiable = $this->isFinal($payment->getMethod(), $finalErrorCode);

        if($errorCodeNonVerifiable === true)
        {
            $e->setAction(VerifyAction::FINISH);
        }


        $this->trace->info(TraceCode::PAYMENT_VERIFY_FAILED,
            [
                'payment_id' => $payment->getId(),
                'internal_error_code' => $finalErrorCode,
                'error code non verifiable' => $errorCodeNonVerifiable,
                'exception verify action' => $e->getAction(),
            ]);
    }

    /**
     * Determine from the output of the mapping if this internal error code can be
     * marked as final
     *
     * @param Payment\Method $method
     * @param string $internalErrorCode
     * @return bool
     */
    protected function isFinal(string $method, string $internalErrorCode) : bool
    {
        $code = $this->processErrorVerifiableMapping($method, $internalErrorCode);

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
        $payment->setVerified($verifyStatus);

        $this->updateErrorInPaymentFromGatewayIfApplicable($payment, $gatewayData);

        $this->repo->saveOrFail($payment);
    }

    protected function updateErrorInPaymentFromGatewayIfApplicable($payment, $data)
    {
        if (empty($data['error']) === true)
        {
            return;
        }

        $error = $data['error'];

        $internalErrorCode = $error['internal_error_code'];

        $errorCode = $error['code'];

        $errorDescription = $error['description'];

        $payment->setError($errorCode, $errorDescription, $internalErrorCode);
    }
}
