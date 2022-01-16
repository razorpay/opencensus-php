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

        self::debug_to_console($sqs);
        self::debug_to_console($command);
        var_dump($sqs, $command);
//        return [
//            'attributes' => ['queueURL' => $params[0]['QueueUrl'],
//                'delaySeconds' => $params[0]['DelaySeconds']],
//            'kind' => 'client',
//            'name' => 'Sqs sendMessage',
//        ];


    }

    static function debug_to_console($data) {
        $output = $data;
        if (is_array($output))
            $output = implode(',', $output);

        echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
    }
}
