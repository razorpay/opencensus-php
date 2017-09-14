<?php

namespace RZP\Services;

use RZP\Exception;
use Illuminate\Support\Manager;

class GatewayFileManager extends Manager
{
    protected $processors = [];

    public function getDefaultDriver()
    {
        throw new Exception\LogicException('No default gateway is specified');
    }

    public function getProcessor(string $type, string $target)
    {
        $driver = $this->getProcessorDriver($type, $target);

        if (isset($this->processors[$driver]) === true)
        {
            return $this->processors[$driver];
        }

        $processor = new $driver;

        $this->processors[$driver] = $processor;

        return $this->processors[$driver];
    }

    protected function getProcessorDriver(string $type, string $target): string
    {
        $baseNamespace = 'RZP\\Models\\Gateway\\File\\Processor\\';

        $driverNameSpace = $baseNamespace . studly_case($type) . '\\' . studly_case($target);

        return $driverNameSpace;
    }
}
