<?php

namespace RZP\Models\Feature;

use App;
use ApiResponse;
use Illuminate\Foundation\Application;

use RZP\Base\RepositoryManager;

class Access
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
     * Access constructor.
     */
    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->ba = $this->app['basicauth'];

        $this->repo = $this->app['repo'];

        $this->merchant = $this->app['basicauth']->getMerchant();
    }

    /**
     * Checks if the accessed route is a feature route, if yes
     * checks if the merchant has access to the feature
     * $authReturn will either be null or store an error object
     *
     * Null return indicates available access
     * @param  mixed  $authReturn
     * @param  string $bearerToken
     * @return null
     */
    public function verifyFeatureAccess($authReturn, string $bearerToken = '')
    {
        if ($authReturn !== null)
        {
            return $authReturn;
        }

        $routeFeatures = $this->ba->getCurrentRouteFeatures();

        // The current route does not require any feature to be present. Allow access.
        if (empty($routeFeatures) === true)
        {
            return null;
        }

        $routeFeaturesAvailableWithMerchant = $this->getMerchantRouteFeatures($routeFeatures);

        if (empty($bearerToken) === true)
        {
            //
            // If the merchant is directly accessing the resource, allow if it
            // has any of the route features required to access the resource.
            //
            if (empty($routeFeaturesAvailableWithMerchant) === false)
            {
                return null;
            }

            return ApiResponse::routeNotFound();
        }

        $allowAccess = $this->allowApplicationToAccessFeatureRoute(
                            $routeFeatures,
                            $routeFeaturesAvailableWithMerchant);

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
     * @param array $routeFeatures
     * @param array $routeFeaturesAvailableWithMerchant
     *
     * @return bool
     */
    protected function allowApplicationToAccessFeatureRoute(
        array $routeFeatures,
        array $routeFeaturesAvailableWithMerchant): bool
    {
        //
        // 1. If the application has any of the route features required,
        //    allow the application to access the resource directly.
        //

        // Fetch all the features of the application that is trying to access the resource
        $applicationFeatures = $this->repo
                                    ->feature
                                    ->getApplicationFeatures($this->ba->applicationId)
                                    ->toArray();

        // Get an array of features
        $applicationFeatures = array_pluck($applicationFeatures, 'name');

        $routeFeaturesAvailableWithApp = array_intersect($routeFeatures, $applicationFeatures);

        if (empty($routeFeaturesAvailableWithApp) === false)
        {
            return true;
        }

        //
        // 2. If the application does not have any of the required route features,
        //    check the merchant features and allow the application to access the
        //    resource if the feature required is not a blacklisted feature.
        //

        $appBlacklistedFeatures = Entity::$appBlacklistedFeatures;

        $routeFeaturesAvailableWithMerchantWhitelisted = array_diff(
                                                            $routeFeaturesAvailableWithMerchant,
                                                            $appBlacklistedFeatures);

        if (empty($routeFeaturesAvailableWithMerchantWhitelisted) === false)
        {
            return true;
        }

        return false;
    }
}
