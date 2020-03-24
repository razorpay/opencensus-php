<?php

namespace RZP\Models\Batch\Processor;

class EcollectRbl extends Base
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
