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
     * Connects a sub-merchant to an application, and return a
     *
     * @param OAuth\Application\Entity $app
     * @param Merchant\Entity          $merchant
     * @param Merchant\Entity          $subMerchant
     *
     * @return string
     * @throws BadRequestValidationFailureException
     */
    public function connectMerchant(
        OAuth\Application\Entity $app,
        Merchant\Entity $merchant,
        Merchant\Entity $subMerchant) : string
    {
        $appId = $app->getId();

        try
        {
            $token = $this->app['authservice']->createPartnerToken($appId, $merchant->getId(), $subMerchant->getId());

            $mapInput[Merchant\AccessMap\Entity::APPLICATION_ID] = $appId;
        }
        catch (\Throwable $t)
        {
            $this->trace->traceException($t);

            throw new BadRequestValidationFailureException('Token creation failed');
        }

        (new Merchant\AccessMap\Core)->addMappingForOAuthApp($subMerchant, $mapInput);

        return $token['partner_token'];
    }
}
