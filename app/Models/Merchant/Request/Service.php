<?php

namespace RZP\Models\Merchant\Request;

use RZP\Models\Base;

class Service extends Base\Service
{

    public function fetchMerchantRequests(array $input)
    {
        $merchantId = $this->merchant->getId();

        $input[Entity::MERCHANT_ID] = $merchantId;

        $input[Constants::EXPAND] = ['merchant'];

        return (new Core)->fetch($input);
    }

    public function fetchMerchantRequestForFeature(string $feature)
    {
        $merchantId = $this->merchant->getId();

        $input = array();

        $input[Entity::MERCHANT_ID] = $merchantId;

        $input[Entity::NAME] = $feature;

        return (new Core)->fetch($input);
    }

    public function fetch(array $input)
    {
        $input[Constants::EXPAND] = ['merchant'];

        return (new Core)->fetch($input);
    }

    public function getMerchantRequestStatusLog(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $merchantRequest = $this->repo->merchant_request->findOrFailPublic($id);

        return $merchantRequest->states->toArrayPublic();
    }

    public function getMerchantRequestDetails(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        return (new Core)->getMerchantRequestDetails($id);
    }

    public function createMerchantRequest(array $input)
    {
        return (new Core)->createMerchantRequest($input);
    }

    public function updateMerchantRequest(string $id, array $input)
    {
        $request = $this->repo->merchant_request->findByIdOrFail($id);

        (new Core)->updateMerchantRequest($request, $input);

        return $this->getMerchantRequestDetails($id);
    }
}
