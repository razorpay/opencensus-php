<?php

namespace RZP\Services\Mock;

class PaymentsCrossBorderClient
{
    /**
     * {@inheritDoc}
     */
    public function makeRequest(string $path, array $payload, int $timeoutMs = null)
    {
        $res = new \WpOrg\Requests\Response;
        $res->success = true;
        $res->body = '{}';

        return $res;
    }

    public function getDocuments($input)
    {
        return null;
    }

    public function postDCSConfiguration($input)
    {
        return null;
    }

    public function getDCSConfiguration($input)
    {
        return null;
    }
    public function requestInternalFirsDocument($input)
    {
        return $this->response;
    }
    public function createForexCharges($header, $input)
    {
        return null;
    }

}
