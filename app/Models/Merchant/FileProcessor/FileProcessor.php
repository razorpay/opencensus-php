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
            $typeProcessor = __NAMESPACE__ . studly_case($this->getType($fileDetails['file_details']));

            unset($fileDetails['file_details']);

            $processedIds[] = $typeProcessor->process($fileDetails);
        }

        return $processedIds;
    }


    //Should be implement in child class
    protected function getType()
    {

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
