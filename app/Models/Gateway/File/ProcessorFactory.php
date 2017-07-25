<?php

namespace RZP\Models\Gateway\File;

class ProcessorFactory
{
    public static function getProcessor(Entity $gatewayFile)
    {
        $type = $gatewayFile->getType();
        $gateway = $gatewayFile->getGateway();
        $bank = $gatewayFile->getBank();

        $processorClass = self::getProcessorClass($type, $gateway);

        $processor = new $processorClass($gatewayFile);

        return $processor;
    }

    public static function getProcessorClass(string $type, $gateway = null, $bank = null)
    {
        $baseNamespace = 'RZP\\Models\\Gateway\\File\\Processor\\';

        $processorNamespace = $baseNamespace . studly_case($type) . '\\';

        if (isset($gateway) === true)
        {
            $processorNamespace .= studly_case($gateway);
        }

        if (isset($bank) === true)
        {
            $processorNamespace .= '\\' . $bank;
        }

        return $processorNamespace;
    }
}
