<?php

namespace RZP\Models\Batch\Processor\Nach\Debit;

use RZP\Exception;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Base\RuntimeManager;
use RZP\Exception\BaseException;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\PublicErrorDescription;
use RZP\Reconciliator\Base\Constants;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payment\Processor\Processor;
use RZP\Models\Batch\Processor\Emandate\Base as BaseProcessor;

class Base extends BaseProcessor
{
    const PAYMENT_ID            = 'payment_id';
    const ACCOUNT_NUMBER        = 'account_number';
    const GATEWAY_RESPONSE_CODE = 'gateway_response_code';
    const AMOUNT                = 'amount';
    const GATEWAY_PAYMENT_ID    = 'gateway_payment_id';
    const GATEWAY_ERROR_CODE    = 'gateway_error_code';
    const GATEWAY_ERROR_MESSAGE = 'gateway_error_message';
    const INTERNAL_ERROR_CODE   = 'internal_error_code';

    protected function processEntry(array &$entry)
    {
        $content = $this->getDataFromRow($entry);

        try
        {
            $this->updatePaymentEntities($content);

            $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::NACH_DEBIT_RESPONSE_ERROR,
                [
                    'gateway' => $this->gateway,
                    'content' => $content,
                    'mode'    => $this->mode,
                ]
            );

            throw $ex;
        }
    }

    protected function updatePaymentEntities(array $content)
    {
        $payment = $this->getPayment($content);

        $this->updateGatewayPaymentEntity($content, $payment);

        $this->assertAmount($payment, $content);

        // Update payment
        $this->updatePayment($payment, $content);
    }

    protected function getPayment(array $content)
    {
        $paymentId = $content[self::PAYMENT_ID];

        $accountNumber = $content[self::ACCOUNT_NUMBER];

        // Get payment
        $payment = $this->repo
                        ->payment
                        ->fetchDebitNachPaymentPendingAuth(
                            $this->gateway,
                            $paymentId,
                            $accountNumber
                        );

        return $payment;
    }

    /**
     * @param Payment\Entity $payment
     * @param $content
     * @throws Exception\LogicException
     */
    protected function assertAmount(Payment\Entity $payment, $content)
    {
        $expectedAmount = number_format($payment->getAmount() / 100, 2, '.', '');

        $actualAmount = $this->getFormattedGatewayAmount($content);

        if ($expectedAmount !== $actualAmount)
        {
            throw new Exception\LogicException(
                'Amount tampering in Nach found.',
                ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED,
                [
                    'expected'   => $expectedAmount,
                    'actual'     => $actualAmount,
                    'payment_id' => $payment->getId(),
                ]);
        }

    }

    protected function getFormattedGatewayAmount($content)
    {
        return number_format($content[self::AMOUNT] / 100, 2, '.', '');
    }

    protected function updatePayment(Payment\Entity $payment, array $content)
    {
        if ($this->isAuthorized($content) === true)
        {
            // handle already processed
            if ($payment->hasBeenAuthorized() === true)
            {
                $this->trace->info(TraceCode::PAYMENT_ALREADY_AUTHORIZED, ['payment_id' => $payment->getId()]);
            }
            else
            {
                $this->processAuthorizedPayment($payment);
            }
        }
        else if ($this->isRejected($content) === true)
        {
            // handle already processed
            // for e-mandate, error code update of failed payments is supported due to low timeout window
            if (($payment->isFailed() === true) and ($payment->isNach() === true))
            {
                $this->trace->info(TraceCode::PAYMENT_STATUS_FAILED, ['payment_id' => $payment->getId()]);
            }
            else
            {
                $this->processFailedPayment($payment, $content);
            }
        }
    }

    protected function processAuthorizedPayment(Payment\Entity $payment)
    {
        $merchant = $payment->merchant;

        $processor = new Processor($merchant);

        $processor = $processor->setPayment($payment);

        $data = $processor->processAuth($payment);

        $this->reconcileEntity($payment);

        return $data;
    }

    protected function processFailedPayment(Payment\Entity $payment, array $content)
    {
        $merchant = $payment->merchant;

        $processor = new Processor($merchant);

        $errorCode = $this->getApiErrorCode($content);

        if($payment->isFailed() !== true)
        {
            try
            {
                $variant = $this->app->razorx->getTreatment(
                    $payment->getMerchantId(),
                    RazorxTreatment::EMANDATE_NET_REVENUE_IMPROVEMENT,
                    $this->mode);

            } catch (\Throwable $ex)
            {
                $variant = "off";
            }

            if($variant === "on")
            {
                $nrErrorCode = $this->getNRErrorCode($content);

                $processor->updatePaymentTokenDetails($payment, $nrErrorCode);
            }
        }

        $e = new Exception\GatewayErrorException(
            $errorCode,
            $content[self::GATEWAY_ERROR_CODE] ?? null,
            $this->getGatewayErrorDesc($content) ?? null,
            [
                'payment_id' => $payment->getId(),
                'gateway'    => $this->gateway,
            ]);

        $processor = $processor->setPayment($payment);

        $processor->updatePaymentAuthFailed($e);
    }

    protected function getApiErrorCode(array $content): string
    {
        return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }

    protected function getNRErrorCode(array $content)
    {
        return [];
    }

    protected function getGatewayErrorDesc(array $content): string
    {
        return PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED;
    }

    public function getOutputFileHeadings(): array
    {
        $headerRule = $this->batch->getValidator()->getHeaderRule();

        return Batch\Header::getHeadersForFileTypeAndBatchType($this->outputFileType, $headerRule);
    }

    protected function sendProcessedMail()
    {
        return;
    }

    protected function updateGatewayPaymentEntity($content, $payment)
    {
        return;
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('2048M');
    }

    public function addSettingsIfRequired(&$input)
    {
        $gateway = $this->batch->getGateway();
        $subType = $this->batch->getSubType();

        $input[Batch\Entity::CONFIG][Constants::GATEWAY]  = $gateway;
        $input[Batch\Entity::CONFIG][Constants::SUB_TYPE] = $subType;

        $this->trace->info(TraceCode::EMANDATE_BATCH_SERVICE_INPUT_CONFIG, $input);
    }

    public function batchProcessEntries(array $entries)
    {
        foreach ($entries as &$entry)
        {
            $entryTracePayload = $entry;

            $this->removeCriticalDataFromTracePayload($entryTracePayload);

            try
            {

                $this->trace->debug(TraceCode::BATCH_PROCESSING_ENTRY, $entryTracePayload);

                $this->processEntry($entry);

                if ($this->resetErrorOnSuccess() === true)
                {
                    // Set errors as null

                    $entry[Batch\Header::ERROR_CODE]        = null;
                    $entry[Batch\Header::ERROR_DESCRIPTION] = null;
                }

                $this->removeCriticalDataFromTracePayload($entry);

            }
            catch (BaseException $e)
            {
                // RZP Exceptions have public error code & description which can be exposed in the output file
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::NACH_DEBIT_RESPONSE_ERROR,
                    [
                        'gateway' => $this->gateway,
                        'content' => $entryTracePayload,
                    ]
                );
                /*
                 Remove payment entry from redis which was added to ignore duplicate payments with same status received
                 in partial and final files of banks. This will give chance to process the payment again if received in
                 another file as it failed to process in current instance.
                */
                $this->deletePaymentFromRedis($entries);

                $error = $e->getError();

                $entry[Batch\Header::STATUS]            = Batch\Status::FAILURE;
                $entry[Batch\Header::ERROR_CODE]        = $error->getPublicErrorCode();
                $entry[Batch\Header::ERROR_DESCRIPTION] = $error->getDescription();

                $this->removeCriticalDataFromTracePayload($entry);
            }
            catch (\Throwable $e)
            {
                // All non RZP exception/errors case: 1) Log critical error & 2) expose just SERVER_ERROR code in output
                $this->trace->traceException(
                    $e,
                    Trace::CRITICAL,
                    TraceCode::NACH_DEBIT_RESPONSE_ERROR,
                    [
                        'gateway' => $this->gateway,
                        'content' => $entryTracePayload,
                    ]
                );
                /*
                 Remove payment entry from redis which was added to ignore duplicate payments with same status received
                 in partial and final files of banks. This will give chance to process the payment again if received in
                 another file as it failed to process in current instance.
                */
                $this->deletePaymentFromRedis($entries);

                $entry[Batch\Header::STATUS]     = Batch\Status::FAILURE;
                $entry[Batch\Header::ERROR_CODE] = ErrorCode::SERVER_ERROR;

                $this->removeCriticalDataFromTracePayload($entry);
            }
        }

        return $entries;
    }

    public function batchInstrumentation(array $entry, array & $instrumentationData)
    {
        $content = $this->getDataFromRow($entry);

        try
        {
            $payment = $this->getPayment($content);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::EMANDATE_INSTRUMENTATION_PAYMENT_FETCH_FAIL,
                [
                    'gateway' => $this->gateway,
                    'content' => $content,
                    'mode'    => $this->mode,
                ]
            );

            $payment = null;
        }

        $instrumentationData["payment_id"]              = $content[self::PAYMENT_ID];
        $instrumentationData["method"]                  = empty($payment) ? null : $payment->getMethod();
        $instrumentationData["amount"]                  = $content[self::AMOUNT];
        $instrumentationData["payment_status"]          = $this->getBankStatus($content[self::GATEWAY_RESPONSE_CODE]);
        $instrumentationData["gateway"]                 = $this->gateway;
        $instrumentationData["response_code"]           = $content[self::GATEWAY_RESPONSE_CODE];
        $instrumentationData["error_code"]              = $content[self::GATEWAY_ERROR_CODE] ?? null;
        $instrumentationData["response_description"]    = $content[self::GATEWAY_ERROR_MESSAGE] ?? null;
        $instrumentationData["api_error_code"]          = null;

        if ($instrumentationData["payment_status"] === 'failed')
        {
            $instrumentationData["response_description"]    = isset($content[self::GATEWAY_ERROR_MESSAGE]) ? $instrumentationData["response_description"] : $this->getGatewayErrorDesc($content);
            $instrumentationData["api_error_code"]          = $this->getApiErrorCode($content);
        }
    }

    public function getPaymentDetails(array $entries){
        foreach ($entries as &$entry)
        {
            $content = $this->getDataFromRow($entry);
            return $this->getPayment($content);
        }
    }

    public function getRedisKey(array $entries){
        foreach ($entries as &$entry)
        {
            $content = $this->getDataFromRow($entry);
            return $content[self::PAYMENT_ID] . '_' . $this->getBankStatus($content[self::GATEWAY_RESPONSE_CODE]);
        }
    }

    public function deletePaymentFromRedis(array $entries){
        try
        {
            $redisKey = $this->getRedisKey($entries);
            $delResult = $this->app['redis']->del($redisKey);
            $this->trace->info(
                TraceCode::NACH_PROCESSING_REDIS_DELETE_KEY,
                [
                    'redisKey' => $redisKey,
                    'delValue' => $delResult,
                ]);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::NACH_PROCESSING_REDIS_FAILURE
            );
        }
    }
}
