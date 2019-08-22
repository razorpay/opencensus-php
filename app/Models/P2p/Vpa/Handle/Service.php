<?php

namespace RZP\Models\P2p\Vpa\Handle;

use RZP\Exception;
use RZP\Models\P2p\Base;

/**
 * @property  Core          $core
 * @property  Validator     $validator
 * @property  Processor     $processor
 */
class Service extends Base\Service
{
    public function fetchAll(array $input): array
    {
        $response = $this->processor->fetchAll($input);

        return $response;
    }

    public function add(array $input): array
    {
        $response = $this->processor->add($input);

        return $response;
    }

    public function update(array $input): array
    {
        $response = $this->processor->update($input);

        return $response;
    }
}
