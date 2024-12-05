<?php

namespace RZP\Services\Mock;

use Rzp\Credcase\Apikey\V1\ApiKeyListResponse;
use Rzp\Credcase\Apikey\V1\ApiKeyResponse;
use RZP\Models\Key\CredcaseApi as CredcaseApi;


class Credcase extends CredcaseApi
{
    public function list(string $ownerId, string $mode, bool $expired): \Rzp\Credcase\Apikey\V1\ApiKeyListResponse
    {
        // Create a new instance of ApiKeyListResponse and set its fields
        $expectedCredcaseResponse = new ApiKeyListResponse();
        $expectedCredcaseResponse->setCount(1);
        $expectedCredcaseResponse->setEntity('key');

        // Mock an ApiKeyResponse to add to items
        $apiKeyResponse = new ApiKeyResponse();
        $apiKeyResponse->setId('rzp_test_AltTestAuthKey');
        $apiKeyResponse->setEntity('key');

        // Add the ApiKeyResponse to the items field
        $expectedCredcaseResponse->setItems([$apiKeyResponse]);
        return $expectedCredcaseResponse;
    }
}
