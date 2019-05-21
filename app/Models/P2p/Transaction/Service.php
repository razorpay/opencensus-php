<?php

namespace RZP\Models\P2p\Transaction;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Vpa;

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

    public function authorizeTransaction(array $input): array
    {
        $response = $this->processor->authorizeTransaction($input);

        return $response;
    }

    public function initiateReject(array $input): array
    {
        if ((isset($input['payee']['block']) === true) and $input['payee']['block'] === true)
        {
            $vpaProcessor = new Vpa\Processor();

            $response = $vpaProcessor->blockVpa($input);
        }

        $response = $this->processor->initiateReject($input);

        return $response;
    }

    public function reject(array $input): array
    {
        $response = $this->processor->reject($input);

        return $response;
    }

    public function raiseConcern(array $input): array
    {
        $response = $this->processor->raiseConcern($input);

        return $response;
    }

    public function concernStatus(array $input): array
    {
        $response = $this->processor->concernStatus($input);

        return $response;
    }

    public function fetchAllConcerns(array $input): array
    {
        $response = $this->processor->fetchAllConcerns($input);

        return $response;
    }
}
