<?php

namespace RZP\Models\Partner;

use Razorpay\OAuth;

use RZP\Models\Base;

class Core extends Base\Core
{
    /**
     * @var OAuth\Application\Repository
     */
    protected $appRepo;

    public function __construct()
    {
        parent::__construct();

        $this->appRepo = new OAuth\Application\Repository;
    }

    /**
     * Connects a merchant to an application, both identified by
     * IDs
     *
     * @param string $appId
     * @param string $merchantId
     */
    public function connectMerchant(string $appId, string $merchantId)
    {
        $app = $this->appRepo->findOrFailPublic($appId);

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        // Validate that the app is a partner app

        // Validate merchant

        // Get the app->client, client should be of type partner

        // Create a partner token for the $merchantID

        // Update merchant_access_map with the relationship
    }
}
