<?php

namespace RZP\Models\P2p\Vpa;

use RZP\Exception;
use RZP\Models\P2p\Base;

/**
 * @property  Core          $core
 * @property  Validator     $validator
 * @property  Processor     $processor
 */
class Service extends Base\Service
{
    public function fetchHandles(array $input): array
    {
        $response = $this->processor->fetchHandles($input);

        return $response;
    }

    public function initiateAdd(array $input): array
    {
        $response = $this->processor->initiateAdd($input);

        return $response;
    }

    public function add(array $input): array
    {
        $response = $this->processor->add($input);

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

    public function assignBankAccount(array $input): array
    {
        $response = $this->processor->assignBankAccount($input);

        return $response;
    }

    public function setDefault(array $input): array
    {
        $response = $this->processor->setDefault($input);

        return $response;
    }

    public function checkAvailability(array $input): array
    {
        $response = $this->processor->checkAvailability($input);

        return $response;
    }

    public function initiateCheckAvailability(array $input): array
    {
        $response = $this->processor->initiateCheckAvailability($input);

        return $response;
    }

    public function delete(array $input): array
    {
        $response = $this->processor->delete($input);

        return $response;
    }
}
