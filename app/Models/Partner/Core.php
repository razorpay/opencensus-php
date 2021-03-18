<?php

namespace RZP\Models\Partner;

use Razorpay\OAuth;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Base\RuntimeManager;
use RZP\Models\Merchant\Detail;
use RZP\Exception\BadRequestException;
use RZP\Jobs\PartnerActivationMigration;
use RZP\Models\Feature\Constants as FeatureConstant;
use RZP\Exception\BadRequestValidationFailureException;

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
     * Connects a sub-merchant to an application, and return a partner token
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

        (new Merchant\AccessMap\Core)->addMappingForOAuthApp($merchant, $subMerchant, $mapInput);

        return $token['partner_token'];
    }

    public function isForceGreylistMerchant(Merchant\Entity $subMerchant, Merchant\Entity $partner = null)
    {
        $subMerchantDetails = (new Detail\Core())->getMerchantDetails($subMerchant);

        if (empty($partner) === true)
        {
            $partners = (new Merchant\Core)->fetchAffiliatedPartners($subMerchant->getId());

            $partner = $partners->filter(function(Merchant\Entity $partner) use ($subMerchant) {

                return ($partner->forceGreyListInternational() === true);

            })->first();
        }

        //
        // if submerchant asked for international and partner wants to force international to greylist
        //

        return ((empty($partner) === false) and
                ($subMerchantDetails->getBusinessInternational() === true) and
                ($partner->forceGreyListInternational() === true));
    }

    public function isKycHandledBYPartner(Merchant\Entity $subMerchant)
    {
        $partners = (new Merchant\Core)->fetchAffiliatedPartners($subMerchant->getId());

        $partner = $partners->filter(function(Merchant\Entity $partner) use ($subMerchant) {

            return ($partner->isKycHandledByPartner() === true);

        })->first();

        return ((empty($partner) === false) and
                ($partner->isKycHandledByPartner() === true));
    }

    public function validateExternalIdForPartnerSubmerchant(Merchant\Entity $partner, string $externalId)
    {
        $merchantCore = new Merchant\Core;

        $appIds = $merchantCore->getPartnerApplicationIds($partner);

        $this->trace->info(TraceCode::PARTNER_FETCH_SUBMERCHANTS,
                           [
                               'partner_id'  => $partner->getId(),
                               'app_ids'     => $appIds,
                               'external_id' => $externalId,
                           ]);

        $params = [
            Merchant\Entity::EXTERNAL_ID => $externalId,
        ];

        $merchants = $this->repo->merchant->fetchSubmerchantsByAppIds($appIds, $params);

        if ($merchants->isNotEmpty() === true)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_DUPLICATE_EXTERNAL_ID,
                Merchant\Entity::EXTERNAL_ID,
                [
                    'partner_id' => $partner->getId(),
                    'merchants'  => $merchants->pluck(Merchant\Entity::ID)->toArray(),
                ]);
        }
    }

    /**
     * @param Merchant\Entity $merchant
     *
     * @return bool
     */
    public function isSmsBlockedSubmerchant(Merchant\Entity $merchant): bool
    {
        $partners = (new Merchant\Core())->fetchAffiliatedPartners($merchant->getId());

        //
        //submerchant can belong to only one aggregator or fully managed at a time
        //
        $partner = $partners->filter(function(Merchant\Entity $partner) {
            return (($partner->isAggregatorPartner() === true) or ($partner->isFullyManagedPartner() === true));
        })->first();

        if (empty($partner) === true)
        {
            return false;
        }

        //
        // Is the feature flag enabled for the submerchant or the partner
        //
        return (($merchant->isFeatureEnabled((FeatureConstant::BLOCK_ONBOARDING_SMS) === true)
            or ($partner->isFeatureEnabled(FeatureConstant::BLOCK_ONBOARDING_SMS) === true)));
    }

    public function createPartnerActivationForPartners(array $input)
    {
        RuntimeManager::setTimeLimit(1800);

        if (empty($input['merchant_ids']) === false)
        {
            PartnerActivationMigration::dispatch($this->mode, $input['merchant_ids']);

            return [];
        }

        $afterId = null;

        $count = 0;

        while (true)
        {
            $repo = $this->repo;

            $merchantIds = $repo->useSlave(function () use ($afterId, $repo) {
                return $repo->merchant->findPartnersWithoutPartnerActivation(1000, $afterId);
            });

            if (empty($merchantIds) === true)
            {
                break;
            }

            $afterId = end($merchantIds);

            $count += count($merchantIds);

            PartnerActivationMigration::dispatch($this->mode, $merchantIds);
        }

        return ['count' => $count];
    }
}
