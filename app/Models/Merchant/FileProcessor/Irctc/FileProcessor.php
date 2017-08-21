<?php

namespace RZP\Models\Merchant\FileProcessor\Irctc;

use RZP\Exception;
use RZP\Trace\Trace;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\FileProcessor\FileProcessor as BaseProcessor;

class FileProcessor extends BaseProcessor
{
    const REFUND = 'refund';
    const SETTLEMENT = 'settlement';

    public function process(array $filesContents)
    {
        $processedIds = [];

        $details = [];

        foreach ($filesContents as $file => $fileContents)
        {
            $type = $this->getType($fileContents['file_details']['file_name']);

            unset($fileContents['file_details']);

            $details[$type]  = $fileContents;
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
        $settlementProcessor = new Settlement();

        $settlementProcessor->process($details);
    }

    public function getType($fileName)
    {
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
