<?php

namespace RZP\Models\P2p\Beneficiary;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Beneficiary;
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

    public function validate(array $input): array
    {
        $response = $this->processor->validate($input);

        return $response;
    }

    public function fetchAll(array $input): array
    {
        $response = $this->processor->fetchAll($input);

        return $response;
    }

    public function handle(array $input): array
    {
        $response = $this->processor->handleBeneficiary($input);

        return $response;
    }
}
