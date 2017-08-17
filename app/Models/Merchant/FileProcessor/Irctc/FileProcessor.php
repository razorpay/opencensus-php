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

        $details = [];

        foreach ($fileContents as $file => &$fileDetails)
        {
            $type = $this->getType($fileDetails);

            $details['type']  = $type;
        }

        $this->processRTypeRefunds();

        $this->processSettlements();

        $this->processCTypeRefunds();

        return $processedIds;
    }

    protected function processRTypeRefunds($details)
    {
        $refundProcessor = new Refund('R');

        $refundProcessor->process($details);
    }

    protected function processCTypeRefunds($detials)
    {
        $refundProcessor = new Refund('C');

        $refundProcessor->process($details);
    }

    protected function processSettlements($details)
    {
        $settlementProcessor = new Settlement('C');

        $settlementProcessor->process($details);
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
