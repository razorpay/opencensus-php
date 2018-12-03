<?php

namespace RZP\Models\P2p\Transaction\UpiTransaction;

use RZP\Exception;
use RZP\Models\P2p\Base;

/**
 * @property  Core          $core
 * @property  Validator     $validator
 * @property  Processor     $processor
 */
class Service extends Base\Service
{
    public function initiatePay(array $input): array
    {
        $response = $this->processor->initiatePay($input);

        return $response;
    }

    public function initiateCollect(array $input): array
    {
        $response = $this->processor->initiateCollect($input);

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

    public function initiateAuthorize(array $input): array
    {
        $response = $this->processor->initiateAuthorize($input);

        return $response;
    }

    public function authorize(array $input): array
    {
        $response = $this->processor->authorize($input);

        return $response;
    }

    public function reject(array $input): array
    {
        $response = $this->processor->reject($input);

        return $response;
    }
}
