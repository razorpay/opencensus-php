<?php

namespace RZP\Models\Batch\Processor\Emandate\Acknowledge;

use RZP\Models\Batch;
use RZP\Models\Batch\Processor\Emandate\Base as BaseProcessor;

class Base extends BaseProcessor
{
    protected $gateway;

    protected function shouldMarkProcessedOnFailures(): bool
    {
        return false;
    }

    public function getOutputFileHeadings(): array
    {
        $headerRule = $this->batch->getValidator()->getHeaderRule();

        return Batch\Header::getHeadersForFileTypeAndBatchType($this->outputFileType, $headerRule);
    }

    protected function sendProcessedMail()
    {
        return;
    }
}
