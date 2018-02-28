<?php

namespace RZP\Models\Merchant\Request;

use RZP\Models\Base;

class Service extends Base\Service
{
    /*
     * This function to be used when Merchant asks for specific product related status on relevant page on dashboard.
     */
    public function getForFeatureTypeAndName(string $type, string $featureName)
    {
        $merchantId = $this->merchant->getId();

        $input = array();

        $input[Entity::NAME] = $featureName;

        $input[Entity::TYPE] = $type;

        return (new Core)->fetch($input, $merchantId);
    }

    public function getAll(array $input)
    {
        $input[Constants::EXPAND] = [Entity::MERCHANT];

        return (new Core)->fetch($input);
    }

    public function getMerchantRequestStatusLog(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $merchantRequest = $this->repo->merchant_request->findOrFailPublic($id);

        return $merchantRequest->states->toArrayPublic();
    }

    public function get(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        return (new Core)->getMerchantRequestDetails($id);
    }

    public function create(array $input)
    {
        return (new Core)->createMerchantRequest($input);
    }

    public function update(string $id, array $input)
    {
        Entity::verifyIdAndStripSign($id);

        $request = $this->repo->merchant_request->findOrFailPublic($id);

        $core = new Core;

        $core->updateMerchantRequest($request, $input);

        return $core->getMerchantRequestDetails($id, $request->merchant->getId());
    }
}
