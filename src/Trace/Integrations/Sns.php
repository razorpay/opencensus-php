<?php

namespace OpenCensus\Trace\Integrations;

class Sns implements IntegrationInterface
{


    public static function load()
    {
        if (!extension_loaded('opencensus')) {
            trigger_error('opencensus extension required to load Sns integrations.', E_USER_WARNING);
            return;
        }
        opencensus_trace_method('Aws\AwsClient', 'execute', [static::class, 'handleExecute']);
    }


    public static function handleExecute($command, $args)
    {
        if (get_class($command) === 'Aws\Sns\SnsClient') {
            $internalFields = Sns::formatArguments($command, $args);
            return [
                'attributes' => $internalFields,
                'kind' => 'client',
                'name' => 'Sns',
                'sameProcessAsParentSpan' => false
            ];
        }
    }

    public static function formatArguments($command, $arguments)
    {
        $TargetArn = "";
        $TopicArn = "";
        $CommandName = "";
        $region = $command->getRegion();
        $apiVersion = $command->getApi()->getApiVersion();
        $getHost = ($command->getEndpoint())->getHost();

        if (!empty($arguments)) {
            $CommandName = $arguments->getName();
            foreach ($arguments as $key => $value) {
                if ($key === "TargetArn") {
                    $TargetArn = $value;
                    continue;
                }
                if ($key === "TopicArn") {
                    $TopicArn = $value;
                }
            }
        }
        return [
            'command' => $CommandName,
            'span.kind' => 'client',
            'sns.TargetArn' => $TargetArn,
            'sns.TopicArn' => $TopicArn,
            'sns.region' => $region,
            'sns.apiVersion' => $apiVersion,
            'sns.host' => $getHost,
        ];
    }
}
