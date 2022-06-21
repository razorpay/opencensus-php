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
    public function initiateVerification(array $input): array
    {
        $response = $this->processor->initiateVerification($input);

        return $response;
    }

    public function verification(array $input): array
    {
        $response = $this->processor->verification($input);

        return $response;
    }

    public function initiateGetToken(array $input): array
    {
        $response = $this->processor->initiateGetToken($input);

        return $response;
    }

    public function getToken(array $input): array
    {
        $response = $this->processor->getToken($input);

        return $response;
    }

    public function deregister(array $input): array
    {
        $response = $this->processor->deregister($input);

        return $response;
    }

    public function updateWithAction(array $input): array
    {
        $response = $this->processor->updateWithAction($input);

        return $response;
    }

    public function fetchAll(array $input): array
    {
        $response = $this->processor->fetchAll($input);

        return $response;
    }
}
