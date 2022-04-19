<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;

class EcollectYesbank extends Base
{
    protected function validateInputFileEntries(array $input): array
    {
        //
        // Not doing anything here as in recon we don't need to validate / parse
        // entries at the time of saving the input file.
        //
        return [];
    }
}
