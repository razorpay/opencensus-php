<?php

namespace RZP\Models\Gateway\File;

class ProcessorFactory
{
    public static function getProcessor(Entity $gatewayFile)
    {
        $type = $gatewayFile->getType();
        $gateway = $gatewayFile->getGateway();

        $processorClass = self::getProcessorClass($type, $gateway);

        $processor = new $processorClass($gatewayFile);

        return $processor;
    }

    public static function getProcessorClass(string $type, string $gateway)
    {
        $baseNamespace = 'RZP\\Models\\Gateway\\File\\Processor\\';

        $processorNamespace = $baseNamespace . studly_case($type) . '\\' . studly_case($gateway);

        return $processorNamespace;
    }
}
