<?php

namespace RZP\Models\P2p\BlackList;

use RZP\Exception;
use RZP\Exception\RuntimeException;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Beneficiary;
/**
 * @property  Processor     $processor
 */
class Service extends Base\Service
{
    public function add(array $input): array
    {
        $response = $this->processor->add($input);

        return $response;
    }

    public function fetchAll(array $input): array
    {
        // TODO: $response = $this->processor->fetchAll($input);

        // TODO: return $response;

        throw new RuntimeException("Not implemented, service Implementation is on the way");
    }

    public function remove(array $input): array
    {
        $response = $this->processor->remove($input);

        return $response;
    }
}
