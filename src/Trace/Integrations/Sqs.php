<?php

namespace OpenCensus\Trace\Integrations;

class Sqs implements IntegrationInterface
{

    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load Sqs integrations.', E_USER_WARNING);
        }

        opencensus_trace_method('Aws\AwsClient', 'execute', function ($command, $args){
            if(get_class($command) === 'Aws\Sqs\SqsClient'){
                $attributes = [
                    'command'              => $args[0],
                    'sqs.QueueUrl' => $args->data["QueueUrl"],
                    'sqs.DelaySeconds'         => $args->data["DelaySeconds"],
                    'sqs.region'              => $command->getRegion(),
                    'sqs.apiVersion'        => ($command->getApi())->getApiVersion(),
                    'sqs.host'        => ($command->getEndpoint())->getHost(),
                    'service.name'         => ($command->getApi())->getServiceName(),
                    'span.kind'            => 'client',
                ];
                var_dump($attributes);
                return [
                    'attributes' => $attributes,
                    'kind' => 'client',
                    'name' => 'Sqs '.$args[0],
                    'sameProcessAsParentSpan' => false
                ];
            }
            return [];
        });

        opencensus_trace_method('Aws\Sqs\SqsClient', '__construct',function ($command, $args){
            if (get_class($command) === 'Aws\Sqs\SqsClient'){
                return [
                    'attributes' => [
                        'sqs.region'              => $command->getRegion(),
                        'sqs.apiVersion'        => ($command->getApi())->getApiVersion(),
                        'sqs.host'        => ($command->getEndpoint())->getHost(),
                        'service.name'         => ($command->getApi())->getServiceName(),
                    ],
                    'kind' => 'client',
                    'name' => 'Sqs '.$args[0],
                    'sameProcessAsParentSpan' => false
                ];
            }
            return [];
        });

    }

}
