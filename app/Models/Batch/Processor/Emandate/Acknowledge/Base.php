<?php

namespace RZP\Models\Batch\Processor\Emandate\Acknowledge;

use RZP\Models\FileStore;
use RZP\Models\Batch\Processor\Base as BaseProcessor;

class Base extends BaseProcessor
{
    protected $gateway;

    protected function shouldMarkProcessedOnFailures(): bool
    {
        return false;
    }

    protected function createSetOutputFileAndSave(array & $entries, string $fileType = FileStore\Type::BATCH_OUTPUT)
    {
        return;
    }

    protected function sendProcessedMail()
    {
        return;
    }
}
