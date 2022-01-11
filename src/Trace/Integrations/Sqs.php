<?php

namespace OpenCensus\Trace\Integrations;

class Sqs implements IntegrationInterface
{

    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load Sqs integrations.', E_USER_WARNING);
        }

        opencensus_trace_method('Sqs\SqsClient', 'sendMessage', function () {
            $query = func_num_args();
            return [
                'attributes' => ['queueURL' => $query['QueueUrl']],
                'kind' => 'client',
                'name' => 'Sqs sendMessage',
            ];
        });


    }
}
