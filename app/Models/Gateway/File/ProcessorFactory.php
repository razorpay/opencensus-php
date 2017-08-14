<?php

namespace RZP\Models\Gateway\File;

class ProcessorFactory
{
    protected static $processors = [];

    public static function getProcessor(Entity $gatewayFile, $type = null)
    {
        $type = $type ?: $gatewayFile->getType();
        $gateway = $gatewayFile->getGateway();
        $bank = $gatewayFile->getBank();

        $processorClass = self::getProcessorClass($type, $gateway, $bank);

        if (isset(self::$processors[$processorClass]) === true)
        {
            return self::$processors[$processorClass];
        }

        return self::$processors[$processorClass] = new $processorClass($gatewayFile);
    }

    public static function getProcessorClass(string $type, string $gateway, string $bank)
    {
        $baseNamespace = 'RZP\\Models\\Gateway\\File\\Processor\\';

        $processorNamespace = $baseNamespace . studly_case($type) . '\\';

        // If type is not emi, we use only the gateway name to get the processor class name
        // else for emi we use the bank name as gateway value will be ALL
        // Hacky way but works for current scenario. Will need to evolve if different
        // use case comes up
        if ($type !== Type::EMI)
        {
            $processorNamespace .= studly_case($gateway);
        }
        else
        {
             $processorNamespace .= $bank;
        }

        return $processorNamespace;
    }
}
