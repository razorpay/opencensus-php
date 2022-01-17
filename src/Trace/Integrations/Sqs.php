<?php

namespace OpenCensus\Trace\Integrations;

class Sqs implements IntegrationInterface
{

    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load Sqs integrations.', E_USER_WARNING);
        }

        opencensus_trace_method('Aws\Sqs\SqsClient', 'execute', [static::class, 'handleExecuteCommand']);

    }
    static function handleExecuteCommand($sqs, $command)
    {

        error_log($sqs);
        error_log($command);
        return [
            'attributes' => ['queueURL' => 'const',
                'delaySeconds' => '2'],
            'kind' => 'client',
            'name' => 'Sqs sendMessage',
        ];


    }

}
