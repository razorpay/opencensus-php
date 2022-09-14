<?php

namespace RZP\Jobs;

use Monolog\Logger;
use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;

class NachBatchProcessWithAsyncBalance extends Job
{
    const QUEUE_NAME_KEY  = 'nach_batch_process_async_balance';

    protected $params;

    protected $id;

    protected $queueConfigKey = self::QUEUE_NAME_KEY;

    public $timeout = 5400;

    public function __construct($mode, $batchId, $data)
    {
        $this->params = $data;

        $this->id = $batchId;

        parent::__construct($mode);
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::NACH_ASYNC_BAL_BATCH_JOB_RECEIVED,
            [
                'sub_type' => $this->params[Batch\Entity::SUB_TYPE],
                'gateway'  => $this->params[Batch\Entity::GATEWAY],
            ]);

        try
        {
            $namespaceKeys = [
                Batch\Entity::TYPE,
                Batch\Entity::SUB_TYPE,
                Batch\Entity::GATEWAY,
            ];

            $processor = "RZP\\Models\\Batch\\Processor";

            foreach ($namespaceKeys as $key)
            {
                $methodValue = $this->params[$key];

                if (empty($methodValue) === false)
                {
                    $processor .= '\\' . studly_case($methodValue);
                }
            }

            if (class_exists($processor) === false)
            {
                throw new LogicException(
                    'Bad request, Batch Processor class does not exist for the type:' . $this->params[Batch\Entity::TYPE] ,
                    ErrorCode::SERVER_ERROR_GATEWAY_BATCH_PROCESSOR_CLASS_ABSENCE,
                    [
                        'sub_type' => $this->params[Batch\Entity::SUB_TYPE],
                        'gateway'  => $this->params[Batch\Entity::GATEWAY],
                    ]);
            }

            $processor = new $processor;

            unset($this->params[Batch\Entity::TYPE]);
            unset($this->params[Batch\Entity::SUB_TYPE]);
            unset($this->params[Batch\Entity::GATEWAY]);

            if (isset($this->params["response_file_name"]) === true)
            {
                unset($this->params["response_file_name"]);
            }


            $processor->batchProcessEntries($this->params);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Logger::CRITICAL,
                TraceCode::BATCH_FILE_PROCESSING_ERROR);
        }
    }
}
