<?php

namespace OpenCensus\Trace\Integrations;

class Sns implements IntegrationInterface
{


    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load Sqs integrations.', E_USER_WARNING);
            return;
        }

        opencensus_trace_method('Aws\AwsClient', 'execute', [static::class, 'handleExecute']);

        opencensus_trace_method('Aws\Sns\SnsClient', '__construct', [static::class, 'handleConstruct']);

    }

    public static function handleConstruct($command, $args)
    {
        if (get_class($command) === 'Aws\Sns\SnsClient') {
            return [
                'attributes' => [
                    'sns.region' => $command->getRegion(),
                    'sns.apiVersion' => ($command->getApi())->getApiVersion(),
                    'sns.host' => ($command->getEndpoint())->getHost(),
                    'service.name' => ($command->getApi())->getServiceName(),
                ],
                'kind' => 'client',
                'name' => 'Sns:Constructor',
                'sameProcessAsParentSpan' => false
            ];
        }
    }

    public static function handleExecute($command, $args)
    {
        if (get_class($command) === 'Aws\Sns\SnsClient') {
            $internalFields = Sns::formatArguments($command, $args);
            return [
                'attributes' => $internalFields,
                'kind' => 'client',
                'name' => 'Sns ' . $args->getName(),
                'sameProcessAsParentSpan' => false
            ];
        }
    }

    public static function formatArguments($command, $arguments)
    {
        return  [
            'command' => 'sqs',
            'span.kind' => 'sqs-client',
        ];
    }
}
