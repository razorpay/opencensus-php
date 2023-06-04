<?php

namespace RZP\Models\EMandate;

use App;

use RZP\Exception;
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
use RZP\Services\Dcs\Configurations\Service as DcsConfigService;

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
        try
        {
            ResposeFileBatchInstrumentation::dispatch($this->mode, $batchId, $input);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, Logger::ERROR, TraceCode::EMANDATE_INSTRUMENTATION_ERROR_SQS_PUSH_FAILED);
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
            
            /*
             Remove payment entry from redis which was added to ignore duplicate payments with same status received
             in partial and final files of banks. This will give chance to process the payment again if received in
             another file as it failed to process in current instance.
             */
            $this->deletePaymentFromRedis($input);

            $data['Status'] = 'Failure';
            $data['Error Code'] = ErrorCode::SERVER_ERROR;;
            $data['Error Description'] = "queue push failed";
        }

        try
        {
            ResposeFileBatchInstrumentation::dispatch($this->mode, $batchId, $input);

        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, Logger::ERROR, TraceCode::EMANDATE_INSTRUMENTATION_ERROR_SQS_PUSH_FAILED);
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
            $ttl = 20 * 60 * 60; // 20 hours in seconds
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

    public function getBulkEmandateConfigs(array $input)
    {
        $this->trace->info(TraceCode::EMANDATE_CONFIG_FETCH_REQUEST,
            [
                "input_data" => $input
            ]);

        $merchantIds = explode(",", $input["merchant_ids"]);

        (new Validator)->validateGetEmandateConfigs($merchantIds);

        $dcsConfigService = new DcsConfigService();

        $key = Constants::EMANDATE_MERCHANT_CONFIGURATIONS;

        $fields = Constants::EMANDATE_CONFIG_FIELDS;

        try
        {
            return $dcsConfigService->fetchConfigurationBulk($key, $merchantIds, $fields, $this->mode);
        }
        catch (\Exception $ex)
        {
            // Need to throw error incase of any request failure

            $this->trace->traceException($ex, null, TraceCode::EMANDATE_CONFIG_FETCH_ERROR, $merchantIds);

            throw new Exception\BadRequestValidationFailureException(
                "error while fetching data from dcs");
        }
    }

    public function postBulkEmandateConfigs(array $input)
    {
        $this->trace->info(
            TraceCode::EMANDATE_CONFIG_CREATE_REQUEST,
            [
                "input_data" => $input
            ]);

        (new Validator)->validateCreateEmandateConfigs($input);

        $merchantIds = $input[Constants::MERCHANT_IDS];

        $merchantConfigs = [];

        foreach (Constants::EMANDATE_CONFIG_FIELDS as $config)
        {
            if(isset($input[$config]) === true and $input[$config] !== null)
            {
                $merchantConfigs[$config] = $input[$config];
            }
        }

        if(count($merchantConfigs) < 0)
        {
            return [];
        }

        $dcsConfigService = new DcsConfigService();

        $key = Constants::EMANDATE_MERCHANT_CONFIGURATIONS;

        $response = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $dcsConfigService->createConfiguration($key, $merchantId, $merchantConfigs, $this->mode);

                $response["success_merchant_ids"][] = $merchantId;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex, null, TraceCode::EMANDATE_CONFIG_CREATE_ERROR, [$merchantId]);

                $response["failed_merchant_ids"][] = $merchantId;
            }
        }

        return $response;
    }

    public function editBulkEmandateConfigs(array $input)
    {
        $this->trace->info(TraceCode::EMANDATE_CONFIG_EDIT_REQUEST,
            [
                "input_data" => $input
            ]);

        (new Validator)->validateEditEmandateConfigs($input);

        $merchantIds = $input["merchant_ids"];

        $merchantConfigs = [];

        foreach (Constants::EMANDATE_CONFIG_FIELDS as $config)
        {
            if(isset($input[$config]) === true and $input[$config] !== null)
            {
                $merchantConfigs[$config] = $input[$config];
            }
        }

        if(count($merchantConfigs) < 0)
        {
            return [];
        }

        $dcsConfigService = new DcsConfigService();

        $key = Constants::EMANDATE_MERCHANT_CONFIGURATIONS;

        $response = [];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                $dcsConfigService->editConfiguration($key, $merchantId, $merchantConfigs, $this->mode);

                $response["success_merchant_ids"][] = $merchantId;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException($ex, null, TraceCode::EMANDATE_CONFIG_CREATE_ERROR, [$merchantId]);

                $response["failed_merchant_ids"][] = $merchantId;
            }
        }

        return $response;
    }
}
