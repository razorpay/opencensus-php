<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs;

use RZP\Models\Merchant\AutoKyc\Response;
use \RZP\Models\Merchant\AutoKyc\Processor as AutoKycProcessor;

interface Processor extends AutoKycProcessor
{
    public function Process():Response;

    public function getArtefact(): array;

    public function GetEnrichments(): array;

    public function GetRules(): array;
}
