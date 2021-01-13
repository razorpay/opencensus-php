<?php

namespace RZP\Models\Merchant\AccountV2;

use RZP\Models\Merchant;

class Service extends Merchant\Service
{
    protected $response;

    public function createAccountV2(array $input): array
    {
        $account = $this->core()->createAccountV2($this->merchant, $input);

        return $this->getResponseObject()->createResponse($account);
    }

    public function fetchAccountV2(string $accountId): array
    {
        $account = $this->core()->fetchAccountV2($accountId);

        return $this->getResponseObject()->createResponse($account);
    }

    public function editAccountV2(string $accountId, array $input): array
    {
        $account = $this->core()->editAccountV2($this->merchant, $accountId, $input);

        return $this->getResponseObject()->createResponse($account);
    }

    protected function getResponseObject()
    {
        if ($this->response === null)
        {
            return new Response;
        }

        return $this->response;
    }
}
