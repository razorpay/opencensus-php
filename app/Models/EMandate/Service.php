<?php

namespace RZP\Models\EMandate;

use App;

use Carbon\Carbon;
use Monolog\Logger;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Jobs\NachBatchProcess;
use RZP\Jobs\NachBatchProcessWithAsyncBalance;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Jobs\ResposeFileBatchInstrumentation;

class Service extends Base\Service
{
    public function reconcileDebitFile(string $gateway, array $input)
    {
        $this->trace->info(
            TraceCode::EMANDATE_DEBIT_RECON_REQUEST,
            ['gateway'   => $gateway]);

        (new Validator)->validateDebitGateway($gateway);

        $response = $this->app['gateway']->call(
                            $gateway,
                            Payment\Action::RECONCILE_DEBIT_EMANDATE,
                            $input,
                            $this->mode);

        return $response;
    }

    public function processBatchRequest(array $input, string $batchId)
    {
        $duplicatePayment = $this->checkEmandateDuplicatePayment($input);
        if($duplicatePayment === true){
            $this->setResponseFields($input);
            return $input;
        }

        // This is for the instrumentation of nach/emandate debit response file
        if (($this->isExperimentEnabledForInstrumentationBank($input['gateway']) === true) and
            ($this->isExperimentEnabledForInstrumentationRamp() === true))
        {
            try
            {
                ResposeFileBatchInstrumentation::dispatch($this->mode, $batchId, $input);

            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException($ex, Logger::ERROR, TraceCode::EMANDATE_INSTRUMENTATION_ERROR_SQS_PUSH_FAILED);
            }
        }

        $namespaceKeys = [
            Batch\Entity::TYPE,
            Batch\Entity::SUB_TYPE,
            Batch\Entity::GATEWAY,
        ];

        $processor = "RZP\\Models\\Batch\\Processor";

        foreach ($namespaceKeys as $key)
        {
            $methodValue = $input[$key];

            if (empty($methodValue) === false)
            {
                $processor .= '\\' . studly_case($methodValue);
            }
        }

        if (class_exists($processor) === false)
        {
            throw new LogicException(
                'Bad request, Batch Processor class does not exist for the type:' . $input[Batch\Entity::TYPE] ,
                ErrorCode::SERVER_ERROR_GATEWAY_BATCH_PROCESSOR_CLASS_ABSENCE,
                [
                    'sub_type' => $input[Batch\Entity::SUB_TYPE],
                    'gateway'  => $input[Batch\Entity::GATEWAY],
                ]);
        }

        $processor = new $processor;

        unset($input[Batch\Entity::TYPE]);
        unset($input[Batch\Entity::SUB_TYPE]);
        unset($input[Batch\Entity::GATEWAY]);

        if (isset($input["response_file_name"]) === true)
        {
            unset($input["response_file_name"]);
        }

        return $processor->batchProcessEntries($input);
    }

    public function processNachBatchRequest(array $input, string $batchId): array
    {
        [$duplicatePayment, $asyncBalEnabled] = $this->isNachProcessingWithAsyncBalanceOrDuplicatePayment($input);

        if($duplicatePayment === true){
            return array_merge($input, [
                'Status'            => 'Success',
                'Error Code'        => null,
                'Error Description' => null,
            ]);
        }

        if($asyncBalEnabled === true){
            return $this->processNachBatchRequestAsync($input, $batchId, true);
        }

        $razorxKey = $this->getRazorxKey($input);
        $enabled = $this->isAsyncNachProcessingEnabled($razorxKey, $this->mode);

        if ($enabled === true)
        {
            return $this->processNachBatchRequestAsync($input, $batchId, false);
        }

        return $this->processBatchRequest($input, $batchId);
    }

