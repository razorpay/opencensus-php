<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Poi;

use RZP\Models\Merchant\AutoKyc\Bvs\Processor;

class PoiProcessorMock implements Processor
{
    public function Process(array $defaultInput, array $input)
    {
        // TODO: Implement Process() method.
    }

    public function GetArtefact(array $defaultInput, array $input): array
    {
        // TODO: Implement GetArtefact() method.
    }

    public function GetEnrichments(): array
    {
        // TODO: Implement GetEnrichments() method.
    }

    public function GetRules(): array
    {
        // TODO: Implement GetRules() method.
    }
}
