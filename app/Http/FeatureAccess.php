<?php

namespace RZP\Http;

use App;
use ApiResponse;
use Illuminate\Foundation\Application;

use RZP\Models\Feature;
use RZP\Base\RepositoryManager;

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

    /**
     * Access constructor.
     */
    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->ba = $this->app['basicauth'];

        $this->repo = $this->app['repo'];

        $this->route = $this->app['api.route'];

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
     * @param $bearerToken
     *
     * @return null
     */
    public function verifyFeatureAccess($authReturn, string $bearerToken = null)
    {
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

        if (empty($bearerToken) === true)
        {
            //
            // If the merchant is directly accessing the resource, allow if it
            // has any of the route features required to access the resource.
            //
            if (empty($merchantRouteFeatures) === false)
            {
                return null;
            }

            return ApiResponse::routeNotFound();
        }

        $allowAccess = $this->allowAppToAccessRoute($routeFeatures, $merchantRouteFeatures);

        if ($allowAccess === true)
        {
            return null;
        }

        return ApiResponse::routeNotFound();
    }

    /**
     * Returns an array of route features that are available with the
     * merchant in the current mode.
     *
     * @param array $routeFeatures
     *
     * @return array
     */
    protected function getMerchantRouteFeatures(array $routeFeatures): array
    {
        //
        // If the merchant has at least one of the features
        // in the $features array enabled, we allow the request
        //
        $merchantFeatures = $this->merchant->getEnabledFeatures();

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
                            ->getApplicationFeatures($this->ba->getOAuthApplicationId())
                            ->pluck(Feature\Entity::NAME)
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

        if ((($appHasNonRestrictedRouteFeatures === true) or
            ($appAndMerchantHaveRestrictedRouteFeature === true)))
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
}
