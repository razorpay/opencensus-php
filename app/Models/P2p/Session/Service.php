<?php

namespace RZP\Models\P2p\Session;

use RZP\Models\P2p\Base;

/**
 * @property  Validator     $validator
 * @property  Processor     $processor
 */
class Service extends Base\Service
{
    public function create(array $input): array
    {
        return $this->processor->create($input);
    }
}
