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

        $input = [
            Entity::NAME => $featureName,
            Entity::TYPE => $type,
        ];

        $request = (new Core)->fetch($input, $merchantId, true);

        if (isset($request[Entity::ID]) === true)
        {
            $id = $request[Entity::ID];

            return $this->get($id);
        }

        return $request;
    }

    public function getAll(array $input)
    {
        $input[Constants::EXPAND] = [Entity::MERCHANT, 'merchant.merchantDetail'];

        return (new Core)->fetch($input);
    }

    public function getStatusLog(string $id)
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

    /**
     * Creates a merchant request. If the merchant request is for a product activation, it also adds the submissions
     * in the settings table.
     *
     * @param array $input
     *
     * @return array
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function create(array $input)
    {
        $core = new Core;

        $request = $core->createMerchantRequest($input);

        return $core->getMerchantRequestDetails($request->getId());
    }

    public function update(string $id, array $input)
    {
        Entity::verifyIdAndStripSign($id);

        $request = $this->repo->merchant_request->findOrFailPublic($id);

        $core = new Core;

        $core->updateMerchantRequest($request, $input);

        return $core->getMerchantRequestDetails($id, $request->merchant->getId());
    }

    public function bulkUpdate(array $input)
    {
        return (new Core)->bulkUpdateMerchantRequests($input);
    }

    public function getRejectionReasons()
    {
        return RejectionReasons::REJECTION_REASONS_MAPPING;
    }
}
