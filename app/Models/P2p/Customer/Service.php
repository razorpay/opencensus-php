<?php

namespace RZP\Models\P2p\Customer;

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

    public function create(array $input): array
    {
        $response = $this->processor->create($input);

        return $response;
    }

    public function delete(array $input): array
    {
        $response = $this->processor->delete($input);

        return $response;
    }
}
