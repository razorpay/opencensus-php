<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Invoice;
use RZP\Trace\TraceCode;
use RZP\Models\Settings;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Status;
use RZP\Models\Batch\Helpers;
use RZP\Models\Batch\Constants;
use RZP\Models\SubscriptionRegistration;

class EcollectIcici extends Base
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
