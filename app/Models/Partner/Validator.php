<?php

namespace RZP\Models\Partner;

use App;

use RZP\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Batch as Batch;
use RZP\Models\Merchant\Entity;
use RZP\Models\Merchant\Constants;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\AccessMap as AccessMap;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Merchant\MerchantApplications as MerchantApp;
use RZP\Models\Merchant\MerchantApplications\Entity as MerchantApplicationsEntity;

class Validator extends Base\Validator
{
    protected static $autoApproveMerchantActivationSplitzVariablesRules = [
        'partner_id'    => 'required|string',
        'limit'         => 'required|integer|max:400',
    ];

    protected static $autoApproveMerchantActivationInputRules = [
        'checkers_file' => 'required|file|max:1024' . Batch\Validator::CSV_EXCEL_MIME_RULE,
    ];

    protected static $resellerToAggregatorMigrationRules = [
        'merchant_id'     => 'required|alpha_num|size:14',
        'new_auth_create' => 'required|boolean',
    ];

    protected static $resellerToPurePlatformMigrationRules = [
        'merchant_id'     => 'required|alpha_num|size:14',
    ];

    protected static $purePlatformToResellerMigrationRules = [
        'merchant_id'     => 'required|alpha_num|size:14',
    ];

    protected static $regenerateReferralLinkRules = [
        'partner_ids' => 'required|array'
    ];

    protected static $raisePartnerMigrationRequestRules = [
        'phone_no'      => 'required|string',
        'website_url'   => 'required|string',
        'other_info'    => 'sometimes|string',
        'terms'         => 'required|array',
        'terms.consent' => 'required|boolean',
        'terms.url'     => 'required|string',
    ];

    protected static $getAppNameRules = [
        'payment_id'  => 'required|string|size:14',
    ];

    /**
     * @param Merchant\Entity $partner

     * @return void
     * @throws BadRequestException
     */
    public function validateIfAggregatorOrFullyManagedPartner(Merchant\Entity $partner)
    {
        $partnerType = $partner->getPartnerType();

        if (($partnerType !== Merchant\Constants::AGGREGATOR) and ($partnerType !== Merchant\Constants::FULLY_MANAGED))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
                [
                    Entity::PARTNER_ID   => $partner->getId(),
                    Entity::PARTNER_TYPE => $partnerType,
                ]
            );
        }
    }

    /**
     * @throws BadRequestException
     */
    public function validateIsAggregatorOrPurePlatformPartner(Entity $partner)
    {
        $partnerType = $partner->getPartnerType();

        if (($partnerType !== Merchant\Constants::AGGREGATOR) and ($partnerType !== Merchant\Constants::PURE_PLATFORM))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
                Entity::PARTNER_TYPE,
                [
                    Entity::PARTNER_ID   => $partner->getId(),
                    Entity::PARTNER_TYPE => $partnerType,
                ]
            );
        }
    }

    /**
     * @param string $fromAppType
     * @param string $toAppType
     *
     * @return void
     * @throws BadRequestException
     */
    public function validateAppTypeChange(string $fromAppType, string $toAppType)
    {
        $allowedAppTypes = [MerchantApplicationsEntity::REFERRED, MerchantApplicationsEntity::MANAGED];

        if ((in_array($fromAppType, $allowedAppTypes) === false) or
            (in_array($toAppType, $allowedAppTypes) === false) or
            ($fromAppType === $toAppType))
        {
            throw new BadRequestException (
                ErrorCode::BAD_REQUEST_INVALID_APPLICATION_TYPE,
                [
                    'from_app_type' => $fromAppType,
                    'to_app_type'   => $toAppType,
                ]
            );
        }
    }

    /**
     * Validates the existing and deleted merchant applications of the reseller partner who was once an Aggregator.
     * @param   PublicCollection $existingApplications      The existing merchant_applications of the Reseller
     * @param   PublicCollection $deletedApplications       The deleted merchant_applications of partner when it was an Aggregator
     *
     * @return  bool    Returns true if this Reseller is eligible for migration to aggregator, only based on apps check
     */
    public function validateMerchantAppForAggrTurnedReseller(
        PublicCollection $existingApplications, PublicCollection $deletedApplications
    ) : bool
    {
        $deletedAppTypeDiff = array_diff(
            array_column($deletedApplications->toArray(), MerchantApplicationsEntity::TYPE),
            [MerchantApplicationsEntity::MANAGED, MerchantApplicationsEntity::REFERRED]
        );
        if (
            count($existingApplications) > 1 or
            count($deletedApplications) !== 2 or
            empty($deletedAppTypeDiff) === false
        )
        {
            $this->getTrace()->info(
                TraceCode::RESELLER_TO_AGGREGATOR_UPDATE_INVALID_APPLICATIONS,
                [
                    'existingApplications' => $existingApplications,
                    'deletedApplications'  => $deletedApplications
                ]
            );
            return false;
        }
        return true;
    }

    /**
     * @param Entity $partner
     * @param string|null $oauthApplicationId
     * @throws BadRequestException
     */
    public function validateIfSubmerchantManualSettlementEnabled(Entity $partner, ?string $oauthApplicationId)
    {
        if ((new Service())->isFeatureEnabledForPartner(FeatureConstants::SUBM_MANUAL_SETTLEMENT, $partner, $oauthApplicationId) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_MANUAL_SETTLEMENT_NOT_ALLOWED, $partner->getId());
        }
    }

    /**
     * @param Entity $partner
     * @param string|null $oauthApplicationId
     * @throws BadRequestException
     */
    public function validateIfRoutePartnershipsFeatureEnabled(Entity $partner, ?string $oauthApplicationId)
    {
        if ((new Service())->isFeatureEnabledForPartner(FeatureConstants::ROUTE_PARTNERSHIPS, $partner, $oauthApplicationId) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ROUTE_PARTNERSHIPS_FEATURE_NOT_ENABLED,
                $partner->getId(),
                [MerchantApplicationsEntity::APPLICATION_ID => $oauthApplicationId]
            );
        }
    }

    public function validateOnboardingApisAccess(?string $partnerId, ?string $product = null)
    {
        if (empty($partnerId) === true or
            (new Merchant\Account\Core())->isOnboardingV2ApiRoute() === false or
            (empty($product) === false and $product !== Merchant\Product\Name::PAYMENT_GATEWAY))
        {
            return;
        }

        $app = App::getFacadeRoot();

        $properties = [
            'id'            => $partnerId,
            'experiment_id' => $app['config']->get('app.enable_onboarding_apis_access_exp_id')
        ];

        if ((new MerchantCore())->isSplitzExperimentEnable($properties, 'enable') !== true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED, null, ['partner_id' => $partnerId]);
        }
    }

    /**
     * This method checks if there exists a mapping between the partner and sub-merchant
     *
     * @param Entity $partner
     * @param Entity $subMerchant
     *
     * @throws BadRequestException
     */
    public function validatePartnerSubMerchantMapping(Merchant\Entity $partner, Merchant\Entity $subMerchant)
    {
        $appType = (new MerchantApp\Core())->getDefaultAppTypeForPartner($partner);

        $isMapped = (new AccessMap\Core())->isMerchantMappedToPartnerWithAppType($partner, $subMerchant, $appType);

        if ($isMapped === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PARTNER_MERCHANT_MAPPING_NOT_FOUND,
                [
                    Entity::PARTNER_ID  => $partner->getId(),
                    Entity::MERCHANT_ID => $subMerchant->getId(),
                    Constants::APP_TYPE => $appType,
                ]
            );
        }
    }
}
