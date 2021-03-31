<?php

namespace RZP\Models\P2p\BankAccount\Bank;

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

    public function retrieveBanks(array $input): array
    {
        $response = $this->processor->retrieveBanks($input);

        return $response;
    }

    public function manageBulk(array $input): array
    {
        $response = $this->processor->manageBulk($input);

        return $response;
    }
}
