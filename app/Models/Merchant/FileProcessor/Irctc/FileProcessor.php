<?php

namespace RZP\Models\Merchant\FileProcessor\Irctc;

use RZP\Models\Merchant\FileProcessor\FileProcessor as BaseProcessor;

class FileProcessor extends BaseProcessor
{
    const REFUND     = 'refund';
    const SETTLEMENT = 'settlement';

    public function process(array $filesContents)
    {
        $processedEntries = [];

        $processedEntries[self::REFUND][] = $this->processRTypeRefunds($filesContents[self::REFUND]);

        $processedEntries[self::SETTLEMENT][] = $this->processSettlements($filesContents[self::SETTLEMENT]);

        $processedEntries[self::REFUND][] = $this->processCTypeRefunds($filesContents[self::REFUND]);

        return $processedEntries;
    }

    protected function processRTypeRefunds(array $details)
    {
        $refundProcessor = new Refund(Refund::R_TYPE);

        return $refundProcessor->process($details);
    }

    protected function processCTypeRefunds(array $details)
    {
        $refundProcessor = new Refund(Refund::C_TYPE);

        return $refundProcessor->process($details);
    }

    protected function processSettlements(array $details)
    {
        $settlementProcessor = new Settlement();

        return $settlementProcessor->process($details);
    }

    public function getType(string $filename)
    {
        if (strpos($filename, self::REFUND) !== false)
        {
            $type = self::REFUND;
        }
        else if (strpos($filename, self::SETTLEMENT) !== false)
        {
            $type = self::SETTLEMENT;
        }

        return $type;
    }

    public function getDelimiter()
    {
        return '|';
    }

    public function getHeaders()
    {
        return [];
    }
}
