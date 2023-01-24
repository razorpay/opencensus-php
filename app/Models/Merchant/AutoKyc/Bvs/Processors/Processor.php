<?php

namespace RZP\Models\Merchant\AutoKyc\Bvs\Processors;

use RZP\Models\Merchant\AutoKyc\Response;
use \RZP\Models\Merchant\AutoKyc\Processor as AutoKycProcessor;

interface Processor extends AutoKycProcessor
{
    public function Process():Response;

    public function getArtefact(): array;

    public function GetEnrichments(): array;

    public function getEnrichmentsV2(): array;

    public function GetRules(): array;

    public function FetchDetails(string $validationId): Response;

    public function getVerificationUrl(array $input);

    public function fetchVerificationDetails(array $input);
}
