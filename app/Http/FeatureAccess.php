<?php

namespace RZP\Http;

use App;
use ApiResponse;
use RZP\Models\Merchant;
use RZP\Exception\BadRequestException;
use Illuminate\Foundation\Application;

use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Entity;
use RZP\Base\RepositoryManager;
use RZP\Models\Partner\Service as PartnerService;

class FeatureAccess
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * The repository manager instance.
     *
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * The merchant instance.
     *
     * @var
     */
    protected $merchant;

    /**
     * Api Route instance
     *
     * @var \RZP\Http\Route
     */
    protected $route;

    const IS_ADMIN_AUTH = 'is_admin_auth';

    /**
     * Access constructor.
     */
    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->ba = $this->app['basicauth'];

        $this->repo = $this->app['repo'];

        $this->route = $this->app['api.route'];

        $this->trace =  $this->app['trace'];

        $this->merchant = $this->ba->getMerchant();
    }

    /**
     * Checks if the accessed route is a feature route.
     * If yes:
     *  Checks if the merchant has access to the feature
     *  $authReturn will either be null or store an error object
     *
     * Null return indicates available access
     *
     * @param $authReturn
     * @return null
     * @throws BadRequestException
     */
    public function verifyFeatureAccess($authReturn)
    {
        // If the previous calls have thrown an error, just forward the same error
        if ($authReturn !== null)
        {
            return $authReturn;
        }

        $routeFeatures = $this->route->getCurrentRouteFeatures();

        // The current route does not require any feature to be present. Allow access.
        if (empty($routeFeatures) === true)
        {
            return null;
        }

        $merchantRouteFeatures = $this->getMerchantRouteFeatures($routeFeatures);

        $appId = $this->ba->getOAuthApplicationId();

        // If the merchant is directly accessing the resource, allow if it
        // has any of the route features required to access the resource.
        if (empty($appId) === true and empty($merchantRouteFeatures) === false)
        {
            return null;
        }
        else if (empty($appId) === false)
        {
            // if app is making the request
            $allowAccess = $this->allowAppToAccessRoute($routeFeatures, $merchantRouteFeatures);

            if ($allowAccess === true)
            {
                return null;
            }
        }

        $this->trace->info(
            TraceCode::ROUTE_NOT_FOUND,
            [
               'route_features'          => $routeFeatures,
               'merchant_Route_features' => $merchantRouteFeatures,
               'app_id_present'          => empty($appId)
            ]
        );

        // if app shouldn't access the route on the merchant behalf or
        // merchant is accessing the route directly and merchant doesn't have access
        return ApiResponse::routeNotFound();
    }

    /**
     * Checks if the accessed route is a feature route.
     * If yes:
     *  Checks if the org has denied access to the feature :
     *    if org has any of route feature : access unavailable
     *
     *  $authReturn will either be null or store an error object
     *
     * Null return indicates available access
     *
     * @return null
     */
    public function verifyOrgLevelFeatureAccess()
    {
        $orgLevelRouteFeatures = $this->route->getCurrentRouteOrgLevelFeatures();

        // The current route does not require any feature to be check. Allow access.
        if ((empty($orgLevelRouteFeatures) === true) or
            (isset($this->merchant) === false))
        {
            return null;
        }

        $orgEnableFeatures = $this->merchant->org->getEnabledFeatures();

        $orgRouteFeatures = array_intersect($orgLevelRouteFeatures, $orgEnableFeatures);

        // if org has not any enabled feature for route : allow access
        if (empty($orgRouteFeatures) === true)
        {
            return null;
        }

        $this->trace->info(TraceCode::ORG_LEVEL_FEATURE_ACCESS_VALIDATION_FAILURE, [
            Entity::ORG_ID      => $this->merchant->getOrgId(),
            Entity::MERCHANT_ID => $this->merchant->getId(),
        ]);

        if (($this->app['basicauth']->isAdminLoggedInAsMerchantOnDashboard() === true) or
            ($this->app['basicauth']->isAdminAuth() === true))
        {
            $this->trace->info(TraceCode::ALLOW_ROUTE_ACCESS_FOR_ADMIN_OR_LOGIN_AS_MERCHANT, [
                self::IS_ADMIN_AUTH => $this->app['basicauth']->isAdminAuth(),
            ]);

            return null;
        }

        return ApiResponse::routeNotFound();
    }

    public function verifyOrgAndMerchantFeatureAccess()
    {
        $orgAndMerchantRouteFeatures = $this->route->getCurrentRouteOrgAndMerchantFeatures();

        // The current route does require any feature to be check.
        if ((empty($orgAndMerchantRouteFeatures) === true) or
            (isset($this->merchant) === false))
        {
            return null;
        }
        $orgEnableFeatures = $this->merchant->org->getEnabledFeatures();

        $orgRouteFeatures = array_intersect($orgAndMerchantRouteFeatures, $orgEnableFeatures);

        if (empty($orgRouteFeatures) === true)
        {
            return null;
        }

        $merchantEnableFeatures = $this->getMerchantRouteFeatures($orgAndMerchantRouteFeatures);

        $this->trace->info(TraceCode::MERCHANT_FEATURE_ACCESS, [
            "merchant_feature"     => $merchantEnableFeatures,
            "orgAndMerchant"       => $orgAndMerchantRouteFeatures,
        ]);

        $routeFeatures = array_intersect($merchantEnableFeatures, $orgRouteFeatures);

        // if org has any enabled feature for route
        if (empty($routeFeatures) === false)
        {
            return null;
        }

        $this->trace->info(TraceCode::ORG_LEVEL_WHITELISTING_FEATURE_ACCESS_VALIDATION_FAILURE, [
            Entity::ORG_ID      => $this->merchant->getOrgId(),
            Entity::MERCHANT_ID => $this->merchant->getId(),
        ]);

        return ApiResponse::featurePermissionNotFound();
    }

    /**
     * Returns an array of route features that are available with the
     * merchant in the current mode.
     *
     * @param array $routeFeatures
     *
     * @return array
     * @throws BadRequestException
     */
    protected function getMerchantRouteFeatures(array $routeFeatures): array
    {
        // If the merchant has at least one of the features in the $features array enabled, we allow the request
        $merchantFeatures = $this->merchant->getEnabledFeatures();

        $merchantFeatures = $this->addPartnerFeaturesToMerchantIfApplicable($merchantFeatures);

        $this->trace->info(
            TraceCode::MERCHANT_FEATURE_ACCESS_DB,
            [
                "merchant_features" => $merchantFeatures,
                "route_features"    => $routeFeatures,
            ]
        );

        return array_intersect($routeFeatures, $merchantFeatures);
    }

    /**
     * Checks if the application requesting to access a feature-based
     * route should be given the access. Returns a boolean.
     *
     * 1. Block access for competitor applications.
     * 2. [simplified] Allow access because the app has the feature.
     * 3. [simplified] Allow access because the merchant has the feature.
     *
     * @param array $routeFeatures
     * @param array $merchantRouteFeatures
     *
     * @return bool
     * @throws BadRequestException
     */
    protected function allowAppToAccessRoute(
        array $routeFeatures,
        array $merchantRouteFeatures): bool
    {
        //
        // 1. Competitor OAuth applications should be blocked to access the S2S routes on behalf of the merchant,
        //    if the merchant does not have the allow_s2s_apps feature enabled.
        //

        if ($this->allowCompetitorApplications() === false)
        {
            return false;
        }

        //
        // 2. Allow the application to access the resource, if -
        //    - one of the features assigned to the application is not a restrictedAccessFeature, OR
        //    - the feature assigned to the application is a restrictedAccessFeature and both the app and
        //      the merchant have it enabled.
        //
        // restrictedAccessFeature routes can only be accessed by the application if both, the app and the merchant
        // have the feature enabled.
        //

        // Fetch all the features of the application that is trying to access the resource
        $appFeatures = $this->repo
                            ->feature
                            ->getApplicationFeatureNames($this->ba->getOAuthApplicationId())
                            ->all();

        $routeFeaturesAvailableWithApp = array_intersect($routeFeatures, $appFeatures);

        $restrictedAccessFeatures = Feature\Entity::$restrictedAccessFeatures;

        $appHasNonRestrictedRouteFeatures = filled(array_values(array_diff(
                                                $routeFeaturesAvailableWithApp,
                                                $restrictedAccessFeatures)));

        $appAndMerchantHaveRestrictedRouteFeature = filled(array_values(array_intersect(
                                                        $restrictedAccessFeatures,
                                                        $routeFeaturesAvailableWithApp,
                                                        $merchantRouteFeatures)));

        if (($appHasNonRestrictedRouteFeatures === true) or
            ($appAndMerchantHaveRestrictedRouteFeature === true) or
            ($this->merchantHaveRestrictedAccessIfApplicable($restrictedAccessFeatures, $merchantRouteFeatures) === true))
        {
            return true;
        }

        //
        // 3. If the application does not have any of the required route features, check the merchant features.
        //    Allow the application to access the resource if -
        //      - the merchant has any of the route features assigned, and,
        //      - the feature required is not a blacklisted feature.
        //      - the feature required is not a restricted access feature.
        //

        $appBlacklistedFeatures = Feature\Entity::$appBlacklistedFeatures;

        $this->removeMarketplaceFeatureAsRestrictedIfApplicable($restrictedAccessFeatures);

        //
        // From the features available with the merchant, remove the features using
        // which the applications should not be allowed to access the routes.
        //
        $merchantRouteFeaturesWhitelisted = array_values(array_diff(
                                                $merchantRouteFeatures,
                                                $appBlacklistedFeatures,
                                                $restrictedAccessFeatures));

        return (filled($merchantRouteFeaturesWhitelisted) === true);
    }

    /**
     *  For aggregator and fully managed partner, check if sub-merchant have
     *  restricted access feature enabled. Here we are skipping the application enabled features
     *  check and allowing only for aggregator / fully - managed partner
     *
     * @param $restrictedAccessFeatures
     * @param $merchantRouteFeatures
     *
     * @return bool
     */
    public function merchantHaveRestrictedAccessIfApplicable($restrictedAccessFeatures, $merchantRouteFeatures)
    {
        $merchantHaveRestrictedRouteFeature = filled(array_values(array_intersect(
                                                                      $restrictedAccessFeatures,
                                                                      $merchantRouteFeatures)));

        $partnerMerchantId = $this->app['basicauth']->getPartnerMerchantId();

        $partner = $this->repo->merchant->find($partnerMerchantId);

        if ($partner != null and
            $merchantHaveRestrictedRouteFeature === true and
            ($partner->isAggregatorPartner() === true) or ($partner->isFullyManagedPartner() === true))
        {
            return true;
        }

        return false;
    }

    /**
     * Defines the access of the competitor oauth applications.
     *
     * @return bool
     */
    protected function allowCompetitorApplications()
    {
        $isCompetitorApplication = in_array($this->ba->getOAuthApplicationId(),
                                    Feature\Type::S2S_APPLICATION_IDS,
                                    true);

        if (($isCompetitorApplication === true) and ($this->route->isS2SPaymentRoute() === true))
        {
            $isAllowed = $this->merchant->isFeatureEnabled(Feature\Constants::ALLOW_S2S_APPS);

            // Allow if the feature is enabled on the merchant account, block otherwise.
            return $isAllowed;
        }

        // Do not block if the app is not a competitor or if the competitor app is not trying to access an S2S route.
        return true;
    }

    /**
     * This is a temporary fix to copy partner feature to merchants.
     * Will be removed once proper fix is released.
     *
     * @param array|null $merchantFeatures
     * @return array|null
     * @throws BadRequestException
     */
    protected function addPartnerFeaturesToMerchantIfApplicable(?array $merchantFeatures): ?array
    {
        $this->enableQrCodeFeatureForMerchantIfApplicable($merchantFeatures);

        $this->enableMarketplaceForMerchantIfApplicable($merchantFeatures);

        return $merchantFeatures;
    }

    /**
     * Enable Route product api (payment_transfer, transfer_create etc) access to sub-merchants for
     * marketplace partners i.e. partners with route_partnerships and marketplace feature enabled
     * @throws BadRequestException
     */
    private function enableMarketplaceForMerchantIfApplicable(?array & $merchantFeatures): void
    {
        if (in_array(Feature\Constants::MARKETPLACE, $merchantFeatures) === false &&
            $this->shouldEnabledMarketplaceForMerchant() === true)
        {
            $merchantFeatures[] = Feature\Constants::MARKETPLACE;

            $this->trace->info(
                TraceCode::ALLOW_MARKETPLACE_FEATURE_FOR_SUBMERCHANT_SUCCESSFUL,
                [
                    Merchant\Constants::MERCHANT_ID => $this->merchant->getId(),
                    Merchant\Constants::PARTNER_ID  => $this->ba->getPartnerMerchantId()
                ]
            );
        }
    }

    /**
     * @throws BadRequestException
     */
    private function shouldEnabledMarketplaceForMerchant(): bool
    {
        if ($this->ba->isPartnerAuth() === false and $this->ba->isOAuth() === false)
        {
            return false;
        }

        $currentRoute = $this->route->getCurrentRouteName();

        $applicableRoutes = [
            'payment_transfer',
            'payment_fetch_transfers',
            'transfer_create',
            'transfer_fetch',
            'transfer_fetch_multiple'
        ];

        $partner = $this->ba->getPartnerMerchant();

        if (empty($partner) === true or
            in_array($currentRoute, $applicableRoutes, true) === false or
            in_array($partner->getPartnerType(), [Merchant\Constants::AGGREGATOR, Merchant\Constants::PURE_PLATFORM]) === false or
            (new PartnerService())->isFeatureEnabledForPartner(Feature\Constants::ROUTE_PARTNERSHIPS, $partner, $this->ba->getOAuthApplicationId()) === false)
        {
            return false;
        }

        $partnerFeatures = $partner->getEnabledFeatures();

        // check if the marketplace feature is enabled for the partner (partner auth + OAuth)
        return in_array(Feature\Constants::MARKETPLACE, $partnerFeatures, true);
    }

    private function enableQrCodeFeatureForMerchantIfApplicable(? array & $merchantFeatures): void
    {
        $featureName = Feature\Constants::QR_CODES;

        $currentRoute = $this->route->getCurrentRouteName();

        $whiteListedRoutes = [
            'qr_code_create',
            'qr_code_close',
            'qr_code_fetch',
            'qr_code_fetch_multiple',
            'qr_payments_fetch_multiple',
            'qr_payment_fetch_for_qr_code',
        ];

        if(in_array($currentRoute, $whiteListedRoutes) === false || // check if current route belongs to qr_codes
            in_array($featureName, $merchantFeatures) === true ||    // if merchant already has this feature enabled no need to process further
            $this->merchant->isLive() === false) // if merchant is not live, no need to do this override by partner
        {
            return;
        }

        $partners = (new Merchant\Core())->fetchAffiliatedPartners($this->merchant->getId());

        $partner = $partners->filter(function (Merchant\Entity $partner) use ($featureName) {
            return ($partner->isFeatureEnabled($featureName) === true);
        })->first();

        if (empty($partner) === true)
        {
            return;
        }

        $isFeatureOverrideAllowedForPartner = (new Merchant\Core)->isRazorxExperimentEnable($partner->getId(),
            Merchant\RazorxTreatment::PARTNER_QR_CODE_FEATURE_OVERRIDE);

        $this->trace->info(
            TraceCode::MERCHANT_FEATURE_ACCESS,
            [
                'partner_id'                            => $partner->getId(),
                'merchant_id'                           => $this->merchant->getId(),
                'partner_feature_override_exp_enabled'  => $isFeatureOverrideAllowedForPartner,
            ]
        );

        if (empty($partner) === false and $isFeatureOverrideAllowedForPartner === true)
        {
            $this->trace->info(
                TraceCode::MERCHANT_FEATURE_ACCESS,
                [
                    'overridden_feature_by_partner' => $partner->getId(),
                    'merchant_id'                   => $this->merchant->getId(),
                    'feature_name'                  => $featureName,
                ]
            );

            $merchantFeatures[] = $featureName;
        }
    }

    /**
     * @throws BadRequestException
     */
    private function removeMarketplaceFeatureAsRestrictedIfApplicable(array & $restrictedFeatures): void
    {
        if ($this->shouldEnabledMarketplaceForMerchant() === true)
        {
            $index = array_search(Feature\Constants::MARKETPLACE, $restrictedFeatures);

            if ($index !== false)
            {
                unset($restrictedFeatures[$index]);

                $this->trace->info(
                    TraceCode::UNRESTRICTED_MARKETPLACE_FEATURE_FOR_OAUTH_APPLICATION,
                    [
                        Merchant\Constants::MERCHANT_ID     => $this->merchant->getId(),
                        Merchant\Constants::PARTNER_ID      => $this->ba->getPartnerMerchantId(),
                        Merchant\Constants::APPLICATION_ID  => $this->ba->getOAuthApplicationId()
                    ]
                );
            }
        }
    }
}
