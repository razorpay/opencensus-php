<?php

namespace RZP\Models\P2p\Device\RegisterToken;

use RZP\Exception;
use RZP\Models\P2p\Base;

/**
 * @property  Core          $core
 * @property  Validator     $validator
 * @property  Processor     $processor
 */
class Service extends Base\Service
{
    public function add(array $input): array
    {
        $response = $this->processor->add($input);

        return $response;
    }

    public function verify(array $input): array
    {
        $response = $this->processor->verify($input);

        return $response;
    }
}
