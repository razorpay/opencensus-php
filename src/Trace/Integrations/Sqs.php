<?php

namespace OpenCensus\Trace\Integrations;

class Sqs implements IntegrationInterface
{

    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load Sqs integrations.', E_USER_WARNING);
        }

        opencensus_trace_method('Aws\AwsClient', 'execute', function ($command){
            $v='s';
        });
        opencensus_trace_method('Aws\AwsClientInterface', 'execute', function ($command){
            $v='s';
        });
        opencensus_trace_method('Aws\AwsClientTrait', 'execute', function ($command){
            $v='s';
        });
        opencensus_trace_method('Aws\Sqs\SqsClient', 'execute', function ($command){
            $v='s';
        });
        opencensus_trace_method('Aws\Sqs\SqsClient', '__construct',function ($config){
            $v = 's';
        });

        //debug
        opencensus_trace_method('Aws\AwsClient', 'execute', function ($command, $args){
            $v='s';
        });
        opencensus_trace_method('Aws\AwsClientInterface', 'execute', function ($command, $args){
            $v='s';
        });
        opencensus_trace_method('Aws\AwsClientTrait', 'execute', function ($command, $args){
            $v='s';
        });
        opencensus_trace_method('Aws\Sqs\SqsClient', 'execute', function ($command, $args){
            $v='s';
        });
        opencensus_trace_method('Aws\Sqs\SqsClient', '__construct',function ($command, $args){
            $v = 's';
        });

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
            'sameProcessAsParentSpan' => false,
        ];


    }

}
