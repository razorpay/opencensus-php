<?php

namespace OpenCensus\Trace\Integrations;

class Sqs implements IntegrationInterface
{

    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load Sqs integrations.', E_USER_WARNING);
            return;
        }

        opencensus_trace_method('Aws\AwsClient', 'execute', [static::class, 'handleExecute']);

        opencensus_trace_method('Aws\Sqs\SqsClient', '__construct', [static::class, 'handleConstruct']);

    }

    public static function handleConstruct($command, $args)
    {
        if (get_class($command) === 'Aws\Sqs\SqsClient') {
            return [
                'attributes' => [
                    'sqs.region' => $command->getRegion(),
                    'sqs.apiVersion' => ($command->getApi())->getApiVersion(),
                    'sqs.host' => ($command->getEndpoint())->getHost(),
                    'service.name' => ($command->getApi())->getServiceName(),
                ],
                'kind' => 'client',
                'name' => 'Sqs:Constructor',
                'sameProcessAsParentSpan' => false
            ];
        }

    }

    public static function handleExecute($command, $args)
    {
        if (get_class($command) === 'Aws\Sqs\SqsClient') {
            $internalFields = Sqs::formatArguments($command, $args);
            return [
                'attributes' => $internalFields,
                'kind' => 'client',
                'name' => 'Sqs ' . $args->getName(),
                'sameProcessAsParentSpan' => false
            ];
        }

    }

    public static function formatArguments($command, $arguments)
    {
        $delaySeconds = 0;
        $QueueUrl = '';
        $name = $arguments->getName();
        $region = $command->getRegion();
        $apiVersion = $command->getApi()->getApiVersion();
        $getHost = ($command->getEndpoint())->getHost();
        $serviceName = ($command->getApi())->getServiceName();
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
        $attributes = [
            'command' => $name,
            'sqs.QueueUrl' => $QueueUrl,
            'sqs.DelaySeconds' => $delaySeconds,
            'sqs.region' => $region,
            'sqs.apiVersion' => $apiVersion,
            'sqs.host' => $getHost,
            'service.name' => $serviceName,
            'span.kind' => 'client',
        ];
        return $attributes;
    }

}
