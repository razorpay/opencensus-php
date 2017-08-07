<?php

namespace RZP\Models\Plan\Subscription\Addon;

use RZP\Models\Base;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function create(string $subscriptionId, array $input) : array
    {
        $subscription = $this->repo->subscription->findByPublicIdAndMerchant($subscriptionId, $this->merchant);

        $addons = [];

        foreach ($input as $addonInput)
        {
            $addons[] = $this->core->create($addonInput, $subscription);
        }

        //
        // TODO: Confirm with Shk on the response
        // The response is not our standard collection
        //
        return $addons;
    }

    public function fetch(string $id): array
    {
        $addon = $this->repo->addon->findByPublicIdAndMerchant($id, $this->merchant);

        return $addon->toArrayPublic();
    }

    public function fetchMultiple(array $input): array
    {
        $addons = $this->repo->addon->fetch($input, $this->merchant->getId());

        return $addons->toArrayPublic();
    }

    public function fetchDueAddons(string $subscriptionId): array
    {
        $subscription = $this->repo->subscription->findByPublicIdAndMerchant($subscriptionId, $this->merchant);

        $addons = $this->repo->addon->getUnusedAddonsForSubscription($subscription);

        return $addons->toArrayPublic();
    }

    public function delete(string $id): array
    {
        $addon = $this->repo->invoice->findByPublicIdAndMerchant($id, $this->merchant);

        $addon = $this->core->delete($addon);

        if ($addon === null)
        {
            return [];
        }

        return $addon->toArrayPublic();
    }
}
