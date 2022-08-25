<?php

namespace RZP\Jobs;

use Carbon\Carbon;
use Monolog\Logger;
use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Services\KafkaProducer;
use RZP\Exception\LogicException;

class ResposeFileBatchInstrumentation extends Job
{
    const QUEUE_NAME_KEY  = 'emandate_files_instrumentation';

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
            TraceCode::EMANDATE_RESPONSE_INSTRUMENTATION_JOB_STARTED,
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

            $instrumentationData = [];

            $instrumentationData["batch_id"] = $this->id;

            if (isset($this->params["response_file_name"]) === true)
            {
                $instrumentationData["file_name"] = $this->params["response_file_name"];
                unset($this->params["response_file_name"]);
            }
            else
            {
                $instrumentationData["file_name"] = null;
            }

            unset($this->params[Batch\Entity::TYPE]);
            unset($this->params[Batch\Entity::SUB_TYPE]);
            unset($this->params[Batch\Entity::GATEWAY]);

            $processor->batchInstrumentation($this->params['data'], $instrumentationData);

            $app = \App::getFacadeRoot();

            $event = [
                "event_name"         => "FILE.RESPONSE.EVENT",
                "event_type"         => "file-response-debit-events",
                "version"            => "v1",
                "event_timestamp"    => Carbon::now()->timestamp,
                "producer_timestamp" => Carbon::now()->timestamp,
                "source"             => "file_response",
                "mode"               => $this->mode,
                "context"            => ['request_id' => $app['request']->getId(),
                                         'task_id'    => $app['request']->getTaskId()],
                "properties"         => $instrumentationData,
            ];

            $topic = 'events.emandate-file-processing-debit.' . $event["version"] . '.' . $this->mode;

            (new KafkaProducer($topic, stringify($event)))->Produce();

            $this->trace->info(
                TraceCode::EMANDATE_INSTRUMENTATION_KAFKA_PRODUCER_SUCCESS,
                [
                    "event" => $event,
                ]);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Logger::CRITICAL,
                TraceCode::EMANDATE_RESPONSE_INSTRUMENTATION_JOB_FAILED);
        }
    }
}
