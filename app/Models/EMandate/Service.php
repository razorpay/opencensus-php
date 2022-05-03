<?php

namespace RZP\Models\EMandate;

use Monolog\Logger;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Jobs\NachBatchProcess;
use RZP\Exception\LogicException;

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

    public function processBatchRequestAsync(array $input, string $batchId)
    {
        try
        {
            NachBatchProcess::dispatch($this->mode, $batchId, $input);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, Logger::ERROR, TraceCode::NACH_BATCH_ERROR_SQS_PUSH_FAILED);
        }
    }
}
