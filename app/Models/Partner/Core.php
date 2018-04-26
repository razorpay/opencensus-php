<?php

namespace RZP\Models\Partner;

use Razorpay\OAuth;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Feature\Constants as Feature;

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
     * Connects a merchant to an application, and return a
     *
     * @param string          $appId
     * @param Merchant\Entity $merchant
     *
     * @return OAuth\Token\Entity
     */
    public function connectMerchant(string $appId, Merchant\Entity $merchant) : OAuth\Token\Entity
    {
        $app = $this->appRepo->findOrFailPublic($appId);

        // Validate that the app is a partner app

        // Get the app->client, client should be of type 'partner'

        $token = $this->app['authservice']->createPartnerToken($appId, $merchant->getId());

        $mapInput[Merchant\AccessMap\Entity::APPLICATION_ID] = $appId;

        (new Merchant\AccessMap\Core)->addMappingForOAuthApp($merchant, $mapInput);

        return $token['id'];
    }

    protected function validateMerchant(Merchant\Entity $merchant)
    {
        // What?
    }
}
