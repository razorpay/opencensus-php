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
            $type = $this->getType($fileDetails);
        }

        return $processedIds;
    }

    protected function getType(array $fileDetails)
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
