<?php

namespace RZP\Models\P2p\Device;

use RZP\Exception;
use RZP\Models\P2p\Base;

/**
 * @property  Core          $core
 * @property  Validator     $validator
 * @property  Processor     $processor
 */
class Service extends Base\Service
{
    public function startVerification(array $input): array
    {
        $response = $this->processor->startVerification($input);

        return $response;
    }

    public function getVerificationStatus(array $input): array
    {
        $response = $this->processor->getVerificationStatus($input);

        return $response;
    }

    public function refreshClToken(array $input): array
    {
        $response = $this->processor->refreshClToken($input);

        return $response;
    }

    public function deregister(array $input): array
    {
        $response = $this->processor->deregister($input);

        return $response;
    }
}
