<?php

namespace RZP\Services\Mock;

use Rzp\Credcase\Apikey\V1\ApiKeyCreateResponse;
use Rzp\Credcase\Apikey\V1\ApiKeyListResponse;
use Rzp\Credcase\Apikey\V1\ApiKeyResponse;
use Rzp\Credcase\Apikey\V1\ApiKeyRotateResponse;
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

    public function findById($keyId, $expired = false, $includeSecret = false): \Rzp\Credcase\Apikey\V1\ApiKeyResponse {

        $expectedCredcaseResponse = new ApiKeyResponse();
        $expectedCredcaseResponse->setId($keyId);
        $expectedCredcaseResponse->setOwnerType("merchant");
        $expectedCredcaseResponse->setOwnerId("");
        $expectedCredcaseResponse->setMode($this->convertModeToEnum("test"));
        return $expectedCredcaseResponse;
    }

    public function getKeyByMerchantAndId($merchantId, $keyId): \Rzp\Credcase\Apikey\V1\ApiKeyResponse {

        $expectedCredcaseResponse = new ApiKeyResponse();
        $expectedCredcaseResponse->setId($keyId);
        $expectedCredcaseResponse->setOwnerType("merchant");
        $expectedCredcaseResponse->setOwnerId($merchantId);
        $expectedCredcaseResponse->setMode($this->convertModeToEnum("test"));
        return $expectedCredcaseResponse;
    }

    public function getKeysForMerchants($ownerIds, $mode, $count, $expired = false, $includeSecret = false): \Rzp\Credcase\Apikey\V1\ApiKeyListResponse {

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


    public function create($mode, string $ownerId): \Rzp\Credcase\Apikey\V1\ApiKeyCreateResponse
    {
        // Create a new instance of ApiKeyListResponse and set its fields
        $expectedCredcaseResponse = new ApiKeyCreateResponse();
        $expectedCredcaseResponse->setOwnerType("merchant");
        $expectedCredcaseResponse->setEntity('key');
        $expectedCredcaseResponse->setMode("test");
        $expectedCredcaseResponse->setSecret("123456789012345678901234");
        $expectedCredcaseResponse->setId("rzp_test_AltTestAuthKey");
        $expectedCredcaseResponse->setDomain("razorpay");
        $expectedCredcaseResponse->setOwnerId("100000razorpay");
        $expectedCredcaseResponse->setCreatedAt(time());
        $expectedCredcaseResponse->setUpdatedAt(time());

        return $expectedCredcaseResponse;
    }

    public function rotate($merchantId, $keyId, $delay): \Rzp\Credcase\Apikey\V1\ApiKeyRotateResponse
    {
        // Create a new instance of ApiKeyListResponse and set its fields
        $newKey = new ApiKeyCreateResponse();
        $newKey->setOwnerType("merchant");
        $newKey->setEntity('key');
        $newKey->setMode("test");
        $newKey->setSecret("123456789012345678901234");
        $newKey->setId("rzp_test_AltTestAuthKey");
        $newKey->setDomain("razorpay");
        $newKey->setOwnerId("100000razorpay");
        $newKey->setCreatedAt(time());
        $newKey->setUpdatedAt(time());


        $oldKey = new ApiKeyResponse();
        $oldKey->setOwnerType("merchant");
        $oldKey->setEntity('key');
        $oldKey->setMode("test");
        $oldKey->setId("rzp_test_AttTestAuthKey");
        $oldKey->setDomain("razorpay");
        $oldKey->setOwnerId("100000razorpay");
        $oldKey->setCreatedAt(time());
        $oldKey->setUpdatedAt(time());
        $response = new ApiKeyRotateResponse();
        $response->setNewKey($newKey);
        $response->setOldKey($oldKey);
        return $response;
    }
}