    public function processNachBatchRequestAsync(array $input, string $batchId, bool $asyncBalanceQueue = false): array
    {
        $data = array_merge($input, [
            'Status'            => 'Success',
            'Error Code'        => null,
            'Error Description' => null,
        ]);

        try
        {
            if($asyncBalanceQueue === true){
                NachBatchProcessWithAsyncBalance::dispatch($this->mode, $batchId, $input);
            }
            else
            {
                NachBatchProcess::dispatch($this->mode, $batchId, $input);
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, Logger::ERROR, TraceCode::NACH_BATCH_ERROR_SQS_PUSH_FAILED);
            $data['Status'] = 'Failure';
            $data['Error Code'] = ErrorCode::SERVER_ERROR;;
            $data['Error Description'] = "queue push failed";

            /*
             Remove payment entry from redis which was added to ignore duplicate payments with same status received
             in partial and final files of banks. This will give chance to process the payment again if received in
             another file as it failed to process in current instance.
             */
            $this->deletePaymentFromRedis($input);
        }

        if (($this->isExperimentEnabledForInstrumentationBank($input['gateway']) === true) and
            ($this->isExperimentEnabledForInstrumentationRamp() === true))
        {
            try
            {
                ResposeFileBatchInstrumentation::dispatch($this->mode, $batchId, $input);

            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException($ex, Logger::ERROR, TraceCode::EMANDATE_INSTRUMENTATION_ERROR_SQS_PUSH_FAILED);
            }
        }

        return $data;
    }

    private static function getRazorxKey(array $input)
    {
        $razorxKey = "async";

        $namespaceKeys = [
            Batch\Entity::TYPE,
            Batch\Entity::SUB_TYPE,
            Batch\Entity::GATEWAY,
        ];

        foreach ($namespaceKeys as $key)
        {
            $methodValue = $input[$key];

            if (empty($methodValue) === false)
            {
                $razorxKey.= '_' . studly_case($methodValue);
            }
        }
        return $key;
    }

    private static function isAsyncNachProcessingEnabled(string $key, $mode): bool
    {
        $app = App::getFacadeRoot();

        $mode = $mode ?? Mode::LIVE;

        $status = $app['razorx']->getTreatment($key, RazorxTreatment::EMANDATE_ASYNC_PAYMENT_PROCESSING_ENABLED, $mode);

        return (strtolower($status) === 'on');
    }

