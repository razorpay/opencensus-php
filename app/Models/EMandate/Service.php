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
        $asyncBalEnabled = $this->isNachProcessingWithAsyncBalance($input, $this->mode);

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

    public function isNachProcessingWithAsyncBalance(array $input, string $mode): bool
    {
        try{
            if(($input[Batch\Entity::GATEWAY] !== 'nach_citi') and
                ($input[Batch\Entity::GATEWAY] !== 'nach_icici')){
                $this->trace->info(TraceCode::NACH_PROCESSING_INVALID_GATEWAY);
                return false;
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

            $payment = $processor->getPaymentDetails($input);

            return $this->isAsyncBalQueueRazorxEnabled($payment);
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

    private function isAsyncBalQueueRazorxEnabled(Payment\Entity $payment): bool
    {
        $app = App::getFacadeRoot();

        $mode = $mode ?? Mode::LIVE;

        $status = $app['razorx']->getTreatment($payment->getMerchantId(),
            RazorxTreatment::EMANDATE_ASYNC_PAYMENT_WITH_ASYNC_BAL_ENABLED, $mode);

        return (strtolower($status) === 'on');
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
