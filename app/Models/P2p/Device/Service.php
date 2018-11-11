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
    public function create(array $input): array
    {
        $response = $this->processor->create($input);

        return $response;
    }

    public function fetch(array $input): array
    {
        $response = $this->processor->fetch($input);

        return $response;
    }

    public function refreshClToken(array $input): array
    {
        $response = $this->processor->refreshClToken($input);

        return $response;
    }

    public function delete(array $input): array
    {
        $response = $this->processor->delete($input);

        return $response;
    }
}
