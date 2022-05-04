<?php

namespace RZP\Models\EMandate;

use App;

use Monolog\Logger;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Jobs\NachBatchProcess;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\RazorxTreatment;

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

    public function processBatchRequest(array $input)
    {
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

        return $processor->batchProcessEntries($input);
    }

    public function processNachBatchRequest(array $input, string $batchId): array
    {
        $razorxKey = $this->getRazorxKey($input);
        $enabled = $this->isAsyncNachProcessingEnabled($razorxKey, $this->mode);

        if ($enabled === true)
        {
            return $this->processNachBatchRequestAsync($input, $batchId);
        }

        return $this->processBatchRequest($input);;
    }

    public function processNachBatchRequestAsync(array $input, string $batchId): array
    {
        $data = array_merge($input, [
            'Status'            => 'Success',
            'Error Code'        => null,
            'Error Description' => null,
        ]);

        try
        {
            NachBatchProcess::dispatch($this->mode, $batchId, $input);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, Logger::ERROR, TraceCode::NACH_BATCH_ERROR_SQS_PUSH_FAILED);
            $data['Status'] = 'Failure';
            $data['Error Code'] = ErrorCode::SERVER_ERROR;;
            $data['Error Description'] = "queue push failed";
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
}
