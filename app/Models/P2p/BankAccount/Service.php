<?php

namespace RZP\Models\P2p\BankAccount;

use RZP\Exception;
use RZP\Models\P2p\Base;

/**
 * @property  Core          $core
 * @property  Validator     $validator
 * @property  Processor     $processor
 */
class Service extends Base\Service
{
    public function initiateRetrieve(array $input): array
    {
        $response = $this->processor->initiateRetrieve($input);

        return $response;
    }

    public function retrieve(array $input): array
    {
        $response = $this->processor->retrieve($input);

        return $response;
    }

    public function fetchAll(array $input): array
    {
        $response = $this->processor->fetchAll($input);

        return $response;
    }

    public function fetch(array $input): array
    {
        $response = $this->processor->fetch($input);

        return $response;
    }

    public function initiateSetUpiPin(array $input): array
    {
        $response = $this->processor->initiateSetUpiPin($input);

        return $response;
    }

    public function setUpiPin(array $input): array
    {
        $response = $this->processor->setUpiPin($input);

        return $response;
    }

    public function initiateFetchBalance(array $input): array
    {
        $response = $this->processor->initiateFetchBalance($input);

        return $response;
    }

    public function fetchBalance(array $input): array
    {
        $response = $this->processor->fetchBalance($input);

        return $response;
    }
}
