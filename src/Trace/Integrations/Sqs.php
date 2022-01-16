<?php

namespace OpenCensus\Trace\Integrations;

class Sqs implements IntegrationInterface
{

    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load Sqs integrations.', E_USER_WARNING);
        }

        opencensus_trace_method('Aws\Sqs\SqsClient', 'execute', function ($params) {
            return [
                'attributes' => ['queueURL' => $params[0]['QueueUrl'],
                    'delaySeconds' => $params[0]['DelaySeconds']],
                'kind' => 'client',
                'name' => 'Sqs sendMessage',
            ];
        });

        opencensus_trace_method('Aws\Sqs\SqsClient', 'executeAsync', function ($params) {
            return [
                'attributes' => ['queueURL' => $params[0]['QueueUrl'],
                    'delaySeconds' => $params[0]['DelaySeconds']],
                'kind' => 'client',
                'name' => 'Sqs sendMessage',
            ];
        });

    }
}
