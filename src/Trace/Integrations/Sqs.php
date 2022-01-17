<?php

namespace OpenCensus\Trace\Integrations;

class Sqs implements IntegrationInterface
{

    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load Sqs integrations.', E_USER_WARNING);
        }

        opencensus_trace_method('Aws\AwsClient', 'execute', function ($command, $args) {
            if (get_class($command) === 'Aws\Sqs\SqsClient') {
                $internalFields = Sqs::formatArguments($args);
                $attributes = [
                    'command' => $args->getName(),
                    'sqs.QueueUrl' => $internalFields[1],
                    'sqs.DelaySeconds' => $internalFields[0],
                    'sqs.region' => $command->getRegion(),
                    'sqs.apiVersion' => ($command->getApi())->getApiVersion(),
                    'sqs.host' => ($command->getEndpoint())->getHost(),
                    'service.name' => ($command->getApi())->getServiceName(),
                    'span.kind' => 'client',
                ];
                return [
                    'attributes' => $attributes,
                    'kind' => 'client',
                    'name' => 'Sqs ' . $args->getName(),
                    'sameProcessAsParentSpan' => false
                ];
            }
            return [];
        });

        opencensus_trace_method('Aws\Sqs\SqsClient', '__construct', function ($command, $args) {
            if (get_class($command) === 'Aws\Sqs\SqsClient') {
                return [
                    'attributes' => [
                        'sqs.region' => $command->getRegion(),
                        'sqs.apiVersion' => ($command->getApi())->getApiVersion(),
                        'sqs.host' => ($command->getEndpoint())->getHost(),
                        'service.name' => ($command->getApi())->getServiceName(),
                    ],
                    'kind' => 'client',
                    'name' => 'Sqs',
                    'sameProcessAsParentSpan' => false
                ];
            }
            return [];
        });

    }

    public static function formatArguments($arguments)
    {
        $delaySeconds = 0;
        $QueueUrl = '';

        $iterator = $arguments->getIterator();
        while ($iterator->valid()) {
            if ($iterator->key() === 'DelaySeconds') {
                $delaySeconds = $iterator->current();
            }
            if ($iterator->key() === 'QueueUrl') {
                $QueueUrl = $iterator->current();
            }
            $iterator->next();
        }

        return array('delaySeconds' => $delaySeconds, 'QueueUrl' => $QueueUrl);
    }

}
