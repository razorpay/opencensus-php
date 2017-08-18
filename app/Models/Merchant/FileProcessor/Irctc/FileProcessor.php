<?php

namespace RZP\Models\Merchant\FileProcessor;

use RZP\Exception;
use RZP\Trace\Trace;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Base as BaseModel;

class FileProcessor extends BaseModel\Core
{
    const REFUND = 'refund';
    const SETTLEMENT = 'settlement';

    public function process(array $fileContents)
    {
        $processedIds = [];

        $details = [];

        foreach ($fileContents as $file => $fileDetails)
        {
            $type = $this->getType($fileDetails['file_details']);

            unset($fileDetails['file_details']);

            $details[$type]  = $fileDetails;
        }

        $this->processRTypeRefunds($details['refund']);

        $this->processSettlements($details['settlement']);

        $this->processCTypeRefunds($details['refund']);

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

    protected function getType($fileDetails)
    {
        $fileName = $fileDetails['file_name'];

        if (strpos($fileName, self::REFUND) !== false)
        {
            $type = self::REFUND;
        }
        else if (strpos($fileName, self::SETTLEMENT) !== false)
        {
            $type = self::SETTLEMENT;
        }

        return $type;
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
