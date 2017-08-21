<?php

namespace RZP\Models\Merchant\FileProcessor;

use RZP\Exception;
use RZP\Trace\Trace;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Base as BaseModel;

class FileProcessor extends BaseModel\Core
{
    public function process(array $fileContents)
    {
        $processedIds = [];

        foreach ($fileContents as $file => $fileDetails)
        {
            $typeProcessorName = __NAMESPACE__ . '\\' . studly_case($this->getType($fileDetails['file_details']));

            $typeProcessor = new $typeProcessorName;

            unset($fileDetails['file_details']);

            $processedIds[] = $typeProcessor->process($fileDetails);
        }

        return $processedIds;
    }


    //Should be implement in child class
    public function getType($fileName)
    {

    }

    public function getColumnHeadersForType($type)
    {
        $typeProcessorName = $this->getParentNamespace() . '\\' . studly_case($type);

        $typeProcessor = new $typeProcessorName;

        return $typeProcessor->getHeaders();
    }

    protected function getParentNamespace()
    {
        // Gets the namespace from the called class, by removing the last part of the FQCN.
        return join('\\', explode('\\', get_called_class(), -1));
    }


    public function getDelimiter()
    {
        return ',';
    }

    public function getHeaders()
    {
        return [];
    }
}