    public function checkEmandateDuplicatePayment(array $input): bool
    {
        try{
            if(strtolower($input[Batch\Entity::GATEWAY]) !== strtolower('EnachNpciNetbanking')){
                return false;
            }

            $processor = $this->getProcessor($input);

            return $this->checkDuplicatePayment($processor, $input);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::NACH_PROCESSING_WITH_ASYNC_BALANCE_QUEUE_FAIL
            );
        }
        return false;
    }

    public function isNachProcessingWithAsyncBalanceOrDuplicatePayment(array $input): array
    {
        try{
            if(($input[Batch\Entity::GATEWAY] !== 'nach_citi') and
                ($input[Batch\Entity::GATEWAY] !== 'nach_icici')){
                $this->trace->info(TraceCode::NACH_PROCESSING_INVALID_GATEWAY);
                return [false,false];
            }

            $processor = $this->getProcessor($input);

            $payment = $processor->getPaymentDetails($input);

            $duplicatePayment = $this->checkDuplicatePayment($processor, $input);

            $asyncBalEnabled = $this->isAsyncBalQueueRazorxEnabled($payment);

            return [$duplicatePayment, $asyncBalEnabled];
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::NACH_PROCESSING_WITH_ASYNC_BALANCE_QUEUE_FAIL
            );
        }
        return [false, false];
    }

    private function checkDuplicatePayment($processor, $input): bool
    {
        try{
            $handleDuplicatePayments = $this->isDuplicatePaymentsHandlingEnabled();
            if($handleDuplicatePayments === false)
                return false;
            $ttl = 20 * 20 * 60; // 20 hours in seconds
            $redisKey = $processor->getRedisKey($input);
            $result = $this->app['redis']->set($redisKey, true, 'ex', $ttl, 'nx');
            if($result === null){
                $this->trace->info(
                    TraceCode::NACH_PROCESSING_REDIS_DUPLICATE_PAYMENT,
                    [
                        'redisKey' => $redisKey,
                    ]);
                return true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::NACH_PROCESSING_REDIS_FAILURE
            );
        }
        return false;
    }

    private function deletePaymentFromRedis($input){
        try
        {
            $processor = $this->getProcessor($input);
            $redisKey = $processor->getRedisKey($input);
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

    private function isAsyncBalQueueRazorxEnabled(Payment\Entity $payment): bool
    {
        $app = App::getFacadeRoot();

        $mode = $mode ?? Mode::LIVE;

        $status = $app['razorx']->getTreatment($payment->getMerchantId(),
            RazorxTreatment::EMANDATE_ASYNC_PAYMENT_WITH_ASYNC_BAL_ENABLED, $mode);

        return (strtolower($status) === 'on');
    }

    private function isDuplicatePaymentsHandlingEnabled(): bool
    {
        $key = Carbon::now()->getTimestamp();

        $razorxTreatment = RazorxTreatment::EMANDATE_HANDLE_DUPLICATE_PAYMENTS;

        $variant = $this->app->razorx->getTreatment($key,
            $razorxTreatment,
            $this->mode
        );

        return (strtolower($variant) === 'on');
    }

    private function setResponseFields(array & $input)
    {
        try
        {
            /* As we are coming here only after ignoring payment processing due to duplicate payment case,
               setting status as success and error fields null for batch service
            */
            $input['data'][Batch\Header::STATUS] = Batch\Status::SUCCESS;
            $input['data'][Batch\Header::ERROR_CODE]        = null;
            $input['data'][Batch\Header::ERROR_DESCRIPTION] = null;
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

    private function getProcessor(array $input){
        $namespaceKeys = [
            Batch\Entity::TYPE,
            Batch\Entity::SUB_TYPE,
            Batch\Entity::GATEWAY,
        ];

        $processor = "RZP\\Models\\Batch\\Processor";

        foreach ($namespaceKeys as $key)
        {
            $methodValue = $input[$key];

            if (empty($methodValue) === false)
            {
                $processor .= '\\' . studly_case($methodValue);
            }
        }

        if (class_exists($processor) === false)
        {
            throw new LogicException(
                'Bad request, Batch Processor class does not exist for the type:' . $input[Batch\Entity::TYPE] ,
                ErrorCode::SERVER_ERROR_GATEWAY_BATCH_PROCESSOR_CLASS_ABSENCE,
                [
                    'sub_type' => $input[Batch\Entity::SUB_TYPE],
                    'gateway'  => $input[Batch\Entity::GATEWAY],
                ]);
        }

        $processor = new $processor;
        return $processor;
    }

    public function isExperimentEnabledForInstrumentationBank($key): bool
    {
        try
        {
            $variant = $this->app['razorx']->getTreatment(
                $key,
                RazorxTreatment::EMANDATE_DEBIT_RESPONSE_FILE_INSTRUMENTATION_BANK,
                $this->mode
            );

            if (strtolower($variant) === 'on')
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::EMANDATE_INSTRUMENTATION_RESPONSE_RAZORX_FAIL
            );
        }

        return false;
    }

    public function isExperimentEnabledForInstrumentationRamp(): bool
    {
        try
        {
            $key = Carbon::now()->getTimestamp();

            $variant = $this->app['razorx']->getTreatment(
                $key,
                RazorxTreatment::EMANDATE_DEBIT_RESPONSE_FILE_INSTRUMENTATION_RAMP,
                $this->mode
            );

            if (strtolower($variant) === 'on')
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::EMANDATE_INSTRUMENTATION_RESPONSE_RAZORX_FAIL
            );
        }

        return false;
    }
}
