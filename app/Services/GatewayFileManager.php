<?php

namespace RZP\Services;

use RZP\Exception;
use Illuminate\Support\Manager;

class GatewayFileManager extends Manager
{
    protected $processors = [];

    public function getDefaultDriver()
    {
        throw new Exception\LogicException('No default driver is specified');
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

    /**
     * Gets GatewayFile Processor's namespace
     *
     * @param string $type
     * @param string $target
     * @return string
     *
     * $folderStruct is the nested folder structure inside the folder Processor
     * The following 3 lines replace '_' with '\\', and convert every first letter to uppercase
     * For example:
     *      $type = emandate_register
     *      $taget = hdfc
     *      $folderStruct = Emandate\\Register
     *      $driveNameSpace = 'RZP\\Models\\Gateway\\File\\Processor\\Emandate\\Register\\Hdfc'
     */
    protected function getProcessorDriver(string $type, string $target): string
    {
        $baseNamespace = 'RZP\\Models\\Gateway\\File\\Processor\\';

        $folderStruct = explode('_', $type);
        $folderStruct = array_map('ucfirst', $folderStruct);
        $folderStruct = implode('\\', $folderStruct);

        $driverNameSpace = $baseNamespace . $folderStruct . '\\' . studly_case($target);

        return $driverNameSpace;
    }
}
