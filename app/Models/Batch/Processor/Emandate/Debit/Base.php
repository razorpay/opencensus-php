<?php

namespace RZP\Models\Batch\Processor\Emandate\Debit;

use RZP\Constants\HyperTrace;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Payment\Gateway;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Base\RuntimeManager;
use Razorpay\Trace\Logger as Trace;
use RZP\Reconciliator\Base\Constants;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payment\Processor\Processor;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Models\Batch\Processor\Emandate\Base as BaseProcessor;
use RZP\Trace\Tracer;

class Base extends BaseProcessor
{
    const PAYMENT_ID            = 'payment_id';
    const ACCOUNT_NUMBER        = 'account_number';
    const GATEWAY_RESPONSE_CODE = 'gateway_response_code';
    const AMOUNT                = 'amount';
    const GATEWAY_PAYMENT_ID    = 'gateway_payment_id';
    const GATEWAY_ERROR_CODE    = 'gateway_error_code';
    const GATEWAY_ERROR_MESSAGE = 'gateway_error_message';

    protected function processEntry(array & $entry)
    {
        $content = $this->getDataFromRow($entry);

        try
        {
            Tracer::inSpan(['name' => HyperTrace::EMANDATE_DEBIT_UPDATE_PAYMENT_ENTITIES], function () use ($content){
                $this->updatePaymentEntities($content);
            });

            $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
        }
        catch (\Throwable $ex)
        {
            unset($content[self::ACCOUNT_NUMBER]);

            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::EMANDATE_DEBIT_RESPONSE_ERROR,
                [
                    'gateway' => $this->gateway,
                    'content' => $content,
                ]
            );

            throw $ex;
        }
    }

    protected function updatePaymentEntities(array $content)
    {
        //
        // Can't put in a transaction because of webhooks and emails
        //

        // Update gateway payment
        $this->updateGatewayPayment($content);

        $payment = $this->getPayment($content);

        $this->assertAmount($payment, $content);

        Tracer::inSpan(['name' => HyperTrace::EMANDATE_DEBIT_UPDATE_PAYMENT], function () use ($payment, $content){
            // Update payment
            $this->updatePayment($payment, $content);
        });
    }

    protected function getPayment(array $content)
    {
        $paymentId = $content[self::PAYMENT_ID];

        $accountNumber = $content[self::ACCOUNT_NUMBER];

        // Get payment
        $payment = $this->repo->payment->fetchDebitEmandatePaymentPendingAuth(
                                                                    $this->gateway,
                                                                    $paymentId,
                                                                    $accountNumber);

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
                'Amount tampering in Emandate found.',
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
        return number_format($content['amount'], 2, '.', '');
    }

    protected function updateGatewayPayment(array $content)
    {
        $paymentId = $content[self::PAYMENT_ID];

        $gatewayPayment = $this->getGatewayPayment($paymentId);

        $attrs = $this->getGatewayAttributes($content);

        $gatewayPayment->fill($attrs);

        $this->repo->saveOrFail($gatewayPayment);
    }

    protected function getGatewayAttributes(array $content)
    {
        return [];
    }

    protected function updatePayment(Payment\Entity $payment, array $content)
    {
        // Possible status from gateway:
        // 1.Success, 2.Failure, 3.Pending
        if ($this->isAuthorized($content) === true)
        {
            // handle already processed
            if ($payment->hasBeenAuthorized() === true)
            {
                $this->trace->info(TraceCode::PAYMENT_ALREADY_AUTHORIZED, ['payment_id' => $payment->getId()]);
            }
            else
            {
                Tracer::inSpan(['name' => HyperTrace::EMANDATE_DEBIT_PROCESS_AUTHORIZED_PAYMENT], function () use ($payment){
                    $this->processAuthorizedPayment($payment);
                });
            }
        }
        else if ($this->isRejected($content) === true)
        {
            // We do not check for already processed here, since we can update the error code of the payment
            $this->processFailedPayment($payment, $content);
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
            $content[self::GATEWAY_ERROR_MESSAGE] ?? null,
            [
                'payment_id' => $payment->getId(),
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

    protected function getGatewayPayment(string $paymentId)
    {
        return $this->repo
                    ->netbanking
                    ->findByPaymentIdAndActionOrFail($paymentId, GatewayAction::AUTHORIZE);
    }

    // Should be overridden in child class to handle pending status if applicable
    protected function isRejected(array $content): bool
    {
        return ($this->isAuthorized($content) === false);
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

                /*
                 Removing the account no from $entry because the entry returned from here will be logged
                 in the api response to batch service. This won't be an issue in output file because
                 in batch service the account no for each row is fetched from the database and not from api response.
                 Keeping this line here and not in controller keeping in mind different child classes can call this
                 as per different formats of account number field
                */
                $this->removeCriticalDataFromTracePayload($entry);
            }
            catch (Exception\BaseException $e)
            {
                // RZP Exceptions have public error code & description which can be exposed in the output file
                $this->trace->traceException($e, null, TraceCode::BATCH_PROCESSING_ERROR, $entryTracePayload);

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

                /*
                 Removing the account no from $entry because the entry returned from here will be logged
                 in the api response to batch service. This won't be an issue in output file because
                 in batch service the account no for each row is fetched from the database and not from api response.
                 Keeping this line here and not in controller keeping in mind different child classes can call this
                 as per different formats of account number field
                */
                $this->removeCriticalDataFromTracePayload($entry);
            }
            catch (\Throwable $e)
            {
                // All non RZP exception/errors case: 1) Log critical error & 2) expose just SERVER_ERROR code in output
                $this->trace->traceException($e, Trace::CRITICAL, TraceCode::BATCH_PROCESSING_ERROR, $entryTracePayload);

                /*
                 Remove payment entry from redis which was added to ignore duplicate payments with same status received
                 in partial and final files of banks. This will give chance to process the payment again if received in
                 another file as it failed to process in current instance.
                */
                $this->deletePaymentFromRedis($entries);

                $entry[Batch\Header::STATUS]     = Batch\Status::FAILURE;
                $entry[Batch\Header::ERROR_CODE] = ErrorCode::SERVER_ERROR;

                /*
                 Removing the account no from $entry because the entry returned from here will be logged
                 in the api response to batch service. This won't be an issue in output file because
                 in batch service the account no for each row is fetched from the database and not from api response.
                 Keeping this line here and not in controller keeping in mind different child classes can call this
                 as per different formats of account number field
                */
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

    public function getRedisKey(array $entries){
        foreach ($entries as &$entry)
        {
            $content = $this->getDataFromRow($entry);
            return $content[self::PAYMENT_ID] . '_' . $this->getBankStatus($content[self::GATEWAY_RESPONSE_CODE]);
        }
    }

    public function deletePaymentFromRedis(array $entries)
    {
        if($this->gateway !== Gateway::ENACH_NPCI_NETBANKING){
            return false;
        }

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
