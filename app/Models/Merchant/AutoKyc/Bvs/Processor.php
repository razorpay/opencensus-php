<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use \RZP\Models\Merchant\AutoKyc\Processor as AutoKycProcessor;
use RZP\Models\Merchant\AutoKyc\Response;

interface Processor extends AutoKycProcessor
{
    public function Process():Response;

    public function GetArtefact(): array;

    public function GetEnrichments(): array;

    public function GetRules(): array;
}
